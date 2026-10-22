<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Setor RMA: perfil próprio (role "rma"), só leitura. Vê o que foi comprado e já chegou
 * (produto, fornecedor, data da compra, quantidade, fotos da conferência e pedido de compra),
 * sem nenhum valor em dinheiro.
 */
class PerfilRmaTest extends TestCase
{
    use RefreshDatabase;

    private User $rma;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rma = User::factory()->create(['role' => 'rma', 'is_admin' => false]);
    }

    /** Compra aprovada com entrada concluída (o que o RMA enxerga). */
    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'product_name' => 'Memória DDR5',
            'supplier' => 'Joyce Informática',
            'quantity' => 12,
            'data_compra' => '2026-09-20',
            'preco_unitario' => 1234.56,
            'valor' => 14814.72,
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => '2026-09-25 10:00:00',
        ], $attrs));
    }

    // ---------- login e perfil ----------

    public function test_pagina_de_login_do_rma_existe_e_aparece_na_escolha_de_perfil(): void
    {
        $this->get(route('login.perfil', 'rma'))->assertOk()->assertSee('RMA');
        $this->get(route('login'))->assertOk()->assertSee(route('login.perfil', 'rma'), false);
    }

    public function test_rma_entra_pelo_login_do_rma_e_cai_na_tela_do_rma(): void
    {
        $this->post(route('login.perfil.store', 'rma'), ['email' => $this->rma->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($this->rma);
        $this->actingAs($this->rma)->get(route('dashboard'))->assertRedirect(route('rma.index'));
    }

    public function test_outros_perfis_nao_entram_pelo_login_do_rma(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->post(route('login.perfil.store', 'rma'), ['email' => $vendedor->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_rma_nao_entra_pelo_login_de_outro_perfil(): void
    {
        foreach (['vendedor', 'conferencia', 'entrada', 'financeiro', 'admin'] as $perfil) {
            $this->post(route('login.perfil.store', $perfil), ['email' => $this->rma->email, 'password' => 'password'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_rma_nao_e_nenhum_outro_perfil(): void
    {
        $this->assertTrue($this->rma->isRma());
        $this->assertFalse($this->rma->isVendedor());
        $this->assertFalse($this->rma->isConferente());
        $this->assertFalse($this->rma->isEntrada());
        $this->assertFalse($this->rma->isFinanceiro());
        $this->assertFalse($this->rma->isAdmin());
    }

    // ---------- acesso ----------

    public function test_tela_rma_so_para_rma_e_admin(): void
    {
        $this->actingAs($this->rma)->get(route('rma.index'))->assertOk();
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('rma.index'))->assertOk();

        foreach ([null, 'conferente', 'entrada', 'financeiro'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_admin' => false]))
                ->get(route('rma.index'))->assertForbidden();
        }
    }

    public function test_visitante_e_mandado_para_o_login(): void
    {
        $this->get(route('rma.index'))->assertRedirect(route('login'));
    }

    public function test_rma_nao_abre_telas_de_outros_perfis(): void
    {
        $item = $this->item();

        foreach ([
            route('requests.index'), route('requests.create') , route('admin.index'), route('admin.compras.feitas'),
            route('pendencias.index'), route('conferencia.index'), route('entrada.index'), route('financeiro.index'),
            route('requests.export', $item),
        ] as $url) {
            $this->actingAs($this->rma)->get($url)->assertForbidden();
        }
    }

    // ---------- conteúdo ----------

    public function test_lista_produto_fornecedor_data_da_compra_e_quantidade(): void
    {
        $this->item();

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertOk()
            ->assertSee('Memória DDR5')
            ->assertSee('Joyce Informática')
            ->assertSee('20/09/2026')
            ->assertSee('12');
    }

    public function test_mostra_a_empresa_que_fez_a_compra(): void
    {
        $this->item(['product_name' => 'Item Da Binario', 'empresa' => 'Binário']);

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertOk()
            ->assertSee('Empresa')
            ->assertSee('Binário');
    }

    public function test_item_sem_empresa_nao_quebra(): void
    {
        $this->item(['product_name' => 'Item Sem Empresa', 'empresa' => null]);

        $this->actingAs($this->rma)->get(route('rma.index'))->assertOk()->assertSee('Item Sem Empresa');
    }

    public function test_busca_tambem_acha_pela_empresa(): void
    {
        $this->item(['product_name' => 'Item A', 'empresa' => 'Binário']);
        $this->item(['product_name' => 'Item B', 'empresa' => 'Mammuth']);

        $this->actingAs($this->rma)->get(route('rma.index', ['q' => 'mammuth']))
            ->assertSee('Item B')->assertDontSee('Item A');
    }

    public function test_so_aparece_item_aprovado_com_entrada_concluida(): void
    {
        $this->item(['product_name' => 'Item Com Entrada']);
        $this->item(['product_name' => 'Item Sem Entrada', 'entrada_concluida_em' => null]);
        $this->item(['product_name' => 'Item Pendente', 'status' => 'pendente']);
        $this->item(['product_name' => 'Item Rejeitado', 'status' => 'rejeitado']);

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertOk()
            ->assertSee('Item Com Entrada')
            ->assertDontSee('Item Sem Entrada')
            ->assertDontSee('Item Pendente')
            ->assertDontSee('Item Rejeitado');
    }

    public function test_nenhum_valor_em_dinheiro_aparece(): void
    {
        $this->item();

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertOk()
            ->assertDontSee('1.234,56')
            ->assertDontSee('14.814,72')
            ->assertDontSee('R$');
    }

    public function test_mostra_as_fotos_da_conferencia_e_o_link_do_pedido_de_compra(): void
    {
        $item = $this->item(['pedido_compra_path' => 'pedidos/p1.pdf', 'pedido_compra_nome' => 'pedido.pdf']);
        ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => 'conferencia/a.jpg', 'nome_original' => 'a.jpg']);
        ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => 'conferencia/b.jpg', 'nome_original' => 'b.jpg']);

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertOk()
            ->assertSee(Storage::url('conferencia/a.jpg'), false)
            ->assertSee(Storage::url('conferencia/b.jpg'), false)
            ->assertSee(route('admin.compras.pedido', $item), false);
    }

    public function test_item_sem_foto_nem_pedido_nao_quebra_e_nao_mostra_link(): void
    {
        $item = $this->item(['pedido_compra_path' => null]);

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertOk()
            ->assertSee('Memória DDR5')
            ->assertDontSee(route('admin.compras.pedido', $item), false);
    }

    public function test_pedido_de_compra_pode_ser_baixado_pelo_rma(): void
    {
        Storage::fake('local');
        $item = $this->item(['pedido_compra_path' => 'pedidos/p1.pdf', 'pedido_compra_nome' => 'pedido.pdf']);
        Storage::disk('local')->put('pedidos/p1.pdf', 'conteudo');

        $this->actingAs($this->rma)->get(route('admin.compras.pedido', $item))->assertOk();
    }

    // ---------- busca e filtros ----------

    public function test_busca_por_produto_e_por_fornecedor(): void
    {
        $this->item(['product_name' => 'Notebook Latitude', 'supplier' => 'Dell Brasil']);
        $this->item(['product_name' => 'Mouse sem fio', 'supplier' => 'Kabum']);

        $this->actingAs($this->rma)->get(route('rma.index', ['q' => 'latitude']))
            ->assertSee('Notebook Latitude')->assertDontSee('Mouse sem fio');

        $this->actingAs($this->rma)->get(route('rma.index', ['q' => 'kabum']))
            ->assertSee('Mouse sem fio')->assertDontSee('Notebook Latitude');
    }

    public function test_filtro_por_data_da_compra(): void
    {
        $this->item(['product_name' => 'Compra de Agosto', 'data_compra' => '2026-08-10']);
        $this->item(['product_name' => 'Compra de Setembro', 'data_compra' => '2026-09-10']);
        $this->item(['product_name' => 'Compra de Outubro', 'data_compra' => '2026-10-02']);

        $this->actingAs($this->rma)->get(route('rma.index', ['data_inicial' => '2026-09-01', 'data_final' => '2026-09-30']))
            ->assertSee('Compra de Setembro')
            ->assertDontSee('Compra de Agosto')
            ->assertDontSee('Compra de Outubro');
    }

    public function test_mais_recente_primeiro_e_paginacao(): void
    {
        $this->item(['product_name' => 'Item Antigo', 'data_compra' => '2026-01-05']);
        $this->item(['product_name' => 'Item Recente', 'data_compra' => '2026-10-05']);

        $this->actingAs($this->rma)->get(route('rma.index'))->assertSeeInOrder(['Item Recente', 'Item Antigo']);

        PurchaseRequest::factory()->count(30)->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'entrada_concluida_em' => '2026-09-25 10:00:00',
            'data_compra' => '2026-09-01',
        ]);

        $this->actingAs($this->rma)->get(route('rma.index'))->assertOk()->assertSee('page=2', false);
    }

    // ---------- admin cadastra o perfil ----------

    public function test_admin_cadastra_usuario_com_perfil_rma(): void
    {
        config(['admin.super_admin_email' => 'super.teste@example.com']);
        $super = User::factory()->create(['is_admin' => true, 'email' => 'super.teste@example.com']);

        $this->actingAs($super)->post(route('admin.users.store'), [
            'name' => 'Pessoa RMA', 'email' => 'rma.novo@example.com', 'password' => 'senha-forte-123',
            'password_confirmation' => 'senha-forte-123', 'perfil' => 'rma',
        ])->assertSessionHasNoErrors();

        $novo = User::where('email', 'rma.novo@example.com')->first();
        $this->assertSame('rma', $novo->role);
        $this->assertFalse((bool) $novo->is_admin);
    }

    public function test_admin_troca_perfil_de_usuario_para_rma(): void
    {
        config(['admin.super_admin_email' => 'super.teste@example.com']);
        $super = User::factory()->create(['is_admin' => true, 'email' => 'super.teste@example.com']);
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->actingAs($super)->patch(route('admin.users.updateRole', $vendedor), ['perfil' => 'rma'])
            ->assertSessionHasNoErrors();

        $this->assertSame('rma', $vendedor->fresh()->role);
    }

    // ---------- menu ----------

    public function test_menu_do_rma_tem_so_o_link_rma(): void
    {
        $this->item();

        $this->actingAs($this->rma)->get(route('rma.index'))
            ->assertSee(route('rma.index'), false)
            ->assertDontSee(route('financeiro.index'), false)
            ->assertDontSee(route('entrada.index'), false)
            ->assertDontSee(route('requests.index'), false);
    }
}
