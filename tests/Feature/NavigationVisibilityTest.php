<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function navSection(string $html): string
    {
        $start = strpos($html, '<nav');
        $end = strpos($html, '</nav>', $start);

        return substr($html, $start, $end - $start);
    }

    public function test_admin_nav_shows_only_admin_link(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.index'));
        $nav = $this->navSection($response->getContent());

        $this->assertStringContainsString('⚙ Admin', $nav);
        $this->assertStringNotContainsString('📋 Pendências', $nav);
        $this->assertStringNotContainsString('🔍 Conferência', $nav);
        $this->assertStringNotContainsString('📦 Entrada', $nav);
        $this->assertStringNotContainsString('Minhas Requisições', $nav);
    }

    public function test_conferente_nav_shows_only_conferencia_link(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));
        $nav = $this->navSection($response->getContent());

        $this->assertStringContainsString('🔍 Conferência', $nav);
        $this->assertStringNotContainsString('⚙ Admin', $nav);
        $this->assertStringNotContainsString('📦 Entrada', $nav);
        $this->assertStringNotContainsString('Minhas Requisições', $nav);
    }

    public function test_entrada_nav_shows_conferencia_and_entrada_links(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));
        $nav = $this->navSection($response->getContent());

        $this->assertStringContainsString('🔍 Conferência', $nav);
        $this->assertStringContainsString('📦 Entrada', $nav);
        $this->assertStringNotContainsString('⚙ Admin', $nav);
        $this->assertStringNotContainsString('Minhas Requisições', $nav);
    }

    public function test_vendedor_nav_shows_only_minhas_requisicoes(): void
    {
        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($vendedor)->get(route('requests.index'));
        $nav = $this->navSection($response->getContent());

        $this->assertStringContainsString('Minhas Requisições', $nav);
        $this->assertStringNotContainsString('⚙ Admin', $nav);
        $this->assertStringNotContainsString('🔍 Conferência', $nav);
        $this->assertStringNotContainsString('📦 Entrada', $nav);
    }
}
