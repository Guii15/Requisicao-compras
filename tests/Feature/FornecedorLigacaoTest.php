<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O campo fornecedor é texto livre (sem autocomplete nem bloqueio). Só quando o nome digitado é
 * IDÊNTICO a um fornecedor já cadastrado (depois de normalizar) o sistema liga a ele, em silêncio.
 * Nome apenas parecido nunca é juntado nem cria fornecedor: isso é decidido na unificação.
 */
class FornecedorLigacaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function atualizar(PurchaseRequest $item, array $dados)
    {
        return $this->actingAs($this->admin)
            ->patch(route('admin.requests.update', $item), array_merge(['status' => 'aprovado', 'empresa_compradora' => 'Binário'], $dados));
    }

    public function test_admin_digita_nome_igual_depois_de_normalizar_e_liga_ao_cadastrado(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['supplier' => null]);

        $this->atualizar($item, ['supplier' => '  joyce informatica ltda '])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame($joyce->id, $item->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $item->supplier);
        $this->assertSame('joyce informatica ltda', trim($item->supplier_original));
        $this->assertSame(1, Fornecedor::count());
    }

    public function test_nome_parecido_nao_e_bloqueado_nem_juntado_nem_cria_fornecedor(): void
    {
        Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['supplier' => null]);

        $this->atualizar($item, ['supplier' => 'joyce'])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertNull($item->fornecedor_id);
        $this->assertSame('Joyce', $item->supplier);
        $this->assertSame(1, Fornecedor::count());
    }

    public function test_nome_novo_continua_texto_livre_sem_criar_fornecedor(): void
    {
        $item = PurchaseRequest::factory()->create(['supplier' => null]);

        $this->atualizar($item, ['supplier' => 'kabum'])->assertSessionHasNoErrors();

        $this->assertSame('Kabum', $item->fresh()->supplier);
        $this->assertNull($item->fresh()->fornecedor_id);
        $this->assertSame(0, Fornecedor::count());
    }

    public function test_trocar_o_nome_desliga_o_fornecedor_antigo(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['supplier' => 'JOYCE INFORMÁTICA', 'fornecedor_id' => $joyce->id]);

        $this->atualizar($item, ['supplier' => 'kabum']);

        $this->assertNull($item->fresh()->fornecedor_id);
        $this->assertSame('Kabum', $item->fresh()->supplier);
    }

    public function test_fornecedor_vazio_limpa_tudo(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['supplier' => 'JOYCE INFORMÁTICA', 'fornecedor_id' => $joyce->id]);

        $this->atualizar($item, ['supplier' => ''])->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->fornecedor_id);
        $this->assertNull($item->fresh()->supplier);
    }

    public function test_tela_de_compras_liga_so_quando_idêntico(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $igual = PurchaseRequest::factory()->aprovado()->create();
        $parecido = PurchaseRequest::factory()->aprovado()->create();
        $dados = ['data_compra' => '2026-09-20', 'preco_unitario' => '10,00', 'condicao_pagamento' => 'a_vista'];

        $this->actingAs($this->admin)->patch(route('admin.compras.update', $igual), $dados + ['supplier' => 'Joyce Informatica Ltda'])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->patch(route('admin.compras.update', $parecido), $dados + ['supplier' => 'Joyce'])->assertSessionHasNoErrors();

        $this->assertSame($joyce->id, $igual->fresh()->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $igual->fresh()->supplier);
        $this->assertNull($parecido->fresh()->fornecedor_id);
        $this->assertSame('Joyce', $parecido->fresh()->supplier);
        $this->assertSame(1, Fornecedor::count());
    }

    public function test_vendedor_liga_so_quando_bate_exato_e_nunca_cria(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $vendedor = User::factory()->create(['role' => null]);

        $this->actingAs($vendedor)->post(route('requests.store'), $this->pedido('joyce informatica'));
        $this->actingAs($vendedor)->post(route('requests.store'), $this->pedido('Joyce'));

        [$exato, $parecido] = PurchaseRequest::orderBy('id')->get()->all();
        $this->assertSame($joyce->id, $exato->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $exato->supplier);
        $this->assertSame('joyce informatica', $exato->supplier_original);
        $this->assertNull($parecido->fornecedor_id);
        $this->assertSame('Joyce', $parecido->supplier);
        $this->assertSame(1, Fornecedor::count());
    }

    public function test_modal_do_admin_e_texto_livre_com_sugestoes_do_navegador(): void
    {
        PurchaseRequest::factory()->create(['status' => 'pendente', 'supplier' => 'Bomvink']);

        $this->actingAs($this->admin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('<datalist id="supplier-options">', false)
            ->assertDontSee('data-fornecedor-input', false)
            ->assertDontSee('Você quis dizer', false);
    }

    public function test_nao_existe_mais_a_busca_de_autocomplete(): void
    {
        $this->actingAs($this->admin)->getJson('/admin/fornecedores/buscar?q=joyce')->assertNotFound();
    }

    private function pedido(string $fornecedor): array
    {
        return [
            'requester_name' => 'Vendedor', 'supplier' => $fornecedor, 'urgency' => 'media', 'reason' => 'Reposição',
            'justification' => 'Filial 31', 'tipo_entrega' => 'estoque',
            'products' => [['product_name' => 'Produto A', 'quantity' => 1]],
        ];
    }
}
