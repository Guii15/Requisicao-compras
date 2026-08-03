<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConferenciaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('conferencia.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('conferencia.index'));

        $response->assertForbidden();
    }

    public function test_conferente_can_access_index(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_approved_requests_without_status_conferencia(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $pending = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Pendente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Ja Conferido']);
        PurchaseRequest::factory()->create(['status' => 'pendente', 'status_conferencia' => null, 'product_name' => 'Produto Nao Aprovado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('Produto Pendente');
        $response->assertDontSee('Produto Ja Conferido');
        $response->assertDontSee('Produto Nao Aprovado');
    }
}
