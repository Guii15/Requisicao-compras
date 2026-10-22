<?php

namespace Tests\Feature;

use App\Models\Cheque;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\SaldoFornecedores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contas que não vieram de uma requisição (saldo antigo, conta do Bling, fornecedor sem compra no
 * sistema): o Financeiro lança à mão e elas entram no saldo, nos pagamentos e no relatório como
 * qualquer compra, mas não aparecem nas telas de compras.
 */
class FinanceiroContaAvulsaTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false, 'name' => 'Ana Financeiro']);
    }

    private function lancar(array $extra = [])
    {
        return $this->actingAs($this->fin)->post(route('financeiro.lancarConta'), array_merge([
            'fornecedor' => 'Distribuidora Nova', 'descricao' => 'Saldo anterior ao sistema', 'valor' => '12.500,00',
            'data_conta' => '2026-09-15', 'condicao' => 'a_vista', 'vencimento' => '2026-10-30',
        ], $extra));
    }

    private function conta(): PurchaseRequest
    {
        return PurchaseRequest::withoutGlobalScopes()->where('tipo_registro', PurchaseRequest::CONTA_AVULSA)->sole();
    }

    private function linha(): array
    {
        return app(SaldoFornecedores::class)->linhas()->sole();
    }

    public function test_lanca_conta_de_fornecedor_sem_compra_e_entra_no_saldo(): void
    {
        $this->lancar()->assertSessionHasNoErrors()
            ->assertRedirect(route('financeiro.fornecedor', 'DISTRIBUIDORA NOVA'))
            ->assertSessionHas('success');

        $c = $this->conta();
        $this->assertSame(['Distribuidora Nova', 'Saldo anterior ao sistema', '12500.00', 'aprovado', 'Ana Financeiro', $this->fin->id],
            [$c->supplier, $c->product_name, $c->preco_unitario, $c->status, $c->requester_name, $c->user_id]);

        $l = $this->linha();
        $this->assertSame(12500.0, $l['aberto']);
        $this->assertSame('2026-10-30', $l['proximo_vencimento']->format('Y-m-d'));
        $this->assertSame('Em aberto', $l['situacao']);
    }

    public function test_nao_aparece_nas_telas_de_compras(): void
    {
        $this->lancar();

        $this->assertSame(0, PurchaseRequest::count());
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.historico-compras'))->assertOk()->assertDontSee('Saldo anterior ao sistema');
    }

    public function test_fornecedor_que_ja_existe_cai_no_mesmo_grupo(): void
    {
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'supplier' => 'Joyce Informática', 'quantity' => 1, 'data_compra' => '2026-09-20',
            'preco_unitario' => 1000, 'valor' => 1000, 'product_name' => 'Memória',
        ]);
        app(\App\Services\UnificadorFornecedores::class)->aplicar(['Joyce Informática' => 'Joyce Informática']);

        $this->lancar(['fornecedor' => 'JOYCE INFORMATICA LTDA', 'valor' => '500,00'])->assertSessionHasNoErrors();

        $resumo = app(SaldoFornecedores::class)->resumo();
        $this->assertCount(1, $resumo);
        $this->assertSame(1500.0, $resumo->first()['saldo']);
        $this->assertSame('Joyce Informática', $this->conta()->supplier);
    }

    public function test_parcelado_vence_de_mes_em_mes(): void
    {
        $this->lancar(['condicao' => 'parcelado', 'parcelas' => '3', 'valor' => '3.000,00', 'vencimento' => '2026-11-10'])->assertSessionHasNoErrors();

        $l = $this->linha();
        $this->assertSame('Parcelado em 3x', $l['condicao']);
        $this->assertSame(1000.0, $l['valor_parcela']);
        $this->assertSame('2026-11-10', $l['proximo_vencimento']->format('Y-m-d'));
    }

    public function test_conta_ja_paga_entra_quitada_com_o_pagamento(): void
    {
        $this->lancar(['ja_paga' => '1', 'meio' => 'boleto', 'banco' => 'Sicoob', 'data_pagamento' => '2026-09-20'])->assertSessionHasNoErrors();

        $pg = PagamentoCompra::sole();
        $this->assertSame(['12500.00', 'a_vista', 'boleto', 'Sicoob', '2026-09-20', $this->conta()->id],
            [$pg->valor, $pg->forma, $pg->meio, $pg->banco, $pg->data_pagamento->format('Y-m-d'), $pg->purchase_request_id]);
        $this->assertSame('Pago', $this->linha()['situacao']);
    }

    public function test_conta_ja_paga_em_cheque_desdobra_as_folhas(): void
    {
        $this->lancar(['valor' => '9.000,00', 'ja_paga' => '1', 'meio' => 'cheque', 'banco' => 'Itaú', 'data_pagamento' => '2026-09-20', 'cheque_primeira_folha' => '000050'])
            ->assertSessionHasNoErrors();

        $this->assertSame([50, 51], Cheque::orderBy('numero')->pluck('numero')->all());
    }

    public function test_conta_ja_paga_exige_os_dados_do_pagamento_e_nada_fica_salvo(): void
    {
        $this->lancar(['ja_paga' => '1'])->assertSessionHasErrors(['meio', 'data_pagamento']);
        $this->lancar(['ja_paga' => '1', 'meio' => 'cheque', 'banco' => 'Itaú', 'data_pagamento' => '2026-09-20'])->assertSessionHasErrors('cheque_primeira_folha');

        $this->assertSame(0, PurchaseRequest::withoutGlobalScopes()->count());
    }

    public function test_folha_de_cheque_usada_nao_deixa_conta_pela_metade(): void
    {
        $this->lancar(['valor' => '100,00', 'ja_paga' => '1', 'meio' => 'cheque', 'banco' => 'Itaú', 'data_pagamento' => '2026-09-20', 'cheque_primeira_folha' => '50']);

        $this->lancar(['valor' => '100,00', 'ja_paga' => '1', 'meio' => 'cheque', 'banco' => 'Itaú', 'data_pagamento' => '2026-09-20', 'cheque_primeira_folha' => '50'])
            ->assertSessionHasErrors('cheque_primeira_folha');

        $this->assertSame(1, PurchaseRequest::withoutGlobalScopes()->count());
    }

    public function test_campos_obrigatorios(): void
    {
        $this->lancar(['fornecedor' => '', 'descricao' => '', 'valor' => '0', 'data_conta' => ''])
            ->assertSessionHasErrors(['fornecedor', 'descricao', 'valor', 'data_conta']);
        $this->lancar(['condicao' => 'parcelado', 'parcelas' => ''])->assertSessionHasErrors('parcelas');

        $this->assertSame(0, PurchaseRequest::withoutGlobalScopes()->count());
    }

    public function test_conta_lancada_pode_ser_paga_estornada_e_ter_acrescimo(): void
    {
        $this->lancar(['valor' => '1.000,00']);
        $c = $this->conta();

        $this->actingAs($this->fin)->post(route('financeiro.pagar', $c), [
            'forma' => 'parcelado', 'valor' => '400,00', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22',
        ])->assertSessionHasNoErrors();
        $this->assertSame(600.0, $this->linha()['aberto']);

        $this->actingAs($this->fin)->post(route('financeiro.pagarFornecedor', 'DISTRIBUIDORA NOVA'), [
            'valor' => '100,00', 'meio' => 'pix', 'banco' => 'Itaú', 'data_pagamento' => '2026-10-22',
        ])->assertSessionHasNoErrors();
        $this->assertSame(500.0, $this->linha()['aberto']);

        $this->actingAs($this->fin)->post(route('financeiro.estornar', PagamentoCompra::first()), ['motivo' => 'Lançado errado'])->assertSessionHasNoErrors();
        $this->assertSame(900.0, $this->linha()['aberto']);

        $this->actingAs($this->fin)->post(route('financeiro.acrescentar', $c), ['valor' => '50,00', 'motivo' => 'Juros'])->assertSessionHasNoErrors();
        $this->assertSame(50.0, $this->linha()['acrescimo']);
    }

    public function test_telas_mostram_que_e_conta_lancada_e_tem_o_botao(): void
    {
        $this->lancar();

        $this->actingAs($this->fin)->get(route('financeiro.fornecedores'))->assertOk()->assertSee('Lançar conta');
        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'DISTRIBUIDORA NOVA'))
            ->assertOk()->assertSee('Saldo anterior ao sistema')->assertSee('conta lançada')->assertSee('Lançar conta');
        $this->actingAs($this->fin)->get(route('financeiro.aguardando'))->assertOk()->assertSee('Saldo anterior ao sistema');
    }

    public function test_lista_de_nomes_para_escolher_o_fornecedor_e_empresa(): void
    {
        $this->lancar(['empresa_conta' => 'Binário']);

        $this->assertSame('Binário', $this->conta()->empresa);
        $this->actingAs($this->fin)->getJson(route('financeiro.nomesFornecedores'))->assertOk()->assertExactJson(['Distribuidora Nova']);
    }

    public function test_so_o_financeiro_lanca(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('financeiro.lancarConta'), ['fornecedor' => 'X', 'descricao' => 'Y', 'valor' => '10', 'data_conta' => '2026-10-01', 'condicao' => 'a_vista'])
            ->assertForbidden();
        $this->assertSame(0, PurchaseRequest::withoutGlobalScopes()->count());
    }
}
