<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRequestsUpdateComprasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_salva_dados_da_compra_junto_com_a_aprovacao_e_calcula_o_total(): void
    {
        $item = PurchaseRequest::factory()->create(['quantity' => 3]);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'            => 'aprovado',
            'supplier'          => 'kabum',
            'codigo_fornecedor' => 'FORN-123',
            'preco_unitario'    => '1.250,50',
            'data_compra'       => '2026-09-20',
            'data_coleta'       => '2026-09-22',
        ])->assertSessionDoesntHaveErrors();

        $item->refresh();
        $this->assertSame('aprovado', $item->status);
        $this->assertSame('Kabum', $item->supplier);
        $this->assertSame('FORN-123', $item->codigo_fornecedor);
        $this->assertEquals(1250.50, (float) $item->preco_unitario);
        $this->assertEquals(3751.50, (float) $item->valor);
        $this->assertSame('2026-09-20', $item->data_compra->format('Y-m-d'));
        $this->assertSame('2026-09-22', $item->data_coleta->format('Y-m-d'));
    }

    public function test_sem_preco_unitario_o_total_fica_nulo(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'   => 'aprovado',
            'supplier' => 'kabum',
        ]);

        $this->assertNull($item->refresh()->valor);
    }

    public function test_coleta_nao_pode_ser_antes_da_compra_no_modal_de_atualizar(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'      => 'aprovado',
            'data_compra' => '2026-09-20',
            'data_coleta' => '2026-09-01',
        ])->assertSessionHasErrors('data_coleta');
    }

    public function test_anexa_pedido_de_compra_pelo_modal_de_atualizar(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'        => 'aprovado',
            'pedido_compra' => UploadedFile::fake()->create('pedido 4512.pdf', 200, 'application/pdf'),
        ]);

        $item->refresh();
        $this->assertSame('pedido 4512.pdf', $item->pedido_compra_nome);
        Storage::disk('local')->assertExists($item->pedido_compra_path);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.compras.pedido', $item))
            ->assertOk();

        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('pedido 4512.pdf', $response->headers->get('content-disposition'));
    }

    public function test_admin_anexa_orcamento_do_vendedor_quando_ele_esqueceu(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status' => 'aprovado',
            'anexo'  => UploadedFile::fake()->create('orcamento.pdf', 200, 'application/pdf'),
        ]);

        $item->refresh();
        $this->assertSame('orcamento.pdf', $item->anexo_nome);
        Storage::disk('local')->assertExists($item->anexo_path);

        $response = $this->actingAs($this->admin())
            ->get(route('requests.anexo', $item))
            ->assertOk();

        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('orcamento.pdf', $response->headers->get('content-disposition'));
    }

    public function test_trocar_o_anexo_pelo_modal_apaga_o_arquivo_antigo(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.requests.update', $item), [
            'status'        => 'aprovado',
            'pedido_compra' => UploadedFile::fake()->create('antigo.pdf', 10, 'application/pdf'),
        ]);
        $caminhoAntigo = $item->refresh()->pedido_compra_path;

        $this->actingAs($admin)->patch(route('admin.requests.update', $item), [
            'status'        => 'aprovado',
            'pedido_compra' => UploadedFile::fake()->create('novo.pdf', 10, 'application/pdf'),
        ]);

        Storage::disk('local')->assertMissing($caminhoAntigo);
        $this->assertSame('novo.pdf', $item->refresh()->pedido_compra_nome);
    }
}
