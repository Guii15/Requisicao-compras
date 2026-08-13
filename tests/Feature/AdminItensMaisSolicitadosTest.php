<?php

namespace Tests\Feature;

use App\Models\ItemMaisSolicitado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItensMaisSolicitadosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.itens-mais-solicitados'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('admin.itens-mais-solicitados'))->assertForbidden();
    }

    public function test_admin_sees_ranking_ordered_by_total_desc(): void
    {
        ItemMaisSolicitado::create([
            'nome_canonico' => 'Filtro de Oleo', 'capacidade' => null, 'total_pedidos' => 3,
            'variacoes_agrupadas' => [['texto' => 'Filtro de Oleo', 'qtd' => 3]], 'atualizado_em' => now(),
        ]);
        ItemMaisSolicitado::create([
            'nome_canonico' => 'SSD 240GB', 'capacidade' => '240gb', 'total_pedidos' => 9,
            'variacoes_agrupadas' => [['texto' => 'SSD 240GB', 'qtd' => 9]], 'atualizado_em' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.itens-mais-solicitados'));

        $response->assertOk();
        $response->assertSeeInOrder(['SSD 240GB', 'Filtro de Oleo']);
    }

    public function test_shows_capacidade_when_present(): void
    {
        ItemMaisSolicitado::create([
            'nome_canonico' => 'SSD 240GB', 'capacidade' => '240gb', 'total_pedidos' => 1,
            'variacoes_agrupadas' => [['texto' => 'SSD 240GB', 'qtd' => 1]], 'atualizado_em' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.itens-mais-solicitados'));

        $response->assertSee('240GB', false);
    }

    public function test_shows_variacoes_agrupadas_for_audit(): void
    {
        ItemMaisSolicitado::create([
            'nome_canonico' => 'SSD', 'capacidade' => null, 'total_pedidos' => 8,
            'variacoes_agrupadas' => [
                ['texto' => 'SSD', 'qtd' => 4],
                ['texto' => 'ssd', 'qtd' => 4],
            ],
            'atualizado_em' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.itens-mais-solicitados'));

        $response->assertSee('SSD', false);
        $response->assertSee('ssd', false);
    }

    public function test_shows_empty_state_when_no_ranking_yet(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.itens-mais-solicitados'));

        $response->assertOk();
        $response->assertSee('Nenhum ranking gerado ainda', false);
    }
}
