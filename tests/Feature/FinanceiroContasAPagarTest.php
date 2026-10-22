<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Aba Aguardando como contas a pagar (estilo Bling): ordenada pelo vencimento, com atalhos
 * Vencidas / Hoje / Próximos 7 dias / Até o fim do mês / Sem vencimento e período escolhido.
 */
class FinanceiroContasAPagarTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        // Quinta-feira, 22/10/2026.
        $this->travelTo(CarbonImmutable::parse('2026-10-22 10:00', 'America/Sao_Paulo'));
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function conta(string $produto, float $valor, ?string $vencimento, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => 'Joyce', 'quantity' => 1, 'data_compra' => '2026-09-01',
            'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => $produto,
            'condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => $vencimento,
        ], $attrs));
    }

    private function cenario(): void
    {
        $this->conta('Conta Vencida', 100, '2026-10-15');
        $this->conta('Conta Hoje', 200, '2026-10-22');
        $this->conta('Conta Semana', 300, '2026-10-27');
        $this->conta('Conta Mes', 400, '2026-10-31');
        $this->conta('Conta Novembro', 500, '2026-11-20');
        $this->conta('Conta Sem Data', 600, null);
    }

    private function ver(array $filtros = [])
    {
        return $this->actingAs($this->fin)->get(route('financeiro.aguardando', $filtros))->assertOk();
    }

    public function test_ordena_pelo_vencimento_e_sem_vencimento_vai_para_o_fim(): void
    {
        $this->cenario();

        $this->ver()->assertSeeInOrder(['Conta Vencida', 'Conta Hoje', 'Conta Semana', 'Conta Mes', 'Conta Novembro', 'Conta Sem Data']);
    }

    public function test_atalhos_de_vencimento_filtram(): void
    {
        $this->cenario();

        $this->ver(['vence' => 'vencidas'])->assertSee('Conta Vencida')->assertDontSee('Conta Hoje')->assertDontSee('Conta Sem Data');
        $this->ver(['vence' => 'hoje'])->assertSee('Conta Hoje')->assertDontSee('Conta Vencida')->assertDontSee('Conta Semana');
        $this->ver(['vence' => 'semana'])->assertSee('Conta Hoje')->assertSee('Conta Semana')->assertDontSee('Conta Mes')->assertDontSee('Conta Vencida');
        $this->ver(['vence' => 'mes'])->assertSee('Conta Hoje')->assertSee('Conta Mes')->assertDontSee('Conta Novembro');
        $this->ver(['vence' => 'sem'])->assertSee('Conta Sem Data')->assertDontSee('Conta Hoje');
    }

    public function test_periodo_de_vencimento_escolhido(): void
    {
        $this->cenario();

        $this->ver(['de' => '2026-10-27', 'ate' => '2026-11-30'])
            ->assertSee('Conta Semana')->assertSee('Conta Novembro')
            ->assertDontSee('Conta Hoje')->assertDontSee('Conta Sem Data');
    }

    public function test_atalhos_mostram_quantas_e_quanto(): void
    {
        $this->cenario();

        $this->ver()
            ->assertSee('Vencidas · 1 · R$ 100,00')
            ->assertSee('Vence hoje · 1 · R$ 200,00')
            ->assertSee('Próximos 7 dias · 2 · R$ 500,00')
            ->assertSee('Sem vencimento · 1 · R$ 600,00');
    }

    public function test_total_acompanha_o_filtro(): void
    {
        $this->cenario();

        $this->ver(['vence' => 'semana'])->assertSee('R$ 500,00')->assertSee('2 compras');
    }

    public function test_conta_parcialmente_paga_entra_pelo_que_falta(): void
    {
        $c = $this->conta('Conta Parcial', 1000, '2026-10-15');
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 400, 'forma' => 'parcelado', 'data_pagamento' => '2026-10-01', 'user_id' => $this->fin->id]);

        $this->ver()->assertSee('Vencidas · 1 · R$ 600,00');
    }

    public function test_filtro_continua_ao_buscar_e_ao_paginar(): void
    {
        $this->cenario();

        $this->ver(['vence' => 'semana'])->assertSee('name="vence" value="semana"', false);
    }
}
