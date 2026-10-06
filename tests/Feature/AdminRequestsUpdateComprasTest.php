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

    public function test_salva_dados_da_compra_junto_com_a_aprovacao_e_o_total_digitado(): void
    {
        $item = PurchaseRequest::factory()->create(['quantity' => 3]);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'            => 'aprovado',
            'empresa_compradora' => 'Binário',
            'supplier'          => 'kabum',
            'codigo_fornecedor' => 'FORN-123',
            'preco_unitario'    => '1.250,50',
            'valor'             => '3.700,00',
            'data_compra'       => '2026-09-20',
        ])->assertSessionDoesntHaveErrors();

        $item->refresh();
        $this->assertSame('aprovado', $item->status);
        $this->assertSame('Kabum', $item->supplier);
        $this->assertSame('FORN-123', $item->codigo_fornecedor);
        $this->assertEquals(1250.50, (float) $item->preco_unitario);
        $this->assertEquals(3700.00, (float) $item->valor);
        $this->assertSame('2026-09-20', $item->data_compra->format('Y-m-d'));
    }

    public function test_data_coleta_nao_e_mais_editavel_pelo_modal_de_atualizar(): void
    {
        $item = PurchaseRequest::factory()->create(['data_coleta' => '2026-09-15']);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'      => 'aprovado',
            'empresa_compradora' => 'Binário',
            'supplier'    => 'kabum',
            'data_coleta' => '2026-09-22',
        ]);

        $this->assertSame('2026-09-15', $item->refresh()->data_coleta->format('Y-m-d'));
    }

    public function test_sem_total_digitado_o_total_fica_nulo(): void
    {
        $item = PurchaseRequest::factory()->create(['valor' => null]);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'   => 'aprovado',
            'empresa_compradora' => 'Binário',
            'supplier' => 'kabum',
        ]);

        $this->assertNull($item->refresh()->valor);
    }

    public function test_total_nao_e_calculado_pelo_unitario_nem_pela_caixa(): void
    {
        $item = PurchaseRequest::factory()->create(['quantity' => 3, 'valor' => null]);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'         => 'aprovado',
            'empresa_compradora' => 'Binário',
            'supplier'       => 'kabum',
            'preco_unitario' => '10,00',
            'preco_caixa'    => '50,00',
            'data_compra'    => '2026-09-20',
        ])->assertSessionDoesntHaveErrors();

        $item->refresh();
        $this->assertEquals(10.00, (float) $item->preco_unitario);
        $this->assertEquals(50.00, (float) $item->preco_caixa);
        $this->assertNull($item->valor);
    }

    public function test_rejeita_total_invalido(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'   => 'aprovado',
            'empresa_compradora' => 'Binário',
            'supplier' => 'kabum',
            'valor'    => 'abc',
        ])->assertSessionHasErrors('valor');
    }

    public function test_anexa_pedido_de_compra_pelo_modal_de_atualizar(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'        => 'aprovado',
            'empresa_compradora' => 'Binário',
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
            'empresa_compradora' => 'Binário',
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
            'empresa_compradora' => 'Binário',
            'pedido_compra' => UploadedFile::fake()->create('antigo.pdf', 10, 'application/pdf'),
        ]);
        $caminhoAntigo = $item->refresh()->pedido_compra_path;

        $this->actingAs($admin)->patch(route('admin.requests.update', $item), [
            'status'        => 'aprovado',
            'empresa_compradora' => 'Binário',
            'pedido_compra' => UploadedFile::fake()->create('novo.pdf', 10, 'application/pdf'),
        ]);

        Storage::disk('local')->assertMissing($caminhoAntigo);
        $this->assertSame('novo.pdf', $item->refresh()->pedido_compra_nome);
    }
}
