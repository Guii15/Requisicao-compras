<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\FornecedorMesclagem;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\FornecedorResolver;
use App\Services\SaldoFornecedores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Joyce" e "Joyce Informática" são o mesmo fornecedor, mas o sistema não junta sozinho nomes
 * com palavras diferentes. O Financeiro junta na tela do fornecedor e escolhe o nome que fica.
 */
class FinanceiroJuntarFornecedoresTest extends TestCase
{
    use RefreshDatabase;

    private User $fin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fin = User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    private function compra(string $fornecedor, float $valor): PurchaseRequest
    {
        return PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'supplier' => $fornecedor, 'quantity' => 1, 'data_compra' => '2026-09-20',
            'preco_unitario' => $valor, 'valor' => $valor, 'product_name' => 'Memória',
        ]);
    }

    private function juntar(string $chave, string $outro, string $fica = 'outro')
    {
        return $this->actingAs($this->fin)->post(route('financeiro.juntarFornecedor', $chave), ['outro' => $outro, 'fica' => $fica]);
    }

    public function test_junta_os_dois_com_o_nome_escolhido_e_soma_o_saldo(): void
    {
        $curta = $this->compra('Joyce', 1000);
        $this->compra('Joyce Informática', 500);
        PagamentoCompra::create(['purchase_request_id' => $curta->id, 'valor' => 300, 'forma' => 'parcelado', 'data_pagamento' => '2026-09-25', 'user_id' => $this->fin->id]);

        $this->juntar('JOYCE', 'JOYCE INFORMATICA')
            ->assertRedirect(route('financeiro.fornecedor', 'JOYCE INFORMATICA'))
            ->assertSessionHas('success');

        $resumo = app(SaldoFornecedores::class)->resumo();
        $this->assertSame(['JOYCE INFORMATICA'], $resumo->pluck('chave')->all());
        $this->assertSame('Joyce Informática', $resumo->first()['nome']);
        $this->assertSame(1200.0, $resumo->first()['saldo']);
        $this->assertSame(2, $resumo->first()['compras']);

        // O que a pessoa digitou fica guardado.
        $this->assertSame('Joyce', $curta->fresh()->supplier_original);
        $this->assertSame(Fornecedor::where('nome_normalizado', 'JOYCE INFORMATICA')->value('id'), $curta->fresh()->fornecedor_id);
    }

    public function test_pode_manter_o_nome_deste(): void
    {
        $this->compra('Joyce', 1000);
        $this->compra('Joyce Informática', 500);

        $this->juntar('JOYCE', 'JOYCE INFORMATICA', 'este')->assertRedirect(route('financeiro.fornecedor', 'JOYCE'));

        $this->assertSame(['Joyce'], PurchaseRequest::pluck('supplier')->unique()->values()->all());
    }

    public function test_fica_registrado_quem_juntou(): void
    {
        $this->compra('Joyce', 1000);
        $this->compra('Joyce', 200);
        $this->compra('Joyce Informática', 500);

        $this->juntar('JOYCE', 'JOYCE INFORMATICA');

        $m = FornecedorMesclagem::sole();
        $this->assertSame(['Joyce', 'JOYCE', 'Joyce Informática', 2, $this->fin->id],
            [$m->origem_nome, $m->origem_normalizado, $m->destino_nome, $m->compras_afetadas, $m->user_id]);
    }

    public function test_quem_digitar_o_nome_juntado_depois_cai_no_fornecedor_que_ficou(): void
    {
        $this->compra('Joyce', 1000);
        $this->compra('Joyce Informática', 500);
        $this->juntar('JOYCE', 'JOYCE INFORMATICA');

        $this->assertSame('Joyce Informática', app(FornecedorResolver::class)->exato('joyce')?->nome);
        $this->assertSame('Joyce Informática', app(FornecedorResolver::class)->exato('JOYCE INFORMATICA LTDA')?->nome);
        $this->assertNull(app(FornecedorResolver::class)->exato('Kabum'));
    }

    public function test_juntar_de_volta_nao_cria_volta_infinita(): void
    {
        $this->compra('Joyce', 1000);
        $this->compra('Joyce Informática', 500);
        $this->juntar('JOYCE', 'JOYCE INFORMATICA');
        $this->compra('Joyce', 100);

        $this->juntar('JOYCE INFORMATICA', 'JOYCE')->assertSessionHasNoErrors();

        $this->assertSame('Joyce', app(FornecedorResolver::class)->exato('Joyce Informática')?->nome);
        $this->assertSame('Joyce', app(FornecedorResolver::class)->exato('Joyce')?->nome);
    }

    public function test_nao_junta_com_ele_mesmo_nem_com_quem_nao_existe(): void
    {
        $this->compra('Joyce', 1000);

        $this->juntar('JOYCE', 'JOYCE')->assertSessionHasErrors('outro');
        $this->juntar('JOYCE', 'NAO EXISTE')->assertSessionHasErrors('outro');
        $this->assertSame(0, FornecedorMesclagem::count());
    }

    public function test_tela_mostra_os_parecidos_primeiro_para_escolher(): void
    {
        $this->compra('Joyce', 1000);
        $this->compra('Joyce Informática', 500);
        $this->compra('Kabum', 500);

        $this->actingAs($this->fin)->get(route('financeiro.fornecedor', 'JOYCE'))
            ->assertOk()
            ->assertSee('Juntar com outro fornecedor')
            ->assertSeeInOrder(['Parecidos', 'Joyce Informática', 'Outros', 'Kabum']);
    }

    public function test_so_o_financeiro_junta(): void
    {
        $this->compra('Joyce', 1000);
        $this->compra('Joyce Informática', 500);

        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->post(route('financeiro.juntarFornecedor', 'JOYCE'), ['outro' => 'JOYCE INFORMATICA', 'fica' => 'outro'])
            ->assertForbidden();
        $this->assertSame(0, FornecedorMesclagem::count());
    }
}
