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

        $response->assertSee('1 aprovada(s)', false);
        $response->assertSee('1 pendente(s)', false);
    }
}
