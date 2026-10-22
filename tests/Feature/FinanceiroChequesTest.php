<?php

namespace Tests\Feature;

use App\Models\Cheque;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pagamento em cheque é desdobrado em folhas de valores iguais, cada uma abaixo de R$ 5.000,00,
 * numeradas a partir da primeira folha informada. Folha usada não se repete no mesmo banco.
 */
class FinanceiroChequesTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(float $valor, string $fornecedor = 'Via Comercio'): PurchaseRequest
    {
        return PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'supplier' => $fornecedor, 'quantity' => 1, 'data_compra' => '2026-09-20',
            'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Notebook',
        ]);
    }

    private function pagarEmCheque(PurchaseRequest $c, array $extra = [])
    {
        return $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), array_merge([
            'forma' => 'a_vista', 'meio' => 'cheque', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22',
            'cheque_primeira_folha' => '101',
        ], $extra));
    }

    public function test_desdobra_o_pagamento_em_folhas_iguais_abaixo_de_5_mil(): void
    {
        $c = $this->compra(23000);

        $this->pagarEmCheque($c)->assertSessionHasNoErrors()->assertSessionHas('success');

        $pg = PagamentoCompra::sole();
        $this->assertSame('23000.00', $pg->valor);
        $cheques = Cheque::orderBy('numero')->get();
        $this->assertSame([101, 102, 103, 104, 105], $cheques->pluck('numero')->all());
        $this->assertSame(['4600.00'], $cheques->pluck('valor')->unique()->values()->all());
        $this->assertTrue($cheques->every(fn ($ch) => $ch->lote === $pg->lote && $ch->banco === 'Itaú' && $ch->situacao === 'emitido'));
        $this->assertSame('2026-10-22', $cheques->first()->bom_para->format('Y-m-d'));
        $this->assertSame($this->fin->id, $cheques->first()->user_id);
    }

    public function test_parcelado_desdobra_so_o_valor_pago_agora(): void
    {
        $c = $this->compra(30000);

        $this->pagarEmCheque($c, ['forma' => 'parcelado', 'valor' => '10.000,00'])->assertSessionHasNoErrors();

        $this->assertSame(['333334', '333333', '333333'], Cheque::orderBy('numero')->get()->map(fn ($ch) => (string) round($ch->valor * 100))->all());
    }

    public function test_aceita_o_numero_com_zeros_na_frente_como_vem_na_folha(): void
    {
        $this->pagarEmCheque($this->compra(1000), ['cheque_primeira_folha' => '000101'])->assertSessionHasNoErrors();

        $this->assertSame(101, Cheque::sole()->numero);
    }

    public function test_bom_para_pode_ser_outra_data(): void
    {
        $this->pagarEmCheque($this->compra(1000), ['cheque_bom_para' => '2026-11-15'])->assertSessionHasNoErrors();

        $this->assertSame('2026-11-15', Cheque::sole()->bom_para->format('Y-m-d'));
    }

    public function test_numero_da_primeira_folha_e_obrigatorio_no_cheque(): void
    {
        $this->pagarEmCheque($this->compra(1000), ['cheque_primeira_folha' => ''])->assertSessionHasErrors('cheque_primeira_folha');
        $this->pagarEmCheque($this->compra(1000), ['cheque_primeira_folha' => 'abc'])->assertSessionHasErrors('cheque_primeira_folha');

        $this->assertSame(0, PagamentoCompra::count());
        $this->assertSame(0, Cheque::count());
    }

    public function test_folha_ja_usada_no_mesmo_banco_bloqueia_e_nada_e_salvo(): void
    {
        $this->pagarEmCheque($this->compra(9000))->assertSessionHasNoErrors(); // folhas 101 e 102

        $this->pagarEmCheque($this->compra(9000), ['cheque_primeira_folha' => '102'])->assertSessionHasErrors('cheque_primeira_folha');

        $this->assertSame(1, PagamentoCompra::count());
        $this->assertSame(2, Cheque::count());
    }

    public function test_mesmo_numero_em_outro_banco_pode(): void
    {
        $this->pagarEmCheque($this->compra(1000))->assertSessionHasNoErrors();

        $this->pagarEmCheque($this->compra(1000), ['banco' => 'Bradesco'])->assertSessionHasNoErrors();

        $this->assertSame(2, Cheque::where('numero', 101)->count());
    }

    public function test_outras_formas_nao_geram_cheque(): void
    {
        $this->pagarEmCheque($this->compra(9000), ['meio' => 'pix'])->assertSessionHasNoErrors();

        $this->assertSame(0, Cheque::count());
    }

    public function test_estorno_cancela_as_folhas_mas_o_numero_continua_usado(): void
    {
        $this->pagarEmCheque($this->compra(9000))->assertSessionHasNoErrors();

        $this->actingAs($this->fin)->post(route('financeiro.estornar', PagamentoCompra::sole()), ['motivo' => 'Cheque rasurado']);

        $this->assertSame(['cancelado'], Cheque::pluck('situacao')->unique()->values()->all());
        $this->pagarEmCheque($this->compra(1000), ['cheque_primeira_folha' => '101'])->assertSessionHasErrors('cheque_primeira_folha');
    }

    public function test_tela_do_fornecedor_mostra_as_folhas_do_pagamento(): void
    {
        $this->pagarEmCheque($this->compra(9000))->assertSessionHasNoErrors();

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'VIA COMERCIO'))
            ->assertOk()
            ->assertSee('000101')
            ->assertSee('000102')
            ->assertSee('R$ 4.500,00');
    }

    public function test_aba_cheques_lista_as_folhas_com_fornecedor_e_filtros(): void
    {
        $this->pagarEmCheque($this->compra(9000, 'Via Comercio'))->assertSessionHasNoErrors();
        $this->pagarEmCheque($this->compra(1000, 'Kabum'), ['banco' => 'Bradesco', 'cheque_primeira_folha' => '777'])->assertSessionHasNoErrors();

        $this->actingAs($this->fin)->get(route('financeiro.cheques'))
            ->assertOk()
            ->assertSee('000101')->assertSee('000777')
            ->assertSee('Via Comercio')->assertSee('Kabum');

        $this->actingAs($this->fin)->get(route('financeiro.cheques', ['banco' => 'Bradesco']))
            ->assertOk()->assertSee('000777')->assertDontSee('000101');

        $this->actingAs($this->fin)->get(route('financeiro.cheques', ['q' => '101']))
            ->assertOk()->assertSee('000101')->assertDontSee('000777');

        $this->actingAs($this->fin)->post(route('financeiro.estornar', PagamentoCompra::first()), ['motivo' => 'Erro']);
        $this->actingAs($this->fin)->get(route('financeiro.cheques', ['situacao' => 'cancelado']))
            ->assertOk()->assertSee('000101')->assertDontSee('000777');
    }

    public function test_so_o_financeiro_ve_a_aba_cheques(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('financeiro.cheques'))->assertForbidden();
    }
}
