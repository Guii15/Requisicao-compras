<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObsEntradaTest extends TestCase
{
    use RefreshDatabase;

    private function itemParaEntrada(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 2, 'quantidade_recebida' => 2,
        ], $attrs));
    }

    private function darEntrada(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs(User::factory()->create(['role' => 'entrada']))
            ->patch(route('entrada.darEntrada', $item), array_merge(['vendedor_destino' => 'Fulano', 'quantidade_entrada' => 2], $extra));
    }

    public function test_entrada_grava_a_observacao_ao_dar_entrada(): void
    {
        $item = $this->itemParaEntrada();

        $this->darEntrada($item, ['obs_entrada' => 'Caixa chegou amassada, produto ok'])->assertSessionHasNoErrors();

        $this->assertSame('Caixa chegou amassada, produto ok', $item->fresh()->obs_entrada);
        $this->assertNotNull($item->fresh()->entrada_concluida_em);
    }

    public function test_observacao_da_entrada_e_opcional(): void
    {
        $item = $this->itemParaEntrada();

        $this->darEntrada($item)->assertSessionHasNoErrors();

        $this->assertNull($item->fresh()->obs_entrada);
    }

    public function test_observacao_da_entrada_tem_limite_de_tamanho(): void
    {
        $item = $this->itemParaEntrada();

        $this->darEntrada($item, ['obs_entrada' => str_repeat('a', 501)])->assertSessionHasErrors('obs_entrada');

        $this->assertNull($item->fresh()->entrada_concluida_em);
    }

    public function test_modal_dar_entrada_tem_o_campo_de_observacao(): void
    {
        $this->itemParaEntrada();

        $this->actingAs(User::factory()->create(['role' => 'entrada']))
            ->get(route('entrada.index'))
            ->assertSee('name="obs_entrada"', false);
    }

    public function test_vendedor_dono_ve_a_observacao_da_entrada(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemParaEntrada(['user_id' => $vendedor->id, 'entrada_concluida_em' => now(), 'obs_entrada' => 'Faltou o manual na caixa']);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertSee('Obs (Entrada)')
            ->assertSee('Faltou o manual na caixa');
    }

    public function test_admin_ve_a_observacao_da_entrada_no_painel_compras_e_compras_feitas(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $item = $this->itemParaEntrada([
            'entrada_concluida_em' => now(), 'obs_entrada' => 'Veio sem nota fiscal',
            'preco_unitario' => 10, 'valor' => 20, 'data_compra' => '2026-09-20', 'supplier' => 'Kabum',
        ]);
        PurchaseRequest::factory()->create(['grupo_id' => $item->grupo_id, 'status' => 'pendente']);

        $this->actingAs($admin)->get(route('admin.index'))->assertSee('Obs (Entrada)')->assertSee('Veio sem nota fiscal');

        // A tela "Compras" saiu: tudo fica em Compras Feitas, onde a nota vem no cartão do item com a etiqueta ENTRADA.
        foreach ([route('admin.compras.feitas'), route('admin.compras.feitas', ['abrir' => $item->id])] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee('>ENTRADA</span>', false)->assertSee('Veio sem nota fiscal');
        }
    }

    public function test_sem_observacao_nao_mostra_o_bloco(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemParaEntrada(['user_id' => $vendedor->id, 'entrada_concluida_em' => now(), 'obs_entrada' => null]);

        $this->actingAs($vendedor)->get(route('requests.index'))->assertDontSee('Obs (Entrada)');
    }

    public function test_conferencia_tambem_ve_a_observacao_da_entrada(): void
    {
        $this->itemParaEntrada(['entrada_concluida_em' => now(), 'obs_entrada' => 'Veio sem nota fiscal']);

        $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index', ['aba' => 'conferidos']))
            ->assertSee('Veio sem nota fiscal');
    }
}
