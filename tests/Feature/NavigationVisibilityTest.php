<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_nav_shows_only_admin_link(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('⚙ Admin');
        $response->assertDontSee('📋 Pendências');
        $response->assertDontSee('🔍 Conferência');
        $response->assertDontSee('📦 Entrada');
        $response->assertDontSee('Minhas Requisições');
    }

    public function test_conferente_nav_shows_only_conferencia_link(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('🔍 Conferência');
        $response->assertDontSee('⚙ Admin');
        $response->assertDontSee('📦 Entrada');
        $response->assertDontSee('Minhas Requisições');
    }

    public function test_entrada_nav_shows_conferencia_and_entrada_links(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('🔍 Conferência');
        $response->assertSee('📦 Entrada');
        $response->assertDontSee('⚙ Admin');
        $response->assertDontSee('Minhas Requisições');
    }

    public function test_vendedor_nav_shows_only_minhas_requisicoes(): void
    {
        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($vendedor)->get(route('requests.index'));

        $response->assertSee('Minhas Requisições');
        $response->assertDontSee('⚙ Admin');
        $response->assertDontSee('🔍 Conferência');
        $response->assertDontSee('📦 Entrada');
    }
}
