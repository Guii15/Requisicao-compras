<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Empresa que fez a compra: o comprador informa em "Dados da compra" (opcional). O nome vai junto para o Financeiro.
 */
class EmpresaDaCompraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function salvar(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->admin)->patch(route('admin.compras.update', $item), array_merge([
            'data_compra' => '2026-10-02', 'preco_unitario' => '10,00', 'supplier' => 'Kabum', 'condicao_pagamento' => 'a_vista',
        ], $extra));
    }

    public function test_salva_a_empresa_limpando_espacos(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['empresa' => '  Oasis   Comércio  '])->assertSessionHasNoErrors();

        $this->assertSame('Oasis Comércio', $item->fresh()->empresa);
    }

    public function test_empresa_e_opcional(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['empresa' => 'Binário']);

        $this->salvar($item, ['empresa' => ''])->assertSessionHasNoErrors();
        $this->assertNull($item->fresh()->empresa);

        $outro = PurchaseRequest::factory()->aprovado()->create();
        $this->salvar($outro)->assertSessionHasNoErrors();
        $this->assertNull($outro->fresh()->empresa);
    }

    public function test_empresa_tem_limite_de_tamanho(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['empresa' => str_repeat('a', 101)])->assertSessionHasErrors('empresa');
    }

    public function test_binario_escrito_de_qualquer_jeito_vira_a_grafia_padrao(): void
    {
        foreach (['binario', 'BINÁRIO', ' Binario '] as $digitado) {
            $item = PurchaseRequest::factory()->aprovado()->create();
            $this->salvar($item, ['empresa' => $digitado])->assertSessionHasNoErrors();
            $this->assertSame('Binário', $item->fresh()->empresa, $digitado);
        }
    }

    public function test_outra_empresa_ja_usada_mantem_a_grafia_da_primeira_vez(): void
    {
        $primeira = PurchaseRequest::factory()->aprovado()->create();
        $this->salvar($primeira, ['empresa' => 'Oasis Comércio']);

        $segunda = PurchaseRequest::factory()->aprovado()->create();
        $this->salvar($segunda, ['empresa' => 'OASIS COMERCIO']);

        $this->assertSame('Oasis Comércio', $segunda->fresh()->empresa);
    }

    public function test_empresas_diferentes_nao_se_misturam(): void
    {
        $a = PurchaseRequest::factory()->aprovado()->create();
        $b = PurchaseRequest::factory()->aprovado()->create();
        $this->salvar($a, ['empresa' => 'Oasis']);
        $this->salvar($b, ['empresa' => 'Oasis Comércio']);

        $this->assertSame('Oasis', $a->fresh()->empresa);
        $this->assertSame('Oasis Comércio', $b->fresh()->empresa);
    }

    public function test_quadro_atualizar_requisicao_tambem_guarda_a_empresa(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin)->patch(route('admin.requests.update', $item), ['status' => 'aprovado', 'empresa' => 'binario'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Binário', $item->fresh()->empresa);
    }

    public function test_empresas_usadas_lista_a_padrao_e_as_ja_cadastradas_sem_repetir(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa' => 'Oasis Comércio']);
        PurchaseRequest::factory()->aprovado()->create(['empresa' => 'Oasis Comércio']);
        PurchaseRequest::factory()->aprovado()->create(['empresa' => 'Binário']);
        PurchaseRequest::factory()->aprovado()->create(['empresa' => null]);

        $this->assertSame(['Binário', 'Oasis Comércio'], PurchaseRequest::empresasUsadas());
    }

    public function test_formulario_de_dados_da_compra_tem_o_campo_com_sugestoes(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa' => 'Oasis Comércio']);
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->actingAs($this->admin)->get(route('admin.compras.edit', $item))
            ->assertOk()
            ->assertSee('name="empresa"', false)
            ->assertSee('value="Binário"', false)
            ->assertSee('value="Oasis Comércio"', false);
    }

    public function test_formulario_mostra_a_empresa_ja_salva(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['empresa' => 'Oasis Comércio']);

        $html = $this->actingAs($this->admin)->get(route('admin.compras.edit', $item))->getContent();

        $this->assertMatchesRegularExpression('/name="empresa"[^>]*value="Oasis Comércio"/u', $html);
    }

    public function test_compras_feitas_e_compras_mostram_a_empresa(): void
    {
        PurchaseRequest::factory()->aprovado()->create([
            'product_name' => 'Item Com Empresa', 'empresa' => 'Oasis Comércio', 'data_compra' => '2026-10-02', 'preco_unitario' => 10,
        ]);

        foreach ([route('admin.compras.feitas'), route('admin.compras.index')] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('Oasis Comércio');
        }
    }

    public function test_item_sem_empresa_nao_mostra_o_rotulo(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Item Sem Empresa', 'empresa' => null, 'data_compra' => '2026-10-02', 'preco_unitario' => 10]);

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))->assertDontSee('Empresa:');
    }
}
