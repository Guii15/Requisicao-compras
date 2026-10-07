<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O comprador corrige a quantidade realmente comprada em "Dados da compra" (ex.: pediram 50, comprou 100).
 * A Conferência lê essa mesma quantidade, então passa a ver o número certo.
 */
class QuantidadeDadosCompraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function dados(array $extra = []): array
    {
        return array_merge([
            'data_compra' => '2026-09-20', 'preco_unitario' => '10,00', 'supplier' => 'Kabum',
            'condicao_pagamento' => 'a_vista',
        ], $extra);
    }

    private function salvar(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->admin)->patch(route('admin.compras.update', $item), $this->dados($extra));
    }

    public function test_admin_corrige_a_quantidade_comprada(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        $this->salvar($item, ['quantity' => '100'])->assertSessionHasNoErrors();

        $this->assertSame(100, $item->fresh()->quantity);
    }

    public function test_conferencia_passa_a_ver_a_quantidade_corrigida(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => null, 'product_name' => 'Cabo Teste']);
        $this->salvar($item, ['quantity' => '100']);

        $html = $this->actingAs(User::factory()->create(['role' => 'conferente']))->get(route('conferencia.index'))->getContent();

        $this->assertStringContainsString('Cabo Teste', $html);
        $this->assertStringContainsString('value="100" min="0"', $html);   // quantidade recebida já vem com 100
        $this->assertStringNotContainsString('value="50" min="0"', $html);
    }

    public function test_sem_o_campo_a_quantidade_nao_muda(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        $this->salvar($item)->assertSessionHasNoErrors();

        $this->assertSame(50, $item->fresh()->quantity);
    }

    public function test_quantidade_precisa_ser_inteira_e_maior_que_zero(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        foreach (['0', '-3', 'abc', '2,5', '1000001'] as $ruim) {
            $this->salvar($item, ['quantity' => $ruim])->assertSessionHasErrors('quantity');
        }

        $this->assertSame(50, $item->fresh()->quantity);
    }

    public function test_item_ja_conferido_nao_aceita_mudar_a_quantidade(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 50]);

        $this->salvar($item, ['quantity' => '100'])->assertSessionHasErrors('quantity');

        $this->assertSame(50, $item->fresh()->quantity);
        $this->assertNull($item->fresh()->data_compra); // nada foi salvo
    }

    public function test_item_ja_conferido_aceita_salvar_com_a_mesma_quantidade(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 50]);

        $this->salvar($item, ['quantity' => '50'])->assertSessionHasNoErrors();

        $this->assertSame('2026-09-20', $item->fresh()->data_compra->format('Y-m-d'));
    }

    public function test_item_de_recebimento_parcial_nao_aceita_mudar_a_quantidade(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 20, 'quantidade_original' => 100, 'restante_de_id' => PurchaseRequest::factory()->aprovado()->create()->id]);

        $this->salvar($item, ['quantity' => '30'])->assertSessionHasErrors('quantity');

        $this->assertSame(20, $item->fresh()->quantity);
    }

    public function test_formulario_mostra_o_campo_com_a_quantidade_atual(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        $html = $this->actingAs($this->admin)->get(route('admin.compras.feitas', ['abrir' => $item->id]))
            ->assertOk()
            ->assertSee('id="janela-compra"', false)
            ->assertSee('name="quantity"', false)
            ->getContent();

        // a janela é preenchida pelo botão do item: a quantidade atual vai nos dados dele, sem trava
        $dados = $this->dadosDaJanelaDeCompra($html, $item->id);
        $this->assertSame(50, $dados['quantity']);
        $this->assertSame('', $dados['travada']);
        $this->assertStringContainsString("campo('quantity').value = d.quantity;", $html);
    }

    public function test_formulario_de_item_conferido_mostra_o_campo_travado(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 50]);

        $html = $this->actingAs($this->admin)->get(route('admin.compras.feitas', ['abrir' => $item->id]))
            ->assertOk()
            ->assertSee('name="quantity"', false)
            ->getContent();

        $dados = $this->dadosDaJanelaDeCompra($html, $item->id);
        $this->assertSame(50, $dados['quantity']);
        $this->assertSame('conferido', $dados['travada']);
        // a janela trava o campo e explica o motivo quando o item vem marcado
        $this->assertStringContainsString("qtd.readOnly = d.travada !== '';", $html);
        $this->assertStringContainsString('Este item já foi conferido; a quantidade não pode mais ser alterada.', $html);
    }

    public function test_formulario_de_item_de_recebimento_parcial_tambem_vem_travado(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 20, 'quantidade_original' => 100]);

        $html = $this->actingAs($this->admin)->get(route('admin.compras.feitas', ['abrir' => $item->id]))->assertOk()->getContent();

        $this->assertSame('parcial', $this->dadosDaJanelaDeCompra($html, $item->id)['travada']);
        $this->assertStringContainsString('Recebimento parcial: ajuste a quantidade pelo botão Editar da Conferência.', $html);
    }

    public function test_mudar_a_quantidade_atualiza_o_custo_quando_nao_ha_total_digitado(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'valor' => null]);
        $this->salvar($item, ['quantity' => '100', 'preco_unitario' => '10,00']);

        $custo = app(\App\Services\SaldoFornecedores::class)->custo($item->fresh());

        $this->assertSame(1000.0, $custo);
    }
}
