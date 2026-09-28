<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_comum_nao_acessa_tela_de_usuarios(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'outro.admin@example.com']);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_super_admin_acessa_tela_de_usuarios(): void
    {
        $superAdmin = User::factory()->create(['is_admin' => true, 'email' => config('admin.super_admin_email')]);

        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_aba_usuarios_so_aparece_para_super_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => 'outro.admin@example.com']);
        $superAdmin = User::factory()->create(['is_admin' => true, 'email' => config('admin.super_admin_email')]);

        $this->actingAs($admin)->get(route('admin.index'))->assertDontSee('Usuários', false);
        $this->actingAs($superAdmin)->get(route('admin.index'))->assertSee('Usuários', false);
    }

    public function test_aba_historico_de_compras_nao_aparece_no_menu(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email' => config('admin.super_admin_email')]);

        $this->actingAs($admin)->get(route('admin.index'))->assertDontSee('Histórico de Compras');
    }
}
