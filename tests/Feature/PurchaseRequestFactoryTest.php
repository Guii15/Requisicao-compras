<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_valid_persisted_purchase_request(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $request->id,
            'status' => 'pendente',
        ]);
        $this->assertNotNull($request->user_id);
        $this->assertNotNull($request->product_name);
    }

    public function test_aprovado_state_sets_status_to_aprovado(): void
    {
        $request = PurchaseRequest::factory()->aprovado()->create();

        $this->assertSame('aprovado', $request->status);
    }
}
