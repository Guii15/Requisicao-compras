<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FornecedorDuplicadoTest extends TestCase
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
            ->from(route('admin.index'))
            ->patch(route('admin.requests.update', $item), array_merge(['status' => 'aprovado'], $dados));
    }

    // ---- busca do autocomplete ----

    public function test_busca_acha_pelo_nome_normalizado(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        Fornecedor::create(['nome' => 'Kabum']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.fornecedores.buscar', ['q' => 'joyce inform']))
            ->assertOk()
            ->assertJsonPath('resultados.0.id', $joyce->id)
            ->assertJsonCount(1, 'resultados');
    }

    public function test_busca_devolve_o_exato_e_os_parecidos_de_um_nome_novo(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);

        $this->actingAs($this->admin)->getJson(route('admin.fornecedores.buscar', ['q' => 'Joyce']))
            ->assertJsonPath('exato', null)
            ->assertJsonPath('parecidos.0.id', $joyce->id);

        $this->actingAs($this->admin)->getJson(route('admin.fornecedores.buscar', ['q' => 'joyce informatica ltda']))
            ->assertJsonPath('exato.id', $joyce->id);
    }

    public function test_so_admin_usa_a_busca(): void
    {
        $this->getJson(route('admin.fornecedores.buscar', ['q' => 'x']))->assertUnauthorized();
        $vendedor = User::factory()->create(['role' => null]);
        $this->actingAs($vendedor)->getJson(route('admin.fornecedores.buscar', ['q' => 'x']))->assertForbidden();
    }

    // ---- modal "Atualizar Requisição" ----

    public function test_fornecedor_escolhido_no_autocomplete_e_usado(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['supplier' => 'joyce info', 'supplier_original' => null]);

        $this->atualizar($item, ['supplier' => 'JOYCE INFORMÁTICA', 'fornecedor_id' => $joyce->id])->assertSessionHasNoErrors();

        $item->refresh();
        $this->assertSame($joyce->id, $item->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $item->supplier);
        $this->assertSame('joyce info', $item->supplier_original);
    }

    public function test_nome_igual_depois_de_normalizar_reaproveita_o_fornecedor(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create();

        $this->atualizar($item, ['supplier' => '  joyce informatica ltda '])->assertSessionHasNoErrors();

        $this->assertSame(1, Fornecedor::count());
        $this->assertSame($joyce->id, $item->fresh()->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $item->fresh()->supplier);
    }

    public function test_nome_parecido_sem_confirmar_pergunta_voce_quis_dizer_e_nao_salva(): void
    {
        Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->atualizar($item, ['supplier' => 'Joyce'])
            ->assertSessionHasErrors(['supplier' => 'Você quis dizer JOYCE INFORMÁTICA?'])
            ->assertSessionHas('modal_aberto', $item->id);

        $this->assertSame(1, Fornecedor::count());
        $this->assertSame('pendente', $item->fresh()->status);
    }

    public function test_nome_parecido_confirmado_cria_fornecedor_novo(): void
    {
        Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create();

        $this->atualizar($item, ['supplier' => 'Joyce', 'confirmar_novo_fornecedor' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(2, Fornecedor::count());
        $this->assertSame('Joyce', $item->fresh()->fornecedor->nome);
    }

    public function test_nome_novo_sem_parecidos_e_criado_pelo_admin(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->atualizar($item, ['supplier' => 'kabum'])->assertSessionHasNoErrors();

        $fornecedor = Fornecedor::first();
        $this->assertSame('Kabum', $fornecedor->nome);
        $this->assertSame($this->admin->id, $fornecedor->criado_por);
        $this->assertSame($fornecedor->id, $item->fresh()->fornecedor_id);
    }

    public function test_fornecedor_vazio_desliga_o_fornecedor(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->create(['fornecedor_id' => $joyce->id, 'supplier' => 'JOYCE INFORMÁTICA']);

        $this->atualizar($item, ['supplier' => ''])->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->fornecedor_id);
        $this->assertNull($item->fresh()->supplier);
    }

    // ---- tela de Compras ----

    public function test_tela_de_compras_tambem_bloqueia_parecido(): void
    {
        Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->actingAs($this->admin)->from(route('admin.compras.edit', $item))
            ->patch(route('admin.compras.update', $item), [
                'data_compra' => '2026-09-20', 'preco_unitario' => '10,00', 'supplier' => 'Joyce',
            ])
            ->assertSessionHasErrors(['supplier' => 'Você quis dizer JOYCE INFORMÁTICA?']);

        $this->assertSame(1, Fornecedor::count());
    }

    public function test_tela_de_compras_reaproveita_por_nome_normalizado(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->actingAs($this->admin)->patch(route('admin.compras.update', $item), [
            'data_compra' => '2026-09-20', 'preco_unitario' => '10,00', 'supplier' => 'Joyce Informatica Ltda',
        ])->assertSessionHasNoErrors();

        $this->assertSame($joyce->id, $item->fresh()->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $item->fresh()->supplier);
    }

    // ---- vendedor ----

    public function test_vendedor_liga_ao_fornecedor_quando_bate_exato(): void
    {
        $joyce = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $vendedor = User::factory()->create(['role' => null]);

        $this->actingAs($vendedor)->post(route('requests.store'), $this->pedido('joyce informatica'));

        $item = PurchaseRequest::first();
        $this->assertSame($joyce->id, $item->fornecedor_id);
        $this->assertSame('JOYCE INFORMÁTICA', $item->supplier);
        $this->assertSame('joyce informatica', $item->supplier_original);
    }

    public function test_vendedor_nunca_cria_fornecedor(): void
    {
        Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $vendedor = User::factory()->create(['role' => null]);

        $this->actingAs($vendedor)->post(route('requests.store'), $this->pedido('Joyce'));

        $this->assertSame(1, Fornecedor::count());
        $item = PurchaseRequest::first();
        $this->assertNull($item->fornecedor_id);
        $this->assertSame('Joyce', $item->supplier);
        $this->assertSame('Joyce', $item->supplier_original);
    }

    public function test_tela_do_admin_usa_o_autocomplete_novo(): void
    {
        PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('data-fornecedor-input', false)
            ->assertSee('name="fornecedor_id"', false)
            ->assertDontSee('<datalist id="supplier-options">', false);
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
