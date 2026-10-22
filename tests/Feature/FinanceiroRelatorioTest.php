<?php

namespace Tests\Feature;

use App\Models\AcrescimoCompra;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\RelatorioFinanceiro;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

/** Aba Relatório do Financeiro: compras e pagamentos de um período (semana, mês ou datas escolhidas), para imprimir ou baixar em Excel. */
class FinanceiroRelatorioTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        // Quinta-feira, 15/10/2026: a semana vai de 12/10 (segunda) a 18/10 (domingo).
        $this->travelTo(CarbonImmutable::parse('2026-10-15 10:00', 'America/Sao_Paulo'));
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false, 'name' => 'Ana Financeiro']);
    }

    private function compra(string $fornecedor, string $data, float $valor, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => $fornecedor, 'quantity' => 1, 'data_compra' => $data,
            'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Produto ' . $fornecedor,
        ], $attrs));
    }

    private function pagamento(PurchaseRequest $c, string $data, float $valor, string $meio = 'pix', ?string $banco = 'Itaú'): PagamentoCompra
    {
        return PagamentoCompra::create([
            'purchase_request_id' => $c->id, 'valor' => $valor, 'forma' => 'parcelado', 'meio' => $meio,
            'banco' => $banco, 'data_pagamento' => $data, 'user_id' => $this->fin->id,
        ]);
    }

    /** Joyce: setembro, paga em outubro. Kabum: outubro, metade paga. Pichau: outubro, nada pago, com acréscimo. */
    private function cenario(): void
    {
        $joyce = $this->compra('Joyce', '2026-09-20', 5000);
        $this->pagamento($joyce, '2026-10-13', 5000, 'pix', 'Itaú');

        $kabum = $this->compra('Kabum', '2026-10-02', 2000);
        $this->pagamento($kabum, '2026-10-05', 1000, 'boleto', 'Sicoob');

        $pichau = $this->compra('Pichau', '2026-10-14', 800);
        AcrescimoCompra::create(['purchase_request_id' => $pichau->id, 'valor' => 120, 'motivo' => 'Frete']);

        $this->pagamento($kabum, '2026-09-30', 100, 'pix', 'Itaú'); // fora de outubro
    }

    private function relatorio(string $de, string $ate): array
    {
        return app(RelatorioFinanceiro::class)->gerar(
            CarbonImmutable::parse($de, 'America/Sao_Paulo'),
            CarbonImmutable::parse($ate, 'America/Sao_Paulo'),
        );
    }

    public function test_resumo_do_mes(): void
    {
        $this->cenario();

        $r = $this->relatorio('2026-10-01', '2026-10-31');

        $this->assertEquals(6000, $r['resumo']['pago']);          // 5000 + 1000 (os 100 de setembro ficam fora)
        $this->assertSame(2, $r['resumo']['pagamentos']);
        $this->assertSame(2, $r['resumo']['compras']);             // Kabum e Pichau
        $this->assertEquals(2800, $r['resumo']['comprado']);
        $this->assertEquals(120, $r['resumo']['acrescimos']);
        $this->assertEquals(2920, $r['resumo']['custo_real']);
        $this->assertEquals(1700, $r['resumo']['saldo_hoje']);     // Kabum 900 + Pichau 800
    }

    public function test_totais_por_forma_e_por_banco(): void
    {
        $this->cenario();

        $r = $this->relatorio('2026-10-01', '2026-10-31');

        $this->assertSame([['nome' => 'PIX', 'pagamentos' => 1, 'valor' => 5000.0], ['nome' => 'Boleto', 'pagamentos' => 1, 'valor' => 1000.0]], $r['por_meio']);
        $this->assertSame([['nome' => 'Itaú', 'pagamentos' => 1, 'valor' => 5000.0], ['nome' => 'Sicoob', 'pagamentos' => 1, 'valor' => 1000.0]], $r['por_banco']);
    }

    public function test_por_fornecedor_mostra_comprado_e_pago_no_periodo_e_saldo_de_hoje(): void
    {
        $this->cenario();

        $porFornecedor = collect($this->relatorio('2026-10-01', '2026-10-31')['por_fornecedor'])->keyBy('nome');

        $this->assertEquals(['comprado' => 0, 'pago' => 5000, 'saldo' => 0], collect($porFornecedor['Joyce'])->only(['comprado', 'pago', 'saldo'])->all());
        $this->assertEquals(['comprado' => 2000, 'pago' => 1000, 'saldo' => 900], collect($porFornecedor['Kabum'])->only(['comprado', 'pago', 'saldo'])->all());
        $this->assertEquals(['comprado' => 800, 'pago' => 0, 'saldo' => 800], collect($porFornecedor['Pichau'])->only(['comprado', 'pago', 'saldo'])->all());
    }

    public function test_semana_atual_e_semana_passada(): void
    {
        $this->cenario();

        $this->actingAs($this->fin)->get(route('financeiro.relatorio', ['periodo' => 'semana']))
            ->assertOk()->assertSee('12/10/2026 a 18/10/2026')->assertSee('Pichau');

        $semana = $this->relatorio('2026-10-12', '2026-10-18');
        $this->assertSame(['Pichau'], array_column($semana['compras'], 'fornecedor'));
        $this->assertSame(['Joyce'], array_column($semana['pagamentos'], 'fornecedor'));

        $this->actingAs($this->fin)->get(route('financeiro.relatorio', ['periodo' => 'semana_passada']))
            ->assertOk()->assertSee('05/10/2026 a 11/10/2026')->assertSee('Sicoob');
    }

    public function test_mes_e_padrao_e_mes_passado_funciona(): void
    {
        $this->actingAs($this->fin)->get(route('financeiro.relatorio'))->assertOk()->assertSee('01/10/2026 a 31/10/2026');
        $this->actingAs($this->fin)->get(route('financeiro.relatorio', ['periodo' => 'mes_passado']))->assertSee('01/09/2026 a 30/09/2026');
    }

    public function test_periodo_escolhido_e_datas_invertidas(): void
    {
        $this->actingAs($this->fin)->get(route('financeiro.relatorio', ['periodo' => 'personalizado', 'de' => '2026-10-10', 'ate' => '2026-10-03']))
            ->assertOk()->assertSee('03/10/2026 a 10/10/2026');
    }

    public function test_tela_tem_imprimir_e_planilha_com_o_mesmo_periodo(): void
    {
        $this->cenario();

        $this->actingAs($this->fin)->get(route('financeiro.relatorio', ['periodo' => 'semana']))
            ->assertSee('window.print()', false)
            ->assertSee(e(route('financeiro.relatorio.planilha', ['de' => '2026-10-12', 'ate' => '2026-10-18'])), false)
            ->assertSee('Ana Financeiro');
    }

    public function test_na_impressao_texto_comprido_quebra_dentro_da_coluna(): void
    {
        $c = $this->compra('Joyce', '2026-10-02', 500, ['product_name' => 'TESTE' . str_repeat('asdfg', 40)]);
        $this->pagamento($c, '2026-10-05', 500);

        $this->actingAs($this->fin)->get(route('financeiro.relatorio', ['periodo' => 'mes']))
            ->assertOk()
            ->assertSee('<td class="rel-texto"', false)
            ->assertSee('.rel-tabela td.rel-texto { overflow-wrap: anywhere; }', false)
            ->assertSee('@media print and (orientation: portrait)', false)
            ->assertSee('@media screen and (max-width: 768px)', false);
    }

    public function test_aba_relatorio_aparece_no_financeiro(): void
    {
        $this->actingAs($this->fin)->get(route('financeiro.index'))->assertSee(route('financeiro.relatorio'), false);
    }

    public function test_quem_nao_e_do_financeiro_nao_ve(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->actingAs($vendedor)->get(route('financeiro.relatorio'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('financeiro.relatorio.planilha'))->assertForbidden();
    }

    public function test_planilha_excel_tem_as_abas_e_os_dados(): void
    {
        $this->cenario();

        $resp = $this->actingAs($this->fin)->get(route('financeiro.relatorio.planilha', ['de' => '2026-10-01', 'ate' => '2026-10-31']));

        $resp->assertOk();
        $this->assertStringContainsString('relatorio-financeiro-2026-10-01-a-2026-10-31.xlsx', $resp->headers->get('content-disposition'));

        $arquivo = tempnam(sys_get_temp_dir(), 'rel');
        file_put_contents($arquivo, $resp->streamedContent() ?: $resp->getFile()->getContent());
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($arquivo) === true);

        $workbook = $zip->getFromName('xl/workbook.xml');
        foreach (['Resumo', 'Pagamentos', 'Compras', 'Fornecedores', 'Em aberto'] as $aba) {
            $this->assertStringContainsString('name="' . $aba . '"', $workbook);
        }
        $this->assertStringContainsString('Sicoob', $zip->getFromName('xl/worksheets/sheet2.xml'));
        $this->assertStringContainsString('Frete', $zip->getFromName('xl/worksheets/sheet3.xml'));
        $zip->close();
        unlink($arquivo);
    }
}
