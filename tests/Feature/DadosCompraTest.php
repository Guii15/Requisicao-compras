<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DadosCompraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function dadosValidos(array $extra = []): array
    {
        return array_merge([
            'data_compra'       => '2026-09-20',
            'preco_unitario'    => '1.250,50',
            'codigo_fornecedor' => 'FORN-123',
            'supplier'          => 'kabum',
        ], $extra);
    }

    public function test_guest_e_vendedor_nao_acessam_fila_de_compras(): void
    {
        $this->get(route('admin.compras.index'))->assertRedirect(route('login'));

        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null]);
        $this->actingAs($vendedor)->get(route('admin.compras.index'))->assertForbidden();
    }

    public function test_fila_lista_so_requisicoes_aprovadas(): void
    {
        $aprovada = PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Mouse Aprovado']);
        PurchaseRequest::factory()->create(['product_name' => 'Teclado Pendente']);

        $this->actingAs($this->admin())
            ->get(route('admin.compras.index'))
            ->assertOk()
            ->assertSee('Mouse Aprovado')
            ->assertDontSee('Teclado Pendente');
    }

    public function test_filtro_sem_dados_esconde_quem_ja_tem_dados_da_compra(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Item Sem Dados']);
        PurchaseRequest::factory()->aprovado()->create([
            'product_name'   => 'Item Com Dados',
            'data_compra'    => '2026-09-20',
            'preco_unitario' => 10,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.compras.index', ['situacao' => 'sem_dados']))
            ->assertSee('Item Sem Dados')
            ->assertDontSee('Item Com Dados');
    }

    public function test_formulario_abre_para_requisicao_aprovada(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Monitor 24']);

        $this->actingAs($this->admin())
            ->get(route('admin.compras.edit', $item))
            ->assertOk()
            ->assertSee('Monitor 24');
    }

    public function test_nao_registra_compra_de_requisicao_nao_aprovada(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.compras.edit', $item))
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->patch(route('admin.compras.update', $item), $this->dadosValidos())
            ->assertNotFound();
    }

    public function test_salva_dados_e_o_total_digitado_manualmente(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 3]);

        $this->actingAs($this->admin())
            ->patch(route('admin.compras.update', $item), $this->dadosValidos(['valor' => '3.700,00']))
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('2026-09-20', $item->data_compra->format('Y-m-d'));
        $this->assertEquals(1250.50, (float) $item->preco_unitario);
        $this->assertEquals(3700.00, (float) $item->valor);
        $this->assertSame('FORN-123', $item->codigo_fornecedor);
        $this->assertSame('Kabum', $item->supplier);
    }

    public function test_total_nao_e_calculado_automaticamente_pelo_unitario_e_caixa(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 3, 'valor' => null]);

        $this->actingAs($this->admin())
            ->patch(route('admin.compras.update', $item), $this->dadosValidos(['preco_caixa' => '20,00']))
            ->assertRedirect();

        $item->refresh();
        $this->assertEquals(1250.50, (float) $item->preco_unitario);
        $this->assertEquals(20.00, (float) $item->preco_caixa);
        $this->assertNull($item->valor);
    }

    public function test_anexa_pedido_de_compra_em_disco_privado(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->actingAs($this->admin())->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('pedido 4512.pdf', 200, 'application/pdf'),
        ]));

        $item->refresh();
        $this->assertSame('pedido 4512.pdf', $item->pedido_compra_nome);
        Storage::disk('local')->assertExists($item->pedido_compra_path);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.compras.pedido', $item))
            ->assertOk();

        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('pedido 4512.pdf', $response->headers->get('content-disposition'));
    }

    public function test_trocar_o_anexo_apaga_o_arquivo_antigo(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->aprovado()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('antigo.pdf', 10, 'application/pdf'),
        ]));
        $caminhoAntigo = $item->refresh()->pedido_compra_path;

        $this->actingAs($admin)->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('novo.pdf', 10, 'application/pdf'),
        ]));

        Storage::disk('local')->assertMissing($caminhoAntigo);
        $this->assertSame('novo.pdf', $item->refresh()->pedido_compra_nome);
    }

    public function test_salvar_sem_novo_anexo_mantem_o_anterior(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->aprovado()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('pedido.pdf', 10, 'application/pdf'),
        ]));
        $this->actingAs($admin)->patch(route('admin.compras.update', $item), $this->dadosValidos(['codigo_fornecedor' => 'X']));

        $this->assertSame('pedido.pdf', $item->refresh()->pedido_compra_nome);
    }

    public function test_rejeita_anexo_que_nao_e_pdf_nem_imagem(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->actingAs($this->admin())->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        ]))->assertSessionHasErrors('pedido_compra');
    }

    public function test_data_coleta_nao_e_mais_editavel_pela_tela_de_dados_da_compra(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['data_coleta' => '2026-09-15']);

        $this->actingAs($this->admin())
            ->patch(route('admin.compras.update', $item), $this->dadosValidos(['data_coleta' => '2026-09-22']));

        $this->assertSame('2026-09-15', $item->refresh()->data_coleta->format('Y-m-d'));
    }

    public function test_download_sem_anexo_da_404(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->actingAs($this->admin())->get(route('admin.compras.pedido', $item))->assertNotFound();
    }

    public function test_vendedor_dono_baixa_o_pedido_de_compra_registrado_pelo_admin(): void
    {
        Storage::fake('local');
        $vendedor = User::factory()->create();
        $item = PurchaseRequest::factory()->aprovado()->create(['user_id' => $vendedor->id]);

        $this->actingAs($this->admin())->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('pedido.pdf', 10, 'application/pdf'),
        ]));

        $response = $this->actingAs($vendedor)
            ->get(route('admin.compras.pedido', $item))
            ->assertOk();

        $this->assertStringContainsString('inline', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('pedido.pdf', $response->headers->get('content-disposition'));
    }

    public function test_outro_vendedor_nao_baixa_pedido_de_compra_alheio(): void
    {
        Storage::fake('local');
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $item = PurchaseRequest::factory()->aprovado()->create(['user_id' => $dono->id]);

        $this->actingAs($this->admin())->patch(route('admin.compras.update', $item), $this->dadosValidos([
            'pedido_compra' => UploadedFile::fake()->create('pedido.pdf', 10, 'application/pdf'),
        ]));

        $this->actingAs($outro)->get(route('admin.compras.pedido', $item))->assertForbidden();
    }

    public function test_compras_feitas_lista_so_quem_tem_dados_da_compra(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Item Sem Dados']);
        PurchaseRequest::factory()->aprovado()->create([
            'product_name'   => 'Item Com Dados',
            'data_compra'    => '2026-09-20',
            'preco_unitario' => 10,
        ]);
        PurchaseRequest::factory()->create(['product_name' => 'Item Pendente']);

        $this->actingAs($this->admin())
            ->get(route('admin.compras.feitas'))
            ->assertOk()
            ->assertSee('Item Com Dados')
            ->assertDontSee('Item Sem Dados')
            ->assertDontSee('Item Pendente');
    }
}
