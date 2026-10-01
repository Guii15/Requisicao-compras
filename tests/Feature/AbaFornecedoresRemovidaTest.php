<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbaFornecedoresRemovidaTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_do_admin_nao_tem_a_aba_fornecedores(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.index'))->assertDontSee('Fornecedores');
    }

    public function test_rota_da_aba_fornecedores_nao_existe_mais(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/fornecedores')->assertNotFound();
        $this->actingAs($admin)->post('/admin/fornecedores/mesclar')->assertNotFound();
    }
}
