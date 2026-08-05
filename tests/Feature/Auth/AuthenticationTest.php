<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_login_screen_shows_default_heading_without_intended_url(): void
    {
        $response = $this->get('/login');

        $response->assertSee('Bem-vindo!');
        $response->assertDontSee('Bem-vindo, Conferente!');
    }

    public function test_login_screen_shows_conferencia_heading_after_hitting_protected_route(): void
    {
        $this->get(route('conferencia.index'));

        $response = $this->get('/login');

        $response->assertSee('Bem-vindo, Conferente!');
    }

    public function test_login_screen_shows_entrada_heading_after_hitting_protected_route(): void
    {
        $this->get(route('entrada.index'));

        $response = $this->get('/login');

        $response->assertSee('Bem-vindo à Entrada!');
    }

    public function test_login_screen_shows_admin_heading_after_hitting_protected_route(): void
    {
        $this->get(route('admin.index'));

        $response = $this->get('/login');

        $response->assertSee('Bem-vindo, Administrador!');
    }

    public function test_login_redirects_to_intended_admin_screen_after_authentication(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->get(route('admin.index'));

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.index'));
    }

    public function test_login_redirects_to_intended_conferencia_screen_after_authentication(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $this->get(route('conferencia.index'));

        $response = $this->post('/login', [
            'email' => $conferente->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('conferencia.index'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
