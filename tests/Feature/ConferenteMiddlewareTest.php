<?php

namespace Tests\Feature;

use App\Http\Middleware\ConferenteMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ConferenteMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', ConferenteMiddleware::class])
            ->get('/_test/conferente-only', fn () => 'ok');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/_test/conferente-only');

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get('/_test/conferente-only');

        $response->assertForbidden();
    }

    public function test_user_with_role_conferente_is_allowed(): void
    {
        $user = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($user)->get('/_test/conferente-only');

        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_admin_is_allowed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/_test/conferente-only');

        $response->assertOk();
    }
}
