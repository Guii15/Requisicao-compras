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

    public function test_login_screen_shows_conferencia_heading_on_its_own_url(): void
    {
        $response = $this->get(route('login.perfil', 'conferencia'));

        $response->assertSee('Bem-vindo, Conferente!');
    }

    public function test_login_screen_shows_entrada_heading_on_its_own_url(): void
    {
        $response = $this->get(route('login.perfil', 'entrada'));

        $response->assertSee('Bem-vindo à Entrada!');
    }

    public function test_login_screen_shows_admin_heading_on_its_own_url(): void
    {
        $response = $this->get(route('login.perfil', 'admin'));

        $response->assertSee('Bem-vindo, Administrador!');
    }

    public function test_login_screen_shows_vendedor_heading_on_its_own_url(): void
    {
        $response = $this->get(route('login.perfil', 'vendedor'));

        $response->assertSee('Bem-vindo, Vendedor!');
    }

    public function test_invalid_perfil_returns_404(): void
    {
        $this->get('/login/gerente')->assertNotFound();
    }

    public function test_picker_page_lists_the_4_perfil_links(): void
    {
        $response = $this->get('/login');

        $response->assertSee(route('login.perfil', 'vendedor'), false);
        $response->assertSee(route('login.perfil', 'conferencia'), false);
        $response->assertSee(route('login.perfil', 'entrada'), false);
        $response->assertSee(route('login.perfil', 'admin'), false);
    }

    public function test_pode_autenticar_a_partir_de_qualquer_url_de_login_por_perfil(): void
    {
        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->get(route('login.perfil', 'admin'));

        $response = $this->post('/login', [
            'email' => $vendedor->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
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

    public function test_dashboard_redirects_vendedor_to_requests_index(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('requests.index'));
    }

    public function test_dashboard_redirects_admin_to_admin_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertRedirect(route('admin.index'));
    }

    public function test_dashboard_redirects_conferente_to_conferencia_index(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('dashboard'));

        $response->assertRedirect(route('conferencia.index'));
    }

    public function test_dashboard_redirects_entrada_to_entrada_index(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('dashboard'));

        $response->assertRedirect(route('entrada.index'));
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
