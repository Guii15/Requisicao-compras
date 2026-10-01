<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PainelFinanceiro;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Painel (dashboard) do Financeiro: números, gráficos e o que aparece na tela. */
class FinanceiroPainelTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(string $fornecedor, float $valor, string $data, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => $fornecedor, 'quantity' => 1,
            'data_compra' => $data, 'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Memória DDR5',
        ], $attrs));
    }

    private function pagar(PurchaseRequest $c, float $valor, string $data, string $forma = 'parcelado'): PagamentoCompra
    {
        return PagamentoCompra::create([
            'purchase_request_id' => $c->id, 'valor' => $valor, 'forma' => $forma,
            'data_pagamento' => $data, 'user_id' => $this->fin->id,
        ]);
    }

    private function dados(string $hoje = '2026-10-15'): array
    {
        return app(PainelFinanceiro::class)->dados(CarbonImmutable::parse($hoje, 'America/Sao_Paulo'));
    }

    public function test_indicadores_principais(): void
    {
        $joyce = $this->compra('Joyce', 50000, '2026-10-05');
        $this->compra('Kabum', 1000, '2026-09-10');
        $this->pagar($joyce, 20000, '2026-10-10');

        $d = $this->dados();

        $this->assertSame(31000.0, $d['saldo']);
        $this->assertSame(51000.0, $d['comprado']);
        $this->assertSame(20000.0, $d['pago']);
        $this->assertSame(50000.0, $d['comprado_mes']);
        $this->assertSame(20000.0, $d['pago_mes']);
        $this->assertSame(2, $d['aguardando']['compras']);
        $this->assertSame(2, $d['aguardando']['fornecedores']);
        $this->assertSame(39.2, $d['percentual_pago']);
    }

    public function test_compra_quitada_vai_para_pagas_e_nao_para_aguardando(): void
    {
        $c = $this->compra('Joyce', 100, '2026-10-05');
        $this->pagar($c, 100, '2026-10-06', 'a_vista');
        $this->compra('Joyce', 50, '2026-10-07');

        $d = $this->dados();

        $this->assertSame(1, $d['aguardando']['compras']);
        $this->assertSame(1, $d['pagas']);
    }

    public function test_ultimos_seis_meses_somam_comprado_por_data_da_compra_e_pago_por_data_do_pagamento(): void
    {
        $a = $this->compra('Joyce', 1000, '2026-04-20'); // fora da janela de 6 meses (mai a out)
        $b = $this->compra('Joyce', 2000, '2026-06-02');
        $c = $this->compra('Joyce', 500, '2026-10-01');
        $this->pagar($b, 700, '2026-08-15');
        $this->pagar($b, 300, '2026-10-03');
        $this->pagar($a, 100, '2026-04-25'); // fora da janela

        $meses = collect($this->dados()['meses']);

        $this->assertSame(['2026-05', '2026-06', '2026-07', '2026-08', '2026-09', '2026-10'], $meses->pluck('chave')->all());
        $this->assertSame(['mai', 'jun', 'jul', 'ago', 'set', 'out'], $meses->pluck('rotulo')->all());
        $this->assertSame([0.0, 2000.0, 0.0, 0.0, 0.0, 500.0], $meses->pluck('comprado')->all());
        $this->assertSame([0.0, 0.0, 0.0, 700.0, 0.0, 300.0], $meses->pluck('pago')->all());
    }

    public function test_maiores_saldos_ordenados_sem_quitados_e_no_maximo_oito(): void
    {
        foreach (range(1, 10) as $i) {
            $this->compra('Fornecedor ' . chr(64 + $i), $i * 100, '2026-10-01');
        }
        $quitada = $this->compra('Zeta', 999999, '2026-10-01');
        $this->pagar($quitada, 999999, '2026-10-02', 'a_vista');

        $top = collect($this->dados()['top']);

        $this->assertCount(8, $top);
        $this->assertSame('Fornecedor J', $top->first()['nome']);
        $this->assertSame(1000.0, $top->first()['saldo']);
        $this->assertNotContains('Zeta', $top->pluck('nome')->all());
    }

    public function test_idade_do_que_esta_em_aberto_em_faixas(): void
    {
        $this->compra('A', 100, '2026-10-12'); // 3 dias
        $this->compra('A', 200, '2026-10-01'); // 14 dias
        $this->compra('B', 300, '2026-09-20'); // 25 dias
        $this->compra('B', 400, '2026-08-01'); // 75 dias
        $parcial = $this->compra('C', 1000, '2026-10-14'); // 1 dia, com 400 já pagos
        $this->pagar($parcial, 400, '2026-10-14');
        $quitada = $this->compra('C', 5000, '2026-08-01');
        $this->pagar($quitada, 5000, '2026-08-02', 'a_vista');

        $faixas = collect($this->dados()['idade'])->keyBy('rotulo');

        $this->assertSame(['compras' => 2, 'valor' => 700.0], ['compras' => $faixas['Até 7 dias']['compras'], 'valor' => $faixas['Até 7 dias']['valor']]);
        $this->assertSame(200.0, $faixas['8 a 15 dias']['valor']);
        $this->assertSame(300.0, $faixas['16 a 30 dias']['valor']);
        $this->assertSame(400.0, $faixas['Mais de 30 dias']['valor']);
    }

    public function test_ultimos_pagamentos_do_mais_recente_para_o_mais_antigo_limitados_a_seis(): void
    {
        $c = $this->compra('Joyce', 100000, '2026-10-01');
        foreach (range(1, 8) as $dia) {
            $this->pagar($c, $dia * 10, sprintf('2026-10-%02d', $dia));
        }

        $ultimos = collect($this->dados()['ultimos']);

        $this->assertCount(6, $ultimos);
        $this->assertSame(80.0, $ultimos->first()['valor']);
        $this->assertSame('Joyce', $ultimos->first()['fornecedor']);
    }

    public function test_sem_dados_tudo_zerado(): void
    {
        $d = $this->dados();

        $this->assertSame(0.0, $d['saldo']);
        $this->assertSame(0.0, $d['percentual_pago']);
        $this->assertSame([], $d['top']);
        $this->assertSame([], $d['ultimos']);
        $this->assertCount(6, $d['meses']);
    }

    public function test_tela_do_painel_mostra_indicadores_graficos_e_abas(): void
    {
        $joyce = $this->compra('Joyce', 50000, now('America/Sao_Paulo')->format('Y-m-d'));
        $this->compra('Kabum', 1000, now('America/Sao_Paulo')->subDays(40)->format('Y-m-d'));
        $this->pagar($joyce, 20000, now('America/Sao_Paulo')->format('Y-m-d'));

        $this->actingAs($this->fin)->get(route('financeiro.index'))
            ->assertOk()
            ->assertSee('Saldo devedor')
            ->assertSee('R$ 31.000,00')
            ->assertSee('Comprado × pago por mês')
            ->assertSee('Maiores saldos devedores')
            ->assertSee('Idade do que está em aberto')
            ->assertSee('Últimos pagamentos')
            ->assertSee('Painel')->assertSee('Aguardando')->assertSee('Pagos')->assertSee('Fornecedores');
    }

    public function test_tela_do_painel_sem_dados_mostra_aviso_amigavel(): void
    {
        $this->actingAs($this->fin)->get(route('financeiro.index'))
            ->assertOk()
            ->assertSee('Ainda não há compras com fornecedor registradas');
    }
}
