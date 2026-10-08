<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\SaldoFornecedores;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Condição negociada na compra (à vista ou parcelado em N vezes) e vencimento: o comprador registra,
 * o Financeiro vê e o sistema calcula o próximo vencimento e o que já venceu.
 */
class CondicaoPagamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $comprador;
    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comprador = User::factory()->create(['is_admin' => true]);
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function dados(array $extra = []): array
    {
        return array_merge([
            'data_compra' => '2026-09-20', 'preco_unitario' => '100,00', 'supplier' => 'Joyce',
            'condicao_pagamento' => 'a_vista',
        ], $extra);
    }

    private function salvar(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->comprador)->patch(route('admin.compras.update', $item), $this->dados($extra));
    }

    private function compra(float $valor, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'supplier' => 'Joyce', 'quantity' => 1, 'data_compra' => '2026-09-01',
            'preco_unitario' => $valor, 'valor' => $valor,
        ], $attrs));
    }

    private function situacao(PurchaseRequest $c, string $hoje = '2026-10-15'): array
    {
        return app(SaldoFornecedores::class)->situacao($c->load('pagamentos'), CarbonImmutable::parse($hoje, 'America/Sao_Paulo'));
    }

    private function pagar(PurchaseRequest $c, float $valor): void
    {
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => $valor, 'forma' => 'parcelado', 'data_pagamento' => '2026-10-01', 'user_id' => $this->fin->id]);
    }

    // ---- registrar a condição na compra ----

    public function test_dados_da_compra_salva_a_vista_com_vencimento(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-10-20', 'parcelas' => '5'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('a_vista', $item->condicao_pagamento);
        $this->assertNull($item->parcelas);
        $this->assertSame('2026-10-20', $item->primeiro_vencimento->format('Y-m-d'));
    }

    public function test_dados_da_compra_salva_parcelado_com_numero_de_parcelas(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['condicao_pagamento' => 'parcelado', 'parcelas' => '3', 'primeiro_vencimento' => '2026-10-10'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame('parcelado', $item->condicao_pagamento);
        $this->assertSame(3, $item->parcelas);
    }

    public function test_condicao_e_obrigatoria_nos_dados_da_compra(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['condicao_pagamento' => ''])->assertSessionHasErrors('condicao_pagamento');
        $this->salvar($item, ['condicao_pagamento' => 'cheque'])->assertSessionHasErrors('condicao_pagamento');
    }

    public function test_parcelado_exige_de_duas_a_trinta_e_seis_parcelas(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['condicao_pagamento' => 'parcelado', 'parcelas' => ''])->assertSessionHasErrors('parcelas');
        $this->salvar($item, ['condicao_pagamento' => 'parcelado', 'parcelas' => '1'])->assertSessionHasErrors('parcelas');
        $this->salvar($item, ['condicao_pagamento' => 'parcelado', 'parcelas' => '37'])->assertSessionHasErrors('parcelas');
        $this->salvar($item, ['condicao_pagamento' => 'parcelado', 'parcelas' => 'x'])->assertSessionHasErrors('parcelas');
    }

    public function test_vencimento_invalido_e_recusado(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['primeiro_vencimento' => 'ontem'])->assertSessionHasErrors('primeiro_vencimento');
    }

    public function test_formulario_de_dados_da_compra_mostra_os_campos(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        // A tela separada saiu: o formulário é a janela "Dados da compra" de Compras Feitas.
        $this->actingAs($this->comprador)->get(route('admin.compras.feitas', ['abrir' => $item->id]))
            ->assertOk()
            ->assertSee('id="janela-compra"', false)
            ->assertSee('name="condicao_pagamento"', false)
            ->assertSee('name="parcelas"', false)
            ->assertSee('name="primeiro_vencimento"', false);
    }

    public function test_quadro_atualizar_requisicao_tambem_guarda_a_condicao_sem_ser_obrigatoria(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->comprador)->patch(route('admin.requests.update', $item), ['status' => 'aprovado', 'supplier' => 'Fornecedor Teste', 'condicao_pagamento' => 'parcelado', 'parcelas' => '4', 'primeiro_vencimento' => '2026-11-05'])
            ->assertSessionHasNoErrors();
        $this->assertSame(4, $item->fresh()->parcelas);

        $outro = PurchaseRequest::factory()->create(['status' => 'pendente']);
        $this->actingAs($this->comprador)->patch(route('admin.requests.update', $outro), ['status' => 'rejeitado'])->assertSessionHasNoErrors();
        $this->assertNull($outro->fresh()->condicao_pagamento);
    }

    // ---- cálculo de vencimento ----

    public function test_a_vista_mostra_o_rotulo_e_o_vencimento(): void
    {
        $c = $this->compra(1000, ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-10-20']);

        $s = $this->situacao($c);

        $this->assertSame('À vista', $s['condicao']);
        $this->assertSame('2026-10-20', $s['proximo_vencimento']->format('Y-m-d'));
        $this->assertSame(5, $s['dias_ate_vencimento']);
        $this->assertFalse($s['vencida']);
        $this->assertSame(0.0, $s['vencido']);
    }

    public function test_a_vista_vencida_conta_o_valor_todo_como_vencido(): void
    {
        $c = $this->compra(1000, ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-10-10']);

        $s = $this->situacao($c);

        $this->assertTrue($s['vencida']);
        $this->assertSame(1000.0, $s['vencido']);
    }

    public function test_parcelado_calcula_parcelas_vencidas_e_proximo_vencimento(): void
    {
        // 3 parcelas de 1.000, a 1ª em 10/09; hoje é 15/10 => vencidas 10/09 e 10/10.
        $c = $this->compra(3000, ['condicao_pagamento' => 'parcelado', 'parcelas' => 3, 'primeiro_vencimento' => '2026-09-10']);

        $s = $this->situacao($c);
        $this->assertSame('Parcelado em 3x', $s['condicao']);
        $this->assertSame(1000.0, $s['valor_parcela']);
        $this->assertSame(2000.0, $s['vencido']);
        $this->assertSame('2026-09-10', $s['proximo_vencimento']->format('Y-m-d'));

        $this->pagar($c, 1000);
        $s = $this->situacao($c->fresh());
        $this->assertSame(1000.0, $s['vencido']);
        $this->assertSame('2026-10-10', $s['proximo_vencimento']->format('Y-m-d'));

        $this->pagar($c, 1000);
        $s = $this->situacao($c->fresh());
        $this->assertSame(0.0, $s['vencido']);
        $this->assertFalse($s['vencida']);
        $this->assertSame('2026-11-10', $s['proximo_vencimento']->format('Y-m-d'));
    }

    public function test_pagamento_maior_que_uma_parcela_adianta_as_seguintes(): void
    {
        $c = $this->compra(3000, ['condicao_pagamento' => 'parcelado', 'parcelas' => 3, 'primeiro_vencimento' => '2026-09-10']);
        $this->pagar($c, 2500);

        $s = $this->situacao($c);

        $this->assertSame(0.0, $s['vencido']);
        $this->assertSame('2026-11-10', $s['proximo_vencimento']->format('Y-m-d'));
        $this->assertSame(500.0, $s['parcela_sugerida']);
    }

    public function test_sem_vencimento_nao_ha_vencido_nem_proximo(): void
    {
        $c = $this->compra(1000, ['condicao_pagamento' => 'parcelado', 'parcelas' => 2, 'primeiro_vencimento' => null]);

        $s = $this->situacao($c);

        $this->assertNull($s['proximo_vencimento']);
        $this->assertFalse($s['vencida']);
        $this->assertSame(0.0, $s['vencido']);
    }

    public function test_compra_antiga_sem_condicao_continua_funcionando(): void
    {
        $c = $this->compra(1000);

        $s = $this->situacao($c);

        $this->assertNull($s['condicao']);
        $this->assertNull($s['proximo_vencimento']);
        $this->assertSame(1000.0, $s['aberto']);
    }

    public function test_quitada_nao_tem_proximo_vencimento(): void
    {
        $c = $this->compra(1000, ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-10-10']);
        $this->pagar($c, 1000);

        $s = $this->situacao($c);

        $this->assertNull($s['proximo_vencimento']);
        $this->assertFalse($s['vencida']);
    }

    public function test_ultima_parcela_fecha_o_total_sem_sobra_de_centavos(): void
    {
        // 1.000 em 3x => 333,33 + 333,33 + 333,34
        $c = $this->compra(1000, ['condicao_pagamento' => 'parcelado', 'parcelas' => 3, 'primeiro_vencimento' => '2026-08-01']);

        $s = $this->situacao($c);

        $this->assertSame(333.33, $s['valor_parcela']);
        $this->assertSame(1000.0, $s['vencido']); // as 3 já venceram: devido = total
    }

    // ---- telas ----

    public function test_aguardando_mostra_condicao_e_vencimento_com_aviso_de_vencida(): void
    {
        CarbonImmutable::setTestNow('2026-10-15');
        $this->compra(3000, ['product_name' => 'Item Vencido', 'condicao_pagamento' => 'parcelado', 'parcelas' => 3, 'primeiro_vencimento' => '2026-09-10']);
        $this->compra(500, ['product_name' => 'Item Em Dia', 'condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-10-30']);

        $this->actingAs($this->fin)->get(route('financeiro.aguardando'))
            ->assertOk()
            ->assertSee('Parcelado em 3x')
            ->assertSee('À vista')
            ->assertSee('10/09/2026')
            ->assertSee('Vencida')
            ->assertSee('30/10/2026');

        CarbonImmutable::setTestNow();
    }

    public function test_painel_mostra_quanto_ja_venceu(): void
    {
        $hoje = CarbonImmutable::parse('2026-10-15', 'America/Sao_Paulo');
        $this->compra(3000, ['condicao_pagamento' => 'parcelado', 'parcelas' => 3, 'primeiro_vencimento' => '2026-09-10']);
        $this->compra(500, ['condicao_pagamento' => 'a_vista', 'primeiro_vencimento' => '2026-10-30']);

        $d = app(\App\Services\PainelFinanceiro::class)->dados($hoje);

        $this->assertSame(['compras' => 1, 'valor' => 2000.0], $d['vencido']);
    }
}
