<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuFinanceiroTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_nao_ve_o_link_do_financeiro_no_menu(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertDontSee(route('financeiro.index'), false);
    }

    public function test_usuario_do_financeiro_continua_vendo_o_link(): void
    {
        $fin = User::factory()->create(['role' => 'financeiro']);

        $this->actingAs($fin)->get(route('financeiro.index'))->assertOk()->assertSee(route('financeiro.index'), false);
    }
}
