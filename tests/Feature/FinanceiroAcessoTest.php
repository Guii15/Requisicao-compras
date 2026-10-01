<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Setor Financeiro: perfil próprio (role "financeiro"), login próprio e acesso só à aba Financeiro.
 */
class FinanceiroAcessoTest extends TestCase
{
    use RefreshDatabase;

    private function financeiro(): User
    {
        return User::factory()->create(['role' => 'financeiro', 'is_admin' => false]);
    }

    public function test_pagina_de_login_do_financeiro_existe_e_aparece_na_escolha_de_perfil(): void
    {
        $this->get(route('login.perfil', 'financeiro'))->assertOk()->assertSee('Financeiro');
        $this->get(route('login'))->assertOk()->assertSee(route('login.perfil', 'financeiro'), false);
    }

    public function test_financeiro_entra_pelo_login_do_financeiro_e_cai_na_aba_financeiro(): void
    {
        $user = $this->financeiro();

        $this->post(route('login.perfil.store', 'financeiro'), ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('financeiro.index'));
    }

    public function test_outros_perfis_nao_entram_pelo_login_do_financeiro(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);

        $this->post(route('login.perfil.store', 'financeiro'), ['email' => $vendedor->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_financeiro_nao_e_vendedor_nem_conferente_nem_entrada(): void
    {
        $user = $this->financeiro();

        $this->assertTrue($user->isFinanceiro());
        $this->assertFalse($user->isVendedor());
        $this->assertFalse($user->isConferente());
        $this->assertFalse($user->isEntrada());
        $this->assertFalse($user->isAdmin());
    }

    public function test_aba_financeiro_so_para_financeiro_e_super_admin(): void
    {
        $super = User::factory()->create(['is_admin' => true, 'email' => 'superadmin.teste@example.com']);
        $this->actingAs($this->financeiro())->get(route('financeiro.index'))->assertOk();
        $this->actingAs($super)->get(route('financeiro.index'))->assertOk();

        foreach ([
            User::factory()->create(['role' => null, 'is_admin' => false]),
            User::factory()->create(['role' => 'conferente']),
            User::factory()->create(['role' => 'entrada']),
            User::factory()->create(['is_admin' => true]),
        ] as $outro) {
            $this->actingAs($outro)->get(route('financeiro.index'))->assertForbidden();
        }
    }

    public function test_visitante_vai_para_o_login(): void
    {
        $this->get(route('financeiro.index'))->assertRedirect(route('login'));
    }

    public function test_financeiro_nao_acessa_as_outras_areas(): void
    {
        $this->actingAs($this->financeiro());

        $this->get(route('requests.index'))->assertForbidden();
        $this->get(route('admin.index'))->assertForbidden();
        $this->get(route('conferencia.index'))->assertForbidden();
        $this->get(route('entrada.index'))->assertForbidden();
    }

    public function test_menu_mostra_financeiro_so_para_quem_pode(): void
    {
        $this->actingAs($this->financeiro())->get(route('financeiro.index'))->assertSee('💰 Financeiro');
        $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))->assertDontSee('💰 Financeiro');
    }

    public function test_super_admin_cria_usuario_com_perfil_financeiro(): void
    {
        $super = User::factory()->create(['is_admin' => true, 'email' => 'superadmin.teste@example.com']);

        $this->actingAs($super)->post(route('admin.users.store'), [
            'name' => 'Maria Financeiro', 'email' => 'maria.fin@example.com',
            'password' => 'senha12345', 'password_confirmation' => 'senha12345', 'perfil' => 'financeiro',
        ])->assertSessionHasNoErrors();

        $criado = User::where('email', 'maria.fin@example.com')->first();
        $this->assertSame('financeiro', $criado->role);
        $this->assertFalse($criado->is_admin);
    }

    public function test_super_admin_muda_o_perfil_de_alguem_para_financeiro(): void
    {
        $super = User::factory()->create(['is_admin' => true, 'email' => 'superadmin.teste@example.com']);
        $user = User::factory()->create(['role' => null]);

        $this->actingAs($super)->patch(route('admin.users.updateRole', $user), ['perfil' => 'financeiro'])->assertSessionHasNoErrors();

        $this->assertSame('financeiro', $user->fresh()->role);
    }

    public function test_tela_de_usuarios_mostra_o_perfil_financeiro(): void
    {
        $super = User::factory()->create(['is_admin' => true, 'email' => 'superadmin.teste@example.com']);
        User::factory()->create(['role' => 'financeiro', 'name' => 'Fulana Fin']);

        $this->actingAs($super)->get(route('admin.users.index'))->assertOk()->assertSee('Fulana Fin')->assertSee('value="financeiro"', false);
    }

    public function test_financeiro_baixa_o_pedido_de_compra(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('pedidos-compra/p.pdf', 'conteudo');
        $item = \App\Models\PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'pedido 1.pdf',
        ]);

        $this->actingAs($this->financeiro())->get(route('admin.compras.pedido', $item))->assertOk();
    }
}
