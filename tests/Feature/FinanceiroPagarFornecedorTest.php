<?php

namespace Tests\Feature;

use App\Models\Cheque;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\SaldoFornecedores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Pagar fornecedor": um valor só (total ou parcial) que quita várias compras do fornecedor,
 * do vencimento mais antigo para o mais novo. A última compra alcançada pode ficar parcial.
 */
class FinanceiroPagarFornecedorTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(float $valor, string $data, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => 'Joyce', 'quantity' => 1, 'data_compra' => $data,
            'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Item ' . $data,
        ], $attrs));
    }

    private function pagarFornecedor(array $extra = [], string $chave = 'JOYCE')
    {
        return $this->actingAs($this->fin)->post(route('financeiro.pagarFornecedor', $chave), array_merge([
            'valor' => '1.000,00', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22',
        ], $extra));
    }

    private function pagoDe(PurchaseRequest $c): float
    {
        return round((float) PagamentoCompra::where('purchase_request_id', $c->id)->whereNull('estornado_em')->sum('valor'), 2);
    }

    public function test_quita_da_mais_antiga_para_a_mais_nova_e_a_ultima_fica_parcial(): void
    {
        $nova = $this->compra(500, '2026-10-10');
        $antiga = $this->compra(300, '2026-09-01');
        $meio = $this->compra(400, '2026-09-20');

        $this->pagarFornecedor(['valor' => '1.000,00'])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(300.0, $this->pagoDe($antiga));
        $this->assertSame(400.0, $this->pagoDe($meio));
        $this->assertSame(300.0, $this->pagoDe($nova));
        $this->assertSame(1, PagamentoCompra::distinct()->count('lote'));
        $this->assertSame('a_vista', PagamentoCompra::where('purchase_request_id', $antiga->id)->value('forma'));
        $this->assertSame('parcelado', PagamentoCompra::where('purchase_request_id', $nova->id)->value('forma'));
    }

    public function test_vencimento_mais_antigo_vem_antes_da_data_da_compra(): void
    {
        // Comprada antes, mas vence depois.
        $venceDepois = $this->compra(300, '2026-09-01', ['condicao_pagamento' => 'parcelado', 'parcelas' => 2, 'primeiro_vencimento' => '2026-12-01']);
        $venceAntes = $this->compra(300, '2026-09-10', ['condicao_pagamento' => 'a_vista', 'parcelas' => 1, 'primeiro_vencimento' => '2026-10-01']);

        $this->pagarFornecedor(['valor' => '300,00'])->assertSessionHasNoErrors();

        $this->assertSame(300.0, $this->pagoDe($venceAntes));
        $this->assertSame(0.0, $this->pagoDe($venceDepois));
    }

    public function test_pagamento_parcial_ja_feito_e_respeitado(): void
    {
        $c1 = $this->compra(1000, '2026-09-01');
        $c2 = $this->compra(1000, '2026-09-02');
        PagamentoCompra::create(['purchase_request_id' => $c1->id, 'valor' => 600, 'forma' => 'parcelado', 'data_pagamento' => '2026-09-05', 'user_id' => $this->fin->id]);

        $this->pagarFornecedor(['valor' => '1.400,00'])->assertSessionHasNoErrors();

        $this->assertSame(1000.0, $this->pagoDe($c1));
        $this->assertSame(1000.0, $this->pagoDe($c2));
        $this->assertSame(0.0, app(SaldoFornecedores::class)->resumo()->firstWhere('chave', 'JOYCE')['saldo']);
    }

    public function test_nao_paga_mais_que_o_saldo_do_fornecedor(): void
    {
        $this->compra(500, '2026-09-01');

        $this->pagarFornecedor(['valor' => '500,01'])->assertSessionHasErrors('valor');

        $this->assertSame(0, PagamentoCompra::count());
    }

    public function test_valor_e_obrigatorio(): void
    {
        $this->compra(500, '2026-09-01');

        $this->pagarFornecedor(['valor' => ''])->assertSessionHasErrors('valor');
    }

    public function test_nao_mexe_em_compra_de_outro_fornecedor(): void
    {
        $outra = $this->compra(500, '2026-08-01', ['supplier' => 'Kabum']);
        $this->compra(500, '2026-09-01');

        $this->pagarFornecedor(['valor' => '500,00'])->assertSessionHasNoErrors();

        $this->assertSame(0.0, $this->pagoDe($outra));
    }

    public function test_com_filtro_de_empresa_paga_so_as_compras_dela(): void
    {
        $binario = $this->compra(500, '2026-08-01', ['empresa' => 'Binário']);
        $outra = $this->compra(500, '2026-07-01', ['empresa' => 'Outra']);

        $this->actingAs($this->fin)->post(route('financeiro.pagarFornecedor', ['chave' => 'JOYCE', 'empresa' => 'Binário']), [
            'valor' => '500,00', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22',
        ])->assertSessionHasNoErrors();

        $this->assertSame(500.0, $this->pagoDe($binario));
        $this->assertSame(0.0, $this->pagoDe($outra));
    }

    public function test_em_cheque_as_folhas_saem_do_valor_total(): void
    {
        $this->compra(3000, '2026-09-01');
        $this->compra(3000, '2026-09-02');
        $this->compra(3000, '2026-09-03');

        $this->pagarFornecedor(['valor' => '9.000,00', 'meio' => 'cheque', 'cheque_primeira_folha' => '000500'])->assertSessionHasNoErrors();

        $cheques = Cheque::orderBy('numero')->get();
        $this->assertSame([500, 501], $cheques->pluck('numero')->all());
        $this->assertSame(['4500.00'], $cheques->pluck('valor')->unique()->values()->all());
        $this->assertSame(1, $cheques->pluck('lote')->unique()->count());
        $this->assertSame($cheques->first()->lote, PagamentoCompra::first()->lote);
        $this->assertSame(3, $cheques->first()->pagamentos()->count());
    }

    public function test_estornar_uma_parte_estorna_o_pagamento_inteiro_e_cancela_as_folhas(): void
    {
        $c1 = $this->compra(3000, '2026-09-01');
        $c2 = $this->compra(3000, '2026-09-02');
        $this->pagarFornecedor(['valor' => '6.000,00', 'meio' => 'cheque', 'cheque_primeira_folha' => '10'])->assertSessionHasNoErrors();

        $this->actingAs($this->fin)->post(route('financeiro.estornar', PagamentoCompra::where('purchase_request_id', $c2->id)->first()), ['motivo' => 'Cheque devolvido'])
            ->assertSessionHas('success');

        $this->assertSame(0.0, $this->pagoDe($c1));
        $this->assertSame(0.0, $this->pagoDe($c2));
        $this->assertSame(2, PagamentoCompra::whereNotNull('estornado_em')->where('motivo_estorno', 'Cheque devolvido')->count());
        $this->assertSame(['cancelado'], Cheque::pluck('situacao')->unique()->values()->all());
    }

    public function test_tela_do_fornecedor_tem_o_botao_e_mostra_que_o_pagamento_foi_dividido(): void
    {
        $this->compra(300, '2026-09-01');
        $this->compra(300, '2026-09-02');

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()->assertSee('Pagar fornecedor');

        $this->pagarFornecedor(['valor' => '600,00'])->assertSessionHasNoErrors();

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()->assertSee('parte de um pagamento de R$ 600,00');
    }

    public function test_erro_reabre_a_janela_com_o_que_foi_digitado(): void
    {
        $this->compra(500, '2026-09-01');
        $tela = route('financeiro.fornecedor', 'JOYCE');

        $this->actingAs($this->fin)->from($tela)->followingRedirects()->post(route('financeiro.pagarFornecedor', 'JOYCE'), [
            '_compra' => 'forn', 'valor' => '900,00', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22',
        ])
            ->assertOk()
            ->assertSee('maior do que o saldo devedor')
            ->assertSee('id="modal-pagar-forn" data-reabrir="1" style="display:flex;', false)
            ->assertSee('value="900,00"', false);
    }

    public function test_estorno_de_pagamento_antigo_sem_lote_mostra_o_valor_dele(): void
    {
        $c = $this->compra(15000, '2026-09-01');
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 15000, 'forma' => 'a_vista', 'data_pagamento' => '2026-09-05', 'user_id' => $this->fin->id]);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()
            ->assertSee('Estornar R$ 15.000,00?')
            ->assertDontSee('R$ 0,00?');
    }

    public function test_valor_do_pagar_fornecedor_comeca_vazio(): void
    {
        $this->compra(500, '2026-09-01');

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()
            ->assertSee('Usar o saldo todo (R$ 500,00)')
            ->assertDontSee('value="500,00"', false);
    }

    public function test_sem_nada_em_aberto_avisa_e_nao_grava(): void
    {
        $c = $this->compra(300, '2026-09-01');
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 300, 'forma' => 'a_vista', 'data_pagamento' => '2026-09-05', 'user_id' => $this->fin->id]);

        $this->pagarFornecedor(['valor' => '10,00'])->assertSessionHas('aviso');

        $this->assertSame(1, PagamentoCompra::count());
    }

    public function test_so_o_financeiro_paga_fornecedor(): void
    {
        $this->compra(300, '2026-09-01');

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('financeiro.pagarFornecedor', 'JOYCE'), ['valor' => '300,00', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22'])
            ->assertForbidden();
    }
}
