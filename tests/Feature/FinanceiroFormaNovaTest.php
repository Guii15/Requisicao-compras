<?php

namespace Tests\Feature;

use App\Models\MeioPagamento;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Além das formas fixas (PIX, boleto...), o Financeiro cadastra uma forma de pagamento nova na hora. */
class FinanceiroFormaNovaTest extends TestCase
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
            'forma' => 'a_vista', 'data_pagamento' => '2026-10-01', 'banco' => 'Itaú',
        ], $extra));
    }

    public function test_cadastra_forma_nova_na_hora_e_ela_aparece_no_historico(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['meio' => '__novo', 'meio_novo' => '  Permuta  '])->assertSessionHasNoErrors();

        $m = MeioPagamento::sole();
        $this->assertSame('Permuta', $m->nome);
        $this->assertSame('m' . $m->id, PagamentoCompra::first()->meio);
        $this->assertSame('Permuta', PagamentoCompra::first()->meioRotulo());

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))->assertSee('Permuta');
    }

    public function test_forma_nova_entra_na_lista_do_quadro(): void
    {
        $this->compra();
        MeioPagamento::create(['nome' => 'Depósito em conta']);

        $html = $this->actingAs($this->fin)->get(route('financeiro.aguardando'))->getContent();

        $this->assertMatchesRegularExpression('/<option value="m\d+">Depósito em conta<\/option>/', $html);
        $this->assertStringContainsString('name="meio_novo"', $html);
        $this->assertStringContainsString('value="__novo">+ Cadastrar nova forma', $html);
    }

    public function test_escolhe_forma_cadastrada(): void
    {
        $m = MeioPagamento::create(['nome' => 'Permuta']);
        $c = $this->compra();

        $this->pagar($c, ['meio' => 'm' . $m->id])->assertSessionHasNoErrors();

        $this->assertSame('m' . $m->id, PagamentoCompra::first()->meio);
    }

    public function test_nome_de_forma_que_ja_existe_nao_duplica(): void
    {
        MeioPagamento::create(['nome' => 'Permuta']);

        $this->pagar($this->compra(), ['meio' => '__novo', 'meio_novo' => 'PERMUTA'])->assertSessionHasNoErrors();
        $this->pagar($this->compra(), ['meio' => '__novo', 'meio_novo' => 'pix'])->assertSessionHasNoErrors();

        $this->assertSame(1, MeioPagamento::count());
        $this->assertSame(['m' . MeioPagamento::first()->id, 'pix'], PagamentoCompra::orderBy('id')->pluck('meio')->all());
    }

    public function test_forma_nova_sem_nome_da_erro(): void
    {
        $this->pagar($this->compra(), ['meio' => '__novo', 'meio_novo' => ''])->assertSessionHasErrors('meio_novo');

        $this->assertSame(0, PagamentoCompra::count());
    }

    public function test_forma_cadastrada_nao_exige_banco(): void
    {
        $m = MeioPagamento::create(['nome' => 'Permuta']);

        $this->pagar($this->compra(), ['meio' => 'm' . $m->id, 'banco' => ''])->assertSessionHasNoErrors();

        $this->assertNull(PagamentoCompra::first()->banco);
    }

    public function test_codigo_de_forma_inexistente_e_recusado(): void
    {
        $this->pagar($this->compra(), ['meio' => 'm999'])->assertSessionHasErrors('meio');
    }
}
