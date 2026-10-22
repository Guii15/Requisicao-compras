<?php

namespace Tests\Feature;

use App\Models\Banco;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** O banco do pagamento é escolhido de uma lista de bancos cadastrados; um banco novo pode ser cadastrado na hora. */
class FinanceiroBancosTest extends TestCase
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
            'forma' => 'a_vista', 'data_pagamento' => '2026-10-01', 'meio' => 'pix',
        ], $extra));
    }

    public function test_quadro_mostra_lista_de_bancos_cadastrados_e_opcao_de_cadastrar_novo(): void
    {
        $this->compra();
        Banco::create(['nome' => 'Sicoob']);
        Banco::create(['nome' => 'Itaú']);

        $html = $this->actingAs($this->fin)->get(route('financeiro.aguardando'))->getContent();

        $this->assertMatchesRegularExpression('/<select[^>]*name="banco"/', $html);
        $this->assertStringContainsString('<option value="Itaú">Itaú</option>', $html);
        $this->assertStringContainsString('<option value="Sicoob">Sicoob</option>', $html);
        $this->assertStringContainsString('value="__novo"', $html);
        $this->assertStringContainsString('name="banco_novo"', $html);
    }

    public function test_escolhe_banco_da_lista(): void
    {
        Banco::create(['nome' => 'Sicoob']);
        $c = $this->compra();

        $this->pagar($c, ['banco' => 'Sicoob'])->assertSessionHasNoErrors();

        $this->assertSame('Sicoob', PagamentoCompra::first()->banco);
        $this->assertSame(1, Banco::count());
    }

    public function test_cadastra_banco_novo_na_hora_e_ele_entra_na_lista(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['banco' => '__novo', 'banco_novo' => '  Sicredi  '])->assertSessionHasNoErrors();

        $this->assertSame('Sicredi', PagamentoCompra::first()->banco);
        $this->assertSame(['Sicredi'], Banco::nomes());
    }

    public function test_banco_novo_com_outra_grafia_nao_duplica(): void
    {
        Banco::create(['nome' => 'Itaú']);
        $c = $this->compra();

        $this->pagar($c, ['banco' => '__novo', 'banco_novo' => 'ITAU'])->assertSessionHasNoErrors();

        $this->assertSame('Itaú', PagamentoCompra::first()->banco);
        $this->assertSame(1, Banco::count());
    }

    public function test_banco_novo_sem_nome_da_erro(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['banco' => '__novo', 'banco_novo' => ''])->assertSessionHasErrors('banco_novo');

        $this->assertSame(0, PagamentoCompra::count());
        $this->assertSame(0, Banco::count());
    }

    public function test_dinheiro_nao_cadastra_banco(): void
    {
        $c = $this->compra();

        $this->pagar($c, ['meio' => 'dinheiro', 'banco' => '__novo', 'banco_novo' => 'Bradesco'])->assertSessionHasNoErrors();

        $this->assertNull(PagamentoCompra::first()->banco);
        $this->assertSame(0, Banco::count());
    }

    public function test_migration_traz_os_bancos_ja_digitados_nos_pagamentos(): void
    {
        $c = $this->compra();
        foreach (['Itaú', ' itau ', 'Nubank PJ', null] as $banco) {
            PagamentoCompra::create(['purchase_request_id' => $c->id, 'valor' => 10, 'forma' => 'parcelado', 'meio' => 'pix', 'banco' => $banco, 'data_pagamento' => '2026-09-25']);
        }
        Schema::drop('bancos');

        $migration = require database_path('migrations/2026_10_14_100000_create_bancos_table.php');
        $migration->up();

        $this->assertSame(['Itaú', 'Nubank PJ'], DB::table('bancos')->orderBy('nome')->pluck('nome')->all());
    }
}
