<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Item aprovado sem data/preço da compra aparece na Conferência mas não em Compras Feitas (só em Compras).
 * A tela de Compras Feitas avisa isso, com atalho para registrar.
 */
class AvisoSemDadosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    public function test_compras_feitas_avisa_quantas_aprovadas_ainda_nao_tem_dados(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['data_compra' => null, 'preco_unitario' => null]);
        PurchaseRequest::factory()->aprovado()->create(['data_compra' => '2026-09-20', 'preco_unitario' => null]);
        PurchaseRequest::factory()->aprovado()->create(['data_compra' => '2026-09-20', 'preco_unitario' => 10]);

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))
            ->assertOk()
            ->assertSee('2 compras aprovadas ainda não têm')
            ->assertSee(route('admin.compras.index', ['situacao' => 'sem_dados']), false);
    }

    public function test_usa_o_singular_quando_e_uma_so(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['data_compra' => null, 'preco_unitario' => null]);

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))->assertSee('1 compra aprovada ainda não tem');
    }

    public function test_sem_pendencia_de_dados_nao_mostra_o_aviso(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['data_compra' => '2026-09-20', 'preco_unitario' => 10]);
        PurchaseRequest::factory()->create(['status' => 'pendente', 'data_compra' => null, 'preco_unitario' => null]);

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))->assertDontSee('ainda não tem')->assertDontSee('ainda não têm');
    }

    public function test_o_atalho_do_aviso_lista_so_as_que_faltam_dados(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Item Sem Dados', 'data_compra' => null, 'preco_unitario' => null]);
        PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Item Completo', 'data_compra' => '2026-09-20', 'preco_unitario' => 10]);

        $this->actingAs($this->admin)->get(route('admin.compras.index', ['situacao' => 'sem_dados']))
            ->assertSee('Item Sem Dados')
            ->assertSee('Registrar compra')
            ->assertDontSee('Item Completo');
    }
}
