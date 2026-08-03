<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'requester_name' => 'Vendedor Teste',
            'supplier' => 'Fornecedor Teste',
            'urgency' => 'media',
            'reason' => 'Reposição',
            'justification' => 'Filial 31',
            'tipo_entrega' => 'estoque',
            'products' => [
                ['product_name' => 'Produto A', 'quantity' => 2],
                ['product_name' => 'Produto B', 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_store_persists_tipo_entrega_on_every_product_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(
            route('requests.store'),
            $this->validStorePayload(['tipo_entrega' => 'entrega_direta'])
        );

        $this->assertDatabaseCount('purchase_requests', 2);
        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto A', 'tipo_entrega' => 'entrega_direta']);
        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto B', 'tipo_entrega' => 'entrega_direta']);
    }

    public function test_store_defaults_to_estoque_when_selected_in_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('requests.store'), $this->validStorePayload());

        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto A', 'tipo_entrega' => 'estoque']);
    }

    public function test_store_rejects_invalid_tipo_entrega(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('requests.store'),
            $this->validStorePayload(['tipo_entrega' => 'algo_invalido'])
        );

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_store_rejects_missing_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $payload = $this->validStorePayload();
        unset($payload['tipo_entrega']);

        $response = $this->actingAs($user)->post(route('requests.store'), $payload);

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertDatabaseCount('purchase_requests', 0);
    }
}
