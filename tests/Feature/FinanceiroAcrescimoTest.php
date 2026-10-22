<?php

namespace Tests\Feature;

use App\Models\AcrescimoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\SaldoFornecedores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acréscimo: o que a compra custou a mais para a empresa além do pago ao fornecedor (impostos, frete...).
 * Soma no custo real da compra, mas não aumenta a dívida com o fornecedor.
 */
class FinanceiroAcrescimoTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => 'Joyce', 'quantity' => 1, 'data_compra' => '2026-09-20',
            'preco_unitario' => 5000, 'valor' => 5000, 'product_name' => 'Servidor',
        ], $attrs));
    }

    private function acrescentar(PurchaseRequest $c, array $dados = [])
    {
        return $this->actingAs($this->fin)->post(route('financeiro.acrescentar', $c), array_merge([
            'valor' => '1.000,00', 'motivo' => 'Impostos (DIFAL)',
        ], $dados));
    }

    public function test_lanca_acrescimo_com_valor_e_motivo(): void
    {
        $c = $this->compra();

        $this->acrescentar($c)->assertSessionHasNoErrors()->assertSessionHas('success');

        $a = AcrescimoCompra::first();
        $this->assertSame($c->id, $a->purchase_request_id);
        $this->assertEquals(1000, $a->valor);
        $this->assertSame('Impostos (DIFAL)', $a->motivo);
        $this->assertSame($this->fin->id, $a->user_id);
    }

    public function test_acrescimo_soma_no_custo_real_mas_nao_na_divida_com_o_fornecedor(): void
    {
        $c = $this->compra();
        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-10-01', 'meio' => 'pix', 'banco' => 'Itaú']);
        $this->acrescentar($c);

        $saldos = app(SaldoFornecedores::class);
        $s = $saldos->situacao($c->fresh()->load('pagamentos'));

        $this->assertEquals(5000, $s['custo']);
        $this->assertEquals(1000, $s['acrescimo']);
        $this->assertEquals(6000, $s['custo_total']);
        $this->assertEquals(0, $s['aberto']);
        $this->assertSame('Pago', $s['situacao']);
        $this->assertEquals(0, $saldos->resumo()->first()['saldo']);
    }

    public function test_valor_e_motivo_sao_obrigatorios(): void
    {
        $c = $this->compra();

        $this->acrescentar($c, ['valor' => ''])->assertSessionHasErrors('valor');
        $this->acrescentar($c, ['valor' => '0'])->assertSessionHasErrors('valor');
        $this->acrescentar($c, ['valor' => 'abc'])->assertSessionHasErrors('valor');
        $this->acrescentar($c, ['motivo' => ''])->assertSessionHasErrors('motivo');
        $this->acrescentar($c, ['motivo' => str_repeat('a', 201)])->assertSessionHasErrors('motivo');

        $this->assertSame(0, AcrescimoCompra::count());
    }

    public function test_compra_fora_do_financeiro_nao_recebe_acrescimo(): void
    {
        $c = $this->compra(['data_compra' => null]);

        $this->acrescentar($c)->assertSessionHas('aviso');

        $this->assertSame(0, AcrescimoCompra::count());
    }

    public function test_remove_acrescimo(): void
    {
        $c = $this->compra();
        $this->acrescentar($c);

        $this->actingAs($this->fin)->delete(route('financeiro.removerAcrescimo', AcrescimoCompra::first()))->assertSessionHas('success');

        $this->assertSame(0, AcrescimoCompra::count());
    }

    public function test_quem_nao_e_do_financeiro_nao_lanca(): void
    {
        $c = $this->compra();
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->actingAs($vendedor)->post(route('financeiro.acrescentar', $c), ['valor' => '10', 'motivo' => 'x']);

        $this->assertSame(0, AcrescimoCompra::count());
    }

    public function test_tela_do_fornecedor_mostra_acrescimo_e_custo_real(): void
    {
        $c = $this->compra();
        $this->acrescentar($c);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertSee('Impostos (DIFAL)')
            ->assertSee('R$ 6.000,00')
            ->assertSee(route('financeiro.acrescentar', $c), false);
    }

    public function test_compra_ja_paga_tambem_tem_botao_de_acrescimo(): void
    {
        $c = $this->compra();
        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'data_pagamento' => '2026-10-01', 'meio' => 'pix', 'banco' => 'Itaú']);

        $this->actingAs($this->fin)->get(route('financeiro.pagos'))
            ->assertSee(route('financeiro.acrescentar', $c), false);
    }

    public function test_painel_mostra_total_de_acrescimos(): void
    {
        $this->acrescentar($this->compra(), ['valor' => '1.234,56']);

        $this->actingAs($this->fin)->get(route('financeiro.index'))->assertSee('Acréscimos')->assertSee('R$ 1.234,56');
    }
}
