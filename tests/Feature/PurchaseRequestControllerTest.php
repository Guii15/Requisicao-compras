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

    public function test_update_changes_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'tipo_entrega' => 'estoque',
        ]);

        $this->actingAs($user)->patch(route('requests.update', $purchaseRequest), [
            'requester_name' => $purchaseRequest->requester_name,
            'supplier' => $purchaseRequest->supplier,
            'urgency' => $purchaseRequest->urgency,
            'reason' => $purchaseRequest->reason,
            'justification' => $purchaseRequest->justification,
            'tipo_entrega' => 'entrega_direta',
            'product_name' => $purchaseRequest->product_name,
            'product_code' => $purchaseRequest->product_code,
            'quantity' => $purchaseRequest->quantity,
        ]);

        $this->assertSame('entrega_direta', $purchaseRequest->fresh()->tipo_entrega);
    }

    public function test_update_rejects_invalid_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($user)->patch(route('requests.update', $purchaseRequest), [
            'requester_name' => $purchaseRequest->requester_name,
            'supplier' => $purchaseRequest->supplier,
            'urgency' => $purchaseRequest->urgency,
            'reason' => $purchaseRequest->reason,
            'justification' => $purchaseRequest->justification,
            'tipo_entrega' => 'algo_invalido',
            'product_name' => $purchaseRequest->product_name,
            'product_code' => $purchaseRequest->product_code,
            'quantity' => $purchaseRequest->quantity,
        ]);

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertSame('estoque', $purchaseRequest->fresh()->tipo_entrega);
    }

    public function test_index_shows_aguardando_conferencia_for_aprovado_without_status_conferencia(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => null,
            'product_name' => 'Produto Aguardando',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Aguardando conferência');
    }

    public function test_index_shows_conferido_ok_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto OK',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido ✓ OK');
        $response->assertDontSee('Aguardando conferência');
    }

    public function test_index_shows_divergente_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'product_name' => 'Produto Divergente',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido — Divergente');
    }

    public function test_index_shows_avancado_mesmo_assim_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Produto Avancado',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido — Avançado Mesmo Assim');
    }

    public function test_index_pendente_shows_no_conferencia_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'status_conferencia' => null,
            'product_name' => 'Produto Pendente',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Aguardando conferência');
        $response->assertDontSee('Conferido ✓ OK');
    }

    public function test_index_rejeitado_without_conferencia_shows_no_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejeitado',
            'status_conferencia' => null,
            'product_name' => 'Produto Rejeitado',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Aguardando conferência');
        $response->assertDontSee('Conferido ✓ OK');
    }

    public function test_index_rejeitado_with_status_conferencia_still_shows_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejeitado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Rejeitado Conferido',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido ✓ OK');
    }

    public function test_index_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'Conferido ✓ OK'));
    }
}
