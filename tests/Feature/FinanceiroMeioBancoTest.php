<?php

namespace Tests\Feature;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Cada pagamento registra a forma (PIX, boleto, transferência, cartão, dinheiro, cheque) e o banco de onde saiu. */
class FinanceiroMeioBancoTest extends TestCase
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
            'preco_unitario' => 1000, 'valor' => 1000, 'product_name' => 'Memória DDR5',
        ], $attrs));
    }

    private function pagar(PurchaseRequest $c, array $extra = [])
    {
        return $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), array_merge([
            'forma' => 'a_vista', 'data_pagamento' => '2026-10-01', 'meio' => 'pix', 'banco' => 'Itaú',
        ], $extra));
    }

    public function test_grava_a_forma_de_pagamento_e_o_banco(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['meio' => 'boleto', 'banco' => 'Banco do Brasil'])->assertSessionHasNoErrors();

        $pg = PagamentoCompra::first();
        $this->assertSame('boleto', $pg->meio);
        $this->assertSame('Banco do Brasil', $pg->banco);
    }

    public function test_aceita_todas_as_formas_inclusive_cheque(): void
    {
        foreach (['pix', 'boleto', 'transferencia', 'cartao', 'dinheiro', 'cheque'] as $i => $meio) {
            $c = $this->compra();
            $this->pagar($c, ['meio' => $meio, 'banco' => $meio === 'dinheiro' ? '' : 'Bradesco'])->assertSessionHasNoErrors();
        }

        $this->assertSame(6, PagamentoCompra::count());
    }

    public function test_forma_de_pagamento_e_obrigatoria_e_precisa_ser_valida(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['meio' => ''])->assertSessionHasErrors('meio');
        $this->pagar($c, ['meio' => 'fiado'])->assertSessionHasErrors('meio');

        $this->assertSame(0, PagamentoCompra::count());
    }

    public function test_banco_e_obrigatorio_menos_para_dinheiro(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['meio' => 'pix', 'banco' => ''])->assertSessionHasErrors('banco');
        $this->assertSame(0, PagamentoCompra::count());

        $this->pagar($c, ['meio' => 'dinheiro', 'banco' => ''])->assertSessionHasNoErrors();
        $this->assertNull(PagamentoCompra::first()->banco);
    }

    public function test_banco_tem_limite_de_tamanho(): void
    {
        $this->pagar($this->compra(), ['banco' => str_repeat('a', 101)])->assertSessionHasErrors('banco');
    }

    public function test_historico_mostra_forma_e_banco(): void
    {
        $c = $this->compra();
        $this->pagar($c, ['meio' => 'transferencia', 'banco' => 'Sicoob']);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertSee('Transferência')
            ->assertSee('Sicoob');
    }

    public function test_painel_mostra_forma_e_banco_nos_ultimos_pagamentos(): void
    {
        $this->pagar($this->compra(), ['meio' => 'cheque', 'banco' => 'Caixa']);

        $this->actingAs($this->fin)->get(route('financeiro.index'))->assertSee('Cheque')->assertSee('Caixa');
    }

    public function test_quadro_de_pagamento_tem_os_campos_e_sugere_bancos_ja_usados(): void
    {
        $c = $this->compra();
        $outra = $this->compra(['supplier' => 'Kabum']);
        PagamentoCompra::create(['purchase_request_id' => $outra->id, 'valor' => 10, 'forma' => 'parcelado', 'meio' => 'pix', 'banco' => 'Nubank PJ', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);

        $this->actingAs($this->fin)->get(route('financeiro.aguardando'))
            ->assertSee('name="meio"', false)
            ->assertSee('name="banco"', false)
            ->assertSee('value="cheque"', false)
            ->assertSee('Nubank PJ');
    }

    public function test_pagamentos_antigos_sem_forma_continuam_aparecendo(): void
    {
        $c = $this->compra();
        PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 100, 'forma' => 'parcelado', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))->assertOk()->assertSee('R$ 100,00');
    }

    public function test_quadro_ja_vem_com_a_condicao_combinada(): void
    {
        $this->compra(['condicao_pagamento' => 'parcelado', 'parcelas' => 4, 'valor' => 1000, 'preco_unitario' => 1000]);

        $html = $this->actingAs($this->fin)->get(route('financeiro.aguardando'))->getContent();

        $this->assertMatchesRegularExpression('/value="parcelado"\s+checked/', $html);
        $this->assertStringContainsString('250,00', $html); // parcela sugerida
    }
}
