<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRequestsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_groups_items_with_same_grupo_id(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'product_name' => 'Item Um']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'product_name' => 'Item Dois']);

        $response = $this->actingAs($admin)->get(route('admin.index'));
        $grupos = $response->original->getData()['requests'];

        $this->assertCount(1, $grupos);
        $this->assertCount(2, $grupos->first());
    }

    public function test_index_keeps_different_grupo_id_as_separate_groups(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PurchaseRequest::factory()->create(['product_name' => 'Item A']);
        PurchaseRequest::factory()->create(['product_name' => 'Item B']);

        $response = $this->actingAs($admin)->get(route('admin.index'));
        $grupos = $response->original->getData()['requests'];

        $this->assertCount(2, $grupos);
    }

    public function test_index_shows_all_items_of_a_matching_group(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'product_name' => 'Amortecedor Dianteiro']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'product_name' => 'Filtro de Ar']);

        $response = $this->actingAs($admin)->get(route('admin.index', ['product_name' => 'Amortecedor']));

        $response->assertSee('Amortecedor Dianteiro');
        $response->assertSee('Filtro de Ar');
    }

    public function test_index_shows_mixed_status_summary_for_group(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'aprovado']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'pendente']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Parcial', false);
    }

    public function test_index_avisa_quantas_aprovadas_ainda_nao_tem_dados_da_compra(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PurchaseRequest::factory()->aprovado()->create(['product_name' => 'Sem Dados']);
        PurchaseRequest::factory()->aprovado()->create([
            'product_name'   => 'Com Dados',
            'data_compra'    => '2026-09-20',
            'preco_unitario' => 10,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertSee('1 aprovada ainda sem dados da compra');
    }

    public function test_index_mostra_dados_da_compra_do_item_pendente_no_grupo(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PurchaseRequest::factory()->create([
            'status'         => 'pendente',
            'supplier'       => 'Auto Peças Sul',
            'preco_unitario' => 890.50,
            'valor'          => 2671.50,
            'data_compra'    => '2026-09-01',
            'data_coleta'    => '2026-09-15',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertSee('Unitário: R$ 890,50', false)
            ->assertSee('01/09/2026', false);
    }

    public function test_index_nao_mostra_aviso_quando_todas_tem_dados_da_compra(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PurchaseRequest::factory()->aprovado()->create([
            'data_compra'    => '2026-09-20',
            'preco_unitario' => 10,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertDontSee('sem dados da compra');
    }
}
