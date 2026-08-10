<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendedorMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('requests.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_plain_user_is_allowed(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertOk();
    }

    public function test_admin_is_forbidden(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('requests.index'));

        $response->assertForbidden();
    }

    public function test_conferente_is_forbidden(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('requests.index'));

        $response->assertForbidden();
    }

    public function test_entrada_is_forbidden(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('requests.index'));

        $response->assertForbidden();
    }

    public function test_admin_is_forbidden_from_creating_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('requests.create'));

        $response->assertForbidden();
    }
}
