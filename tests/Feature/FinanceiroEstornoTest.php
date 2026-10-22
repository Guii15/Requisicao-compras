<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\SaldoFornecedores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pagamento errado não é apagado: é estornado, com motivo, quem fez e quando, e o valor volta para o saldo. */
class FinanceiroEstornoTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compraPaga(float $valor = 1000): PurchaseRequest
    {
        $c = PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'supplier' => 'Joyce', 'quantity' => 1, 'data_compra' => '2026-09-20',
            'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Memória DDR5',
        ]);

        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), [
            'forma' => 'a_vista', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-09-25',
        ])->assertSessionHasNoErrors();

        return $c;
    }

    private function saldoJoyce(): float
    {
        return app(SaldoFornecedores::class)->resumo()->firstWhere('chave', 'JOYCE')['saldo'];
    }

    public function test_estorno_mantem_o_pagamento_guardado_e_devolve_o_saldo(): void
    {
        $this->compraPaga(1000);
        $pg = PagamentoCompra::first();
        $this->assertSame(0.0, $this->saldoJoyce());

        $this->actingAs($this->fin)->post(route('financeiro.estornar', $pg), ['motivo' => 'Valor digitado errado'])
            ->assertSessionHas('success');

        $this->assertSame(1000.0, $this->saldoJoyce());
        $pg->refresh();
        $this->assertSame(1, PagamentoCompra::count());
        $this->assertNotNull($pg->estornado_em);
        $this->assertSame($this->fin->id, $pg->estornado_por);
        $this->assertSame('Valor digitado errado', $pg->motivo_estorno);
    }

    public function test_motivo_e_obrigatorio(): void
    {
        $this->compraPaga();
        $pg = PagamentoCompra::first();

        $this->actingAs($this->fin)->post(route('financeiro.estornar', $pg), ['motivo' => ' '])->assertSessionHasErrors('motivo');

        $this->assertNull($pg->fresh()->estornado_em);
    }

    public function test_nao_estorna_duas_vezes(): void
    {
        $this->compraPaga();
        $pg = PagamentoCompra::first();
        $this->actingAs($this->fin)->post(route('financeiro.estornar', $pg), ['motivo' => 'Primeiro']);
        $quando = $pg->fresh()->estornado_em;

        $this->actingAs($this->fin)->post(route('financeiro.estornar', $pg), ['motivo' => 'Segundo'])->assertSessionHas('aviso');

        $this->assertSame('Primeiro', $pg->fresh()->motivo_estorno);
        $this->assertEquals($quando, $pg->fresh()->estornado_em);
    }

    public function test_depois_do_estorno_a_compra_pode_ser_paga_de_novo(): void
    {
        $c = $this->compraPaga(1000);
        $this->actingAs($this->fin)->post(route('financeiro.estornar', PagamentoCompra::first()), ['motivo' => 'Banco errado']);

        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), [
            'forma' => 'a_vista', 'meio' => 'pix', 'banco' => 'Bradesco', 'data_pagamento' => '2026-09-26',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0.0, $this->saldoJoyce());
        $this->assertSame(2, PagamentoCompra::count());
    }

    public function test_tela_do_fornecedor_mostra_o_estornado_com_motivo(): void
    {
        $this->compraPaga(1000);
        $this->actingAs($this->fin)->post(route('financeiro.estornar', PagamentoCompra::first()), ['motivo' => 'Pago em duplicidade']);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()
            ->assertSee('Estornado')
            ->assertSee('Pago em duplicidade');
    }

    public function test_so_o_financeiro_estorna(): void
    {
        $this->compraPaga();
        $pg = PagamentoCompra::first();
        $comprador = User::factory()->create(['is_admin' => true]);

        $this->actingAs($comprador)->post(route('financeiro.estornar', $pg), ['motivo' => 'x'])->assertForbidden();

        $this->assertNull($pg->fresh()->estornado_em);
    }

    public function test_nao_existe_mais_rota_que_apaga_pagamento(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('financeiro.desfazer'));
    }
}
