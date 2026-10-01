<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Abas Aguardando (o que falta pagar) e Pagos (compras quitadas), e a lista de fornecedores. */
class FinanceiroListasTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(string $fornecedor, float $valor, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => $fornecedor, 'quantity' => 1,
            'data_compra' => '2026-09-20', 'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Memória DDR5',
        ], $attrs));
    }

    private function pagar(PurchaseRequest $c, float $valor, string $data = '2026-09-25'): void
    {
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => $valor, 'forma' => 'parcelado', 'data_pagamento' => $data, 'user_id' => $this->fin->id]);
    }

    public function test_aguardando_lista_so_o_que_falta_pagar_da_mais_antiga_para_a_mais_nova(): void
    {
        $this->compra('Joyce', 100, ['product_name' => 'Item Novo', 'data_compra' => '2026-10-10']);
        $this->compra('Kabum', 100, ['product_name' => 'Item Antigo', 'data_compra' => '2026-08-01']);
        $parcial = $this->compra('Joyce', 1000, ['product_name' => 'Item Parcial', 'data_compra' => '2026-09-01']);
        $this->pagar($parcial, 400);
        $quitada = $this->compra('Joyce', 777, ['product_name' => 'Item Quitado']);
        $this->pagar($quitada, 777);

        $this->actingAs($this->fin)->get(route('financeiro.aguardando'))
            ->assertOk()
            ->assertSeeInOrder(['Item Antigo', 'Item Parcial', 'Item Novo'])
            ->assertDontSee('Item Quitado')
            ->assertSee('R$ 600,00');
    }

    public function test_pagos_lista_so_as_quitadas_com_a_data_do_ultimo_pagamento(): void
    {
        $quitada = $this->compra('Joyce', 500, ['product_name' => 'Item Quitado']);
        $this->pagar($quitada, 200, '2026-09-25');
        $this->pagar($quitada, 300, '2026-10-03');
        $this->compra('Joyce', 100, ['product_name' => 'Item Em Aberto']);

        $this->actingAs($this->fin)->get(route('financeiro.pagos'))
            ->assertOk()
            ->assertSee('Item Quitado')
            ->assertSee('03/10/2026')
            ->assertDontSee('Item Em Aberto');
    }

    public function test_busca_filtra_por_fornecedor_produto_ou_comprador(): void
    {
        $this->compra('Joyce', 10, ['product_name' => 'Memória', 'requester_name' => 'Isaac']);
        $this->compra('Kabum', 10, ['product_name' => 'Teclado', 'requester_name' => 'Ian']);

        $this->actingAs($this->fin);

        $this->get(route('financeiro.aguardando', ['q' => 'kabum']))->assertSee('Teclado')->assertDontSee('Memória');
        $this->get(route('financeiro.aguardando', ['q' => 'teclado']))->assertSee('Teclado')->assertDontSee('Memória');
        $this->get(route('financeiro.aguardando', ['q' => 'isaac']))->assertSee('Memória')->assertDontSee('Teclado');
    }

    public function test_lista_e_paginada(): void
    {
        foreach (range(1, 30) as $i) {
            $this->compra('Joyce', 10, ['product_name' => 'Produto ' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'data_compra' => '2026-08-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
        }

        $this->actingAs($this->fin);

        $this->get(route('financeiro.aguardando'))->assertSee('Produto 01')->assertDontSee('Produto 30');
        $this->get(route('financeiro.aguardando', ['page' => 2]))->assertSee('Produto 30')->assertDontSee('Produto 01');
    }

    public function test_abas_mostram_a_quantidade_aguardando(): void
    {
        $this->compra('Joyce', 10);
        $this->compra('Kabum', 10);

        $this->actingAs($this->fin)->get(route('financeiro.pagos'))->assertSee('Aguardando')->assertSee('>2</span>', false);
    }

    public function test_pagar_pela_lista_volta_para_a_lista(): void
    {
        $c = $this->compra('Joyce', 100);

        $this->actingAs($this->fin)->from(route('financeiro.aguardando'))
            ->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-01'])
            ->assertRedirect(route('financeiro.aguardando'))
            ->assertSessionHas('success');
    }

    public function test_lista_de_fornecedores_tem_saldo_e_total_geral(): void
    {
        $this->compra('Joyce', 1000000);
        $this->compra('Kabum', 500);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedores'))
            ->assertOk()
            ->assertSeeInOrder(['Joyce', 'R$ 1.000.000,00', 'Kabum'])
            ->assertSee('R$ 1.000.500,00');
    }

    public function test_novas_telas_so_para_o_financeiro(): void
    {
        foreach (['financeiro.aguardando', 'financeiro.pagos', 'financeiro.fornecedores'] as $rota) {
            $this->actingAs(User::factory()->create(['role' => null, 'is_admin' => false]))->get(route($rota))->assertForbidden();
            $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route($rota))->assertForbidden();
            $this->actingAs($this->fin)->get(route($rota))->assertOk();
        }
    }
}
