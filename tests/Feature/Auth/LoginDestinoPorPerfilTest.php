<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Depois do login a pessoa volta para a página que tentou abrir antes, MAS só se o perfil
 * dela puder ver essa página. Senão ia direto num 403 (ex: celular que já abriu /admin
 * e agora é usado por um vendedor) — ela vai para a tela inicial do perfil.
 */
class LoginDestinoPorPerfilTest extends TestCase
{
    use RefreshDatabase;

    private function entrar(User $user, string $perfil)
    {
        return $this->post(route('login.perfil.store', $perfil), ['email' => $user->email, 'password' => 'password']);
    }

    public function test_vendedor_que_caiu_numa_pagina_do_admin_vai_para_a_tela_dele(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->get('/admin')->assertRedirect(route('login'));
        $this->entrar($vendedor, 'vendedor')->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($vendedor);
    }

    public function test_vendedor_que_caiu_na_conferencia_vai_para_a_tela_dele(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->get('/conferencia?aba=coleta');
        $this->entrar($vendedor, 'vendedor')->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_admin_comum_nao_volta_para_a_tela_do_super_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'admin.comum@example.com']);

        $this->get('/admin/usuarios');
        $this->entrar($admin, 'admin')->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_pagina_permitida_continua_sendo_o_destino(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $this->get('/conferencia?aba=coleta');
        $this->entrar($conferente, 'conferencia')->assertRedirect('/conferencia?aba=coleta');
    }

    public function test_vendedor_volta_para_a_propria_pagina(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->get('/requisicoes/nova');
        $this->entrar($vendedor, 'vendedor')->assertRedirect('/requisicoes/nova');
    }

    public function test_sem_pagina_guardada_vai_para_a_tela_inicial(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->entrar($vendedor, 'vendedor')->assertRedirect(route('dashboard', absolute: false));
    }
}
