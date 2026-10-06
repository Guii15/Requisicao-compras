<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PainelFinanceiro;
use App\Services\SaldoFornecedores;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A empresa que fez a compra vai para o Financeiro: aparece em cada compra, dá para filtrar por empresa e os saldos
 * seguem as mesmas regras dentro de cada empresa.
 */
class FinanceiroEmpresaTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(string $fornecedor, float $valor, ?string $empresa, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => $fornecedor, 'quantity' => 1, 'empresa' => $empresa,
            'data_compra' => '2026-10-02', 'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Item ' . $fornecedor . ' ' . ($empresa ?? 'sem'),
        ], $attrs));
    }

    private function pagar(PurchaseRequest $c, float $valor): void
    {
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => $valor, 'forma' => 'parcelado', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-03', 'user_id' => $this->fin->id]);
    }

    private function cenario(): void
    {
        $this->compra('Joyce', 1000, 'Binário');
        $this->compra('Joyce', 500, 'Oasis Comércio');
        $this->compra('Kabum', 200, null);
    }

    private function abrir(string $rota, array $q = [], array $params = [])
    {
        return $this->actingAs($this->fin)->get(route($rota, $params + $q))->assertOk();
    }

    // ---------- serviço ----------

    public function test_linhas_trazem_a_empresa_e_a_chave(): void
    {
        $this->cenario();

        $linhas = app(SaldoFornecedores::class)->linhas()->keyBy(fn ($l) => $l['empresa_chave']);

        $this->assertSame('Binário', $linhas['binario']['empresa']);
        $this->assertSame('Oasis Comércio', $linhas['oasis comercio']['empresa']);
        $this->assertNull($linhas['_sem']['empresa']);
    }

    public function test_empresas_somam_o_saldo_de_cada_uma(): void
    {
        $this->cenario();
        $this->pagar(PurchaseRequest::where('empresa', 'Binário')->first(), 300);

        $s = app(SaldoFornecedores::class);
        $empresas = $s->empresas($s->linhas())->keyBy('chave');

        $this->assertSame(700.0, $empresas['binario']['saldo']);
        $this->assertSame(500.0, $empresas['oasis comercio']['saldo']);
        $this->assertSame(200.0, $empresas['_sem']['saldo']);
        $this->assertSame('Não informada', $empresas['_sem']['nome']);
    }

    public function test_grafias_diferentes_da_mesma_empresa_viram_uma(): void
    {
        $this->compra('Joyce', 100, 'Binário');
        $this->compra('Joyce', 100, 'BINARIO');
        $this->compra('Joyce', 100, 'binário ');

        $s = app(SaldoFornecedores::class);
        $empresas = $s->empresas($s->linhas());

        $this->assertCount(1, $empresas);
        $this->assertSame(300.0, $empresas->first()['saldo']);
        $this->assertSame(3, $empresas->first()['compras']);
    }

    public function test_somente_empresa_filtra_e_sem_filtro_devolve_tudo(): void
    {
        $this->cenario();
        $s = app(SaldoFornecedores::class);
        $todas = $s->linhas();

        $this->assertCount(3, $s->somenteEmpresa($todas, null));
        $this->assertCount(3, $s->somenteEmpresa($todas, ''));
        $this->assertCount(1, $s->somenteEmpresa($todas, 'binario'));
        $this->assertCount(1, $s->somenteEmpresa($todas, '_sem'));
        $this->assertCount(0, $s->somenteEmpresa($todas, 'nao-existe'));
    }

    // ---------- listas ----------

    public function test_aguardando_mostra_a_empresa_de_cada_compra(): void
    {
        $this->cenario();

        $this->abrir('financeiro.aguardando')->assertSee('Binário')->assertSee('Oasis Comércio');
    }

    public function test_aguardando_filtra_por_empresa(): void
    {
        $this->cenario();

        $html = $this->abrir('financeiro.aguardando', ['empresa' => 'oasis comercio'])->getContent();

        $this->assertStringContainsString('Item Joyce Oasis Comércio', $html);
        $this->assertStringNotContainsString('Item Joyce Binário', $html);
        $this->assertStringNotContainsString('Item Kabum sem', $html);
    }

    public function test_filtro_das_sem_empresa(): void
    {
        $this->cenario();

        $html = $this->abrir('financeiro.aguardando', ['empresa' => '_sem'])->getContent();

        $this->assertStringContainsString('Item Kabum sem', $html);
        $this->assertStringNotContainsString('Item Joyce Binário', $html);
    }

    public function test_empresa_desconhecida_mostra_lista_vazia_sem_erro(): void
    {
        $this->cenario();

        $this->abrir('financeiro.aguardando', ['empresa' => 'xyz'])->assertSee('Nada aguardando pagamento');
    }

    public function test_pagos_filtra_por_empresa(): void
    {
        $a = $this->compra('Joyce', 100, 'Binário');
        $b = $this->compra('Joyce', 100, 'Oasis Comércio');
        $this->pagar($a, 100);
        $this->pagar($b, 100);

        $html = $this->abrir('financeiro.pagos', ['empresa' => 'binario'])->getContent();

        $this->assertStringContainsString('Item Joyce Binário', $html);
        $this->assertStringNotContainsString('Item Joyce Oasis', $html);
    }

    // ---------- fornecedores ----------

    public function test_fornecedor_comprado_por_duas_empresas_soma_sem_filtro_e_separa_com_filtro(): void
    {
        $this->cenario();

        $todos = $this->abrir('financeiro.fornecedores')->getContent();
        $this->assertStringContainsString('R$ 1.500,00', $todos);   // Joyce: 1000 + 500

        $soBinario = $this->abrir('financeiro.fornecedores', ['empresa' => 'binario'])->getContent();
        $this->assertStringContainsString('R$ 1.000,00', $soBinario);
        $this->assertStringNotContainsString('Kabum', $soBinario);
    }

    public function test_pagina_do_fornecedor_respeita_o_filtro_de_empresa(): void
    {
        $this->cenario();

        $html = $this->abrir('financeiro.fornecedor', ['empresa' => 'oasis comercio'], ['chave' => 'JOYCE'])->getContent();

        $this->assertStringContainsString('Item Joyce Oasis Comércio', $html);
        $this->assertStringNotContainsString('Item Joyce Binário', $html);
    }

    public function test_fornecedor_sem_compras_na_empresa_filtrada_volta_para_a_lista_com_aviso(): void
    {
        $this->cenario();

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', ['chave' => 'KABUM', 'empresa' => 'binario']))
            ->assertRedirect(route('financeiro.fornecedores', ['empresa' => 'binario']))
            ->assertSessionHas('aviso');
    }

    public function test_fornecedor_que_nao_existe_continua_dando_404(): void
    {
        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', ['chave' => 'NAO EXISTE', 'empresa' => 'binario']))->assertNotFound();
    }

    // ---------- painel ----------

    public function test_painel_com_filtro_so_conta_a_empresa(): void
    {
        $this->cenario();

        $d = app(PainelFinanceiro::class)->dados(CarbonImmutable::parse('2026-10-05', 'America/Sao_Paulo'), 'binario');

        $this->assertSame(1000.0, $d['saldo']);
        $this->assertSame(1000.0, $d['comprado']);
        $this->assertSame(1, $d['aguardando']['compras']);
    }

    public function test_painel_sem_filtro_traz_o_saldo_por_empresa(): void
    {
        $this->cenario();

        $d = app(PainelFinanceiro::class)->dados(CarbonImmutable::parse('2026-10-05', 'America/Sao_Paulo'));

        $this->assertSame(1700.0, $d['saldo']);
        $this->assertCount(3, $d['empresas']);
    }

    public function test_painel_mostra_o_bloco_saldo_por_empresa_quando_ha_mais_de_uma(): void
    {
        $this->cenario();

        $this->abrir('financeiro.index')->assertSee('Saldo devedor por empresa')->assertSee('Oasis Comércio')->assertSee('Não informada');
    }

    public function test_painel_nao_mostra_o_bloco_quando_so_ha_uma_empresa(): void
    {
        $this->compra('Joyce', 100, 'Binário');

        $this->abrir('financeiro.index')->assertDontSee('Saldo devedor por empresa');
    }

    public function test_painel_com_filtro_mostra_qual_empresa_esta_filtrada(): void
    {
        $this->cenario();

        $this->abrir('financeiro.index', ['empresa' => 'oasis comercio'])->assertSee('R$ 500,00')->assertSee('Oasis Comércio');
    }

    // ---------- filtro na tela ----------

    public function test_filtro_de_empresa_aparece_quando_ha_empresa_informada(): void
    {
        $this->cenario();

        foreach (['financeiro.index', 'financeiro.aguardando', 'financeiro.pagos', 'financeiro.fornecedores'] as $rota) {
            $this->abrir($rota)->assertSee('name="empresa"', false)->assertSee('Todas as empresas');
        }
    }

    public function test_filtro_nao_aparece_quando_nenhuma_compra_tem_empresa(): void
    {
        $this->compra('Joyce', 100, null);

        $this->abrir('financeiro.aguardando')->assertDontSee('Todas as empresas');
    }

    public function test_opcoes_do_filtro_sem_repetir_empresa(): void
    {
        $this->compra('Joyce', 100, 'Binário');
        $this->compra('Kabum', 100, 'BINARIO');
        $this->compra('Kabum', 100, 'Oasis Comércio');

        $html = $this->abrir('financeiro.aguardando')->getContent();

        $this->assertSame(1, substr_count($html, 'value="binario"'));
        $this->assertSame(1, substr_count($html, 'value="oasis comercio"'));
    }

    public function test_abas_e_links_mantem_o_filtro_de_empresa(): void
    {
        $this->cenario();

        $html = $this->abrir('financeiro.aguardando', ['empresa' => 'binario'])->getContent();

        $this->assertStringContainsString(route('financeiro.pagos', ['empresa' => 'binario']), $html);
        $this->assertStringContainsString(route('financeiro.index', ['empresa' => 'binario']), $html);
        $this->assertStringContainsString(route('financeiro.fornecedor', ['chave' => 'JOYCE', 'empresa' => 'binario']), $html);
    }

    public function test_pagar_pela_lista_filtrada_volta_para_a_lista_filtrada(): void
    {
        $c = $this->compra('Joyce', 100, 'Binário');
        $url = route('financeiro.aguardando', ['empresa' => 'binario']);

        $this->actingAs($this->fin)->from($url)
            ->post(route('financeiro.pagar', $c), ['forma' => 'a_vista', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-05'])
            ->assertRedirect($url);
    }

    public function test_pagamentos_e_vencidos_seguem_as_regras_dentro_da_empresa(): void
    {
        $a = $this->compra('Joyce', 1000, 'Binário', ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-09-01']);
        $this->compra('Joyce', 1000, 'Oasis Comércio', ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-09-01']);
        $this->pagar($a, 400);

        $d = app(PainelFinanceiro::class)->dados(CarbonImmutable::parse('2026-10-05', 'America/Sao_Paulo'), 'binario');

        $this->assertSame(600.0, $d['saldo']);
        $this->assertSame(['compras' => 1, 'valor' => 600.0], $d['vencido']);
    }
}
