<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contas a pagar por fornecedor: cada compra feita aumenta o saldo devedor do fornecedor e cada
 * pagamento (à vista ou parcelado) dá baixa nele.
 */
class FinanceiroSaldoTest extends TestCase
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
            'data_compra' => '2026-09-20', 'preco_unitario' => $valor, 'valor' => $valor,
            'product_name' => 'Memória DDR5',
        ], $attrs));
    }

    private function resumo(): array
    {
        return app(\App\Services\SaldoFornecedores::class)->resumo()->keyBy('chave')->map(fn ($f) => [
            'comprado' => $f['comprado'], 'pago' => $f['pago'], 'saldo' => $f['saldo'],
        ])->all();
    }

    public function test_compras_do_mesmo_fornecedor_somam_no_saldo_mesmo_com_grafias_diferentes(): void
    {
        $this->compra('Joyce', 50000);
        $this->compra('JOYCE', 20000);
        $this->compra('joyce informatica ltda', 30000, ['supplier' => 'Joyce Informática Ltda.']);
        $this->compra('Kabum', 1000);

        $r = $this->resumo();

        $this->assertSame(['comprado' => 50000.0 + 20000.0, 'pago' => 0.0, 'saldo' => 70000.0], $r['JOYCE']);
        $this->assertSame(30000.0, $r['JOYCE INFORMATICA']['saldo']);
        $this->assertSame(1000.0, $r['KABUM']['saldo']);
    }

    public function test_so_conta_compra_aprovada_e_com_dados_da_compra(): void
    {
        $this->compra('Joyce', 100);
        $this->compra('Joyce', 999, ['status' => 'pendente']);
        $this->compra('Joyce', 999, ['status' => 'rejeitado']);
        $this->compra('Joyce', 999, ['data_compra' => null]);
        $this->compra('Joyce', 999, ['preco_unitario' => null]);
        $this->compra('Joyce', 999, ['tipo_registro' => 'compra_historica']);

        $this->assertSame(100.0, $this->resumo()['JOYCE']['saldo']);
    }

    public function test_valor_da_compra_usa_o_total_digitado_ou_preco_unitario_vezes_quantidade(): void
    {
        $this->compra('Joyce', 0, ['valor' => 500, 'preco_unitario' => 10, 'quantity' => 3]);
        $this->compra('Joyce', 0, ['valor' => null, 'preco_unitario' => 10.5, 'quantity' => 4]);

        $this->assertSame(542.0, $this->resumo()['JOYCE']['comprado']);
    }

    public function test_recebimento_parcial_nao_conta_o_valor_duas_vezes(): void
    {
        // 5 pedidas a R$ 10 (total digitado 50): 3 chegaram e 2 ficaram aguardando.
        $chegou = $this->compra('Joyce', 0, ['valor' => 50, 'preco_unitario' => 10, 'quantity' => 3, 'quantidade_original' => 5]);
        $this->compra('Joyce', 0, [
            'valor' => null, 'preco_unitario' => 10, 'quantity' => 2, 'quantidade_original' => 5,
            'restante_de_id' => $chegou->id, 'grupo_id' => $chegou->grupo_id,
        ]);

        $this->assertSame(50.0, $this->resumo()['JOYCE']['comprado']);
    }

    public function test_fornecedor_vazio_vira_sem_fornecedor(): void
    {
        $this->compra('', 70, ['supplier' => null]);

        $r = $this->resumo();

        $this->assertSame(70.0, $r['-']['saldo']);
    }

    public function test_pagamento_a_vista_quita_a_compra(): void
    {
        $c = $this->compra('Joyce', 50000);

        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-09-25'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(50000.0, (float) PagamentoCompra::first()->valor);
        $this->assertSame('a_vista', PagamentoCompra::first()->forma);
        $this->assertSame($this->fin->id, PagamentoCompra::first()->user_id);
        $this->assertSame(0.0, $this->resumo()['JOYCE']['saldo']);
    }

    public function test_pagamento_parcelado_abate_so_o_valor_informado_e_aceita_formato_brasileiro(): void
    {
        $c = $this->compra('Joyce', 50000);

        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), ['forma' => 'parcelado', 'valor' => '12.500,50', 'data_pagamento' => '2026-09-25'])
            ->assertSessionHasNoErrors();

        $this->assertSame(37499.5, $this->resumo()['JOYCE']['saldo']);
        $this->assertSame(12500.5, $this->resumo()['JOYCE']['pago']);
    }

    public function test_varios_pagamentos_na_mesma_compra_vao_baixando_ate_zerar(): void
    {
        $c = $this->compra('Joyce', 1000);
        $this->actingAs($this->fin);

        $this->post(route('financeiro.pagar', $c), ['forma' => 'parcelado', 'valor' => '400', 'data_pagamento' => '2026-09-25']);
        $this->post(route('financeiro.pagar', $c), ['forma' => 'parcelado', 'valor' => '600', 'data_pagamento' => '2026-09-26']);

        $this->assertSame(0.0, $this->resumo()['JOYCE']['saldo']);
        $this->assertSame(2, PagamentoCompra::count());
    }

    public function test_nao_aceita_pagar_mais_do_que_falta_nem_zero(): void
    {
        $c = $this->compra('Joyce', 1000);
        $this->actingAs($this->fin);

        $this->post(route('financeiro.pagar', $c), ['forma' => 'parcelado', 'valor' => '1000,01', 'data_pagamento' => '2026-09-25'])->assertSessionHasErrors('valor');
        $this->post(route('financeiro.pagar', $c), ['forma' => 'parcelado', 'valor' => '0', 'data_pagamento' => '2026-09-25'])->assertSessionHasErrors('valor');
        $this->post(route('financeiro.pagar', $c), ['forma' => 'parcelado', 'valor' => 'abc', 'data_pagamento' => '2026-09-25'])->assertSessionHasErrors('valor');
        $this->post(route('financeiro.pagar', $c), ['forma' => 'outra', 'valor' => '10', 'data_pagamento' => '2026-09-25'])->assertSessionHasErrors('forma');

        $this->assertSame(0, PagamentoCompra::count());
    }

    public function test_compra_ja_quitada_nao_recebe_novo_pagamento(): void
    {
        $c = $this->compra('Joyce', 1000);
        $this->actingAs($this->fin);
        $this->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-09-25']);

        $this->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-09-26'])->assertSessionHas('aviso');

        $this->assertSame(1, PagamentoCompra::count());
    }

    public function test_so_paga_compra_que_conta_no_financeiro(): void
    {
        $pendente = $this->compra('Joyce', 100, ['status' => 'pendente']);

        $this->actingAs($this->fin)->post(route('financeiro.pagar', $pendente), ['forma' => 'a_vista', 'data_pagamento' => '2026-09-25'])
            ->assertSessionHas('aviso');

        $this->assertSame(0, PagamentoCompra::count());
    }

    public function test_desfazer_pagamento_devolve_o_saldo(): void
    {
        $c = $this->compra('Joyce', 1000);
        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-09-25']);
        $pagamento = PagamentoCompra::first();

        $this->delete(route('financeiro.desfazer', $pagamento))->assertSessionHas('success');

        $this->assertSame(1000.0, $this->resumo()['JOYCE']['saldo']);
        $this->assertSame(0, PagamentoCompra::count());
    }

    public function test_so_o_financeiro_registra_ou_desfaz_pagamento(): void
    {
        $c = $this->compra('Joyce', 1000);
        $pagamento = PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 10, 'forma' => 'parcelado', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);
        $comprador = User::factory()->create(['is_admin' => true]);

        $this->actingAs($comprador)->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-09-25'])->assertForbidden();
        $this->actingAs($comprador)->delete(route('financeiro.desfazer', $pagamento))->assertForbidden();

        $this->assertSame(1, PagamentoCompra::count());
    }

    public function test_tela_lista_fornecedores_com_saldo_e_total_geral(): void
    {
        $this->compra('Joyce', 1000000);
        $this->compra('Kabum', 500);
        $c = $this->compra('Joyce', 50000);
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 50000, 'forma' => 'a_vista', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedores'))
            ->assertOk()
            ->assertSeeInOrder(['Joyce', 'R$ 1.000.000,00', 'Kabum'])
            ->assertSee('R$ 1.000.500,00');
    }

    public function test_tela_do_fornecedor_lista_as_compras_com_situacao(): void
    {
        $aberta = $this->compra('Joyce', 1000, ['product_name' => 'Memória Aberta', 'requester_name' => 'Isaac']);
        $paga = $this->compra('Joyce', 200, ['product_name' => 'Memória Paga']);
        $parcial = $this->compra('Joyce', 300, ['product_name' => 'Memória Parcial']);
        PagamentoCompra::create(['purchase_request_id' => $paga->id, 'valor' => 200, 'forma' => 'a_vista', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);
        PagamentoCompra::create(['purchase_request_id' => $parcial->id, 'valor' => 100, 'forma' => 'parcelado', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()
            ->assertSee('Memória Aberta')->assertSee('Isaac')
            ->assertSee('Em aberto')->assertSee('Parcial')->assertSee('Pago')
            ->assertSee('R$ 1.500,00');
    }

    public function test_fornecedor_inexistente_da_404(): void
    {
        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'NAO EXISTE'))->assertNotFound();
    }

    public function test_nome_mostrado_e_sempre_o_mesmo_e_bem_formatado_quando_as_grafias_empatam(): void
    {
        $this->compra('joyce', 10);
        $this->compra('JOYCE', 10);
        $this->compra('Joyce', 10);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))->assertSee('>Joyce</h1>', false);
        $this->actingAs($this->fin)->get(route('financeiro.fornecedores'))->assertSee('>Joyce</td>', false);
    }

    public function test_nome_usa_a_grafia_mais_frequente(): void
    {
        $this->compra('Joyce Informática', 10);
        $this->compra('Joyce Informática', 10);
        $this->compra('JOYCE INFORMATICA', 10);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE INFORMATICA'))->assertSee('>Joyce Informática</h1>', false);
    }
}
