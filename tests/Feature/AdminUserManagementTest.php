<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validStoreUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuário Teste',
            'email' => 'usuario.teste@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'perfil' => 'vendedor',
        ], $overrides);
    }

    public function test_store_user_with_perfil_admin_sets_is_admin_true_and_role_null(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'novo.admin@example.com',
            'perfil' => 'admin',
        ]));

        $criado = User::where('email', 'novo.admin@example.com')->first();
        $this->assertNotNull($criado);
        $this->assertTrue($criado->is_admin);
        $this->assertNull($criado->role);
    }

    public function test_store_user_with_perfil_conferente_sets_role_conferente(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'novo.conferente@example.com',
            'perfil' => 'conferente',
        ]));

        $criado = User::where('email', 'novo.conferente@example.com')->first();
        $this->assertFalse($criado->is_admin);
        $this->assertSame('conferente', $criado->role);
    }

    public function test_store_user_with_perfil_entrada_sets_role_entrada(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'nova.entrada@example.com',
            'perfil' => 'entrada',
        ]));

        $criado = User::where('email', 'nova.entrada@example.com')->first();
        $this->assertFalse($criado->is_admin);
        $this->assertSame('entrada', $criado->role);
    }

    public function test_store_user_with_perfil_vendedor_sets_is_admin_false_and_role_null(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'novo.vendedor@example.com',
            'perfil' => 'vendedor',
        ]));

        $criado = User::where('email', 'novo.vendedor@example.com')->first();
        $this->assertFalse($criado->is_admin);
        $this->assertNull($criado->role);
    }

    public function test_store_user_rejects_missing_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $payload = $this->validStoreUserPayload();
        unset($payload['perfil']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $payload);

        $response->assertSessionHasErrors('perfil');
    }

    public function test_store_user_rejects_invalid_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'invalido@example.com',
            'perfil' => 'gerente',
        ]));

        $response->assertSessionHasErrors('perfil');
        $this->assertNull(User::where('email', 'invalido@example.com')->first());
    }

    public function test_update_role_changes_existing_user_to_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'admin',
        ]);

        $response->assertRedirect();
        $fresh = $alvo->fresh();
        $this->assertTrue($fresh->is_admin);
        $this->assertNull($fresh->role);
    }

    public function test_update_role_changes_existing_user_to_conferente(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'conferente',
        ]);

        $fresh = $alvo->fresh();
        $this->assertFalse($fresh->is_admin);
        $this->assertSame('conferente', $fresh->role);
    }

    public function test_update_role_changes_existing_user_to_entrada(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'entrada',
        ]);

        $fresh = $alvo->fresh();
        $this->assertFalse($fresh->is_admin);
        $this->assertSame('entrada', $fresh->role);
    }

    public function test_update_role_changes_existing_user_back_to_vendedor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => 'conferente']);

        $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'vendedor',
        ]);

        $fresh = $alvo->fresh();
        $this->assertFalse($fresh->is_admin);
        $this->assertNull($fresh->role);
    }

    public function test_update_role_rejects_invalid_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'gerente',
        ]);

        $response->assertSessionHasErrors('perfil');
        $this->assertNull($alvo->fresh()->role);
    }

    public function test_update_role_blocks_admin_from_changing_own_profile(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $admin), [
            'perfil' => 'conferente',
        ]);

        $response->assertRedirect();
        $fresh = $admin->fresh();
        $this->assertTrue($fresh->is_admin);
        $this->assertNull($fresh->role);
    }

    public function test_update_role_requires_admin_access(): void
    {
        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($vendedor)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'conferente',
        ]);

        $response->assertForbidden();
        $this->assertNull($alvo->fresh()->role);
    }

    public function test_users_index_shows_correct_badge_for_each_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Logado']);
        User::factory()->create(['is_admin' => false, 'role' => 'conferente', 'name' => 'Fulano Conferente']);
        User::factory()->create(['is_admin' => false, 'role' => 'entrada', 'name' => 'Fulano Entrada']);
        User::factory()->create(['is_admin' => false, 'role' => null, 'name' => 'Fulano Vendedor']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee('>Admin<', false);
        $response->assertSee('>Conferente<', false);
        $response->assertSee('>Entrada<', false);
        $response->assertSee('>Vendedor<', false);
    }

    public function test_users_index_does_not_show_perfil_button_on_own_row(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $html = $response->getContent();
        $this->assertStringNotContainsString("modal-perfil-{$admin->id}", $html);
    }

    public function test_users_index_shows_perfil_button_on_other_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $outro = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee("modal-perfil-{$outro->id}", false);
    }

    public function test_create_user_form_has_perfil_select_with_four_options(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee('name="perfil"', false);
        $response->assertSee('value="vendedor"', false);
        $response->assertSee('value="conferente"', false);
        $response->assertSee('value="entrada"', false);
        $response->assertSee('value="admin"', false);
    }

    /**
     * Extrai apenas o <select name="perfil"> do modal "Editar Perfil" da linha do
     * usuário informado, para que as asserções de "selected" não deem falso positivo
     * por causa do <select> de outra linha ou do formulário de "Novo Usuário".
     */
    private function extrairBlocoModalPerfil(string $html, int $userId): string
    {
        $marcador = 'id="modal-perfil-' . $userId . '"';
        $inicio = strpos($html, $marcador);
        $this->assertNotFalse($inicio, "Modal de perfil do usuário {$userId} não foi encontrado na página.");

        $inicioSelect = strpos($html, '<select name="perfil"', $inicio);
        $this->assertNotFalse($inicioSelect, "Select de perfil do usuário {$userId} não foi encontrado na página.");

        $fimSelect = strpos($html, '</select>', $inicioSelect);
        $this->assertNotFalse($fimSelect, "Fechamento do select de perfil do usuário {$userId} não foi encontrado na página.");

        return substr($html, $inicioSelect, $fimSelect - $inicioSelect);
    }

    public function test_edit_perfil_modal_preselects_current_perfil_for_conferente_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Um']);
        $conferente = User::factory()->create(['is_admin' => false, 'role' => 'conferente', 'name' => 'Beltrano Conferente']);
        User::factory()->create(['is_admin' => false, 'role' => 'entrada', 'name' => 'Ciclano Entrada']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $bloco = $this->extrairBlocoModalPerfil($response->getContent(), $conferente->id);

        $this->assertStringContainsString('value="conferente" selected', $bloco);
        $this->assertStringNotContainsString('value="vendedor" selected', $bloco);
        $this->assertStringNotContainsString('value="entrada" selected', $bloco);
        $this->assertStringNotContainsString('value="admin" selected', $bloco);
    }

    public function test_edit_perfil_modal_preselects_current_perfil_for_admin_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Logado']);
        $outroAdmin = User::factory()->create(['is_admin' => true, 'role' => null, 'name' => 'Outro Admin']);
        User::factory()->create(['is_admin' => false, 'role' => 'entrada', 'name' => 'Fulano Entrada']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $bloco = $this->extrairBlocoModalPerfil($response->getContent(), $outroAdmin->id);

        $this->assertStringContainsString('value="admin" selected', $bloco);
        $this->assertStringNotContainsString('value="vendedor" selected', $bloco);
        $this->assertStringNotContainsString('value="conferente" selected', $bloco);
        $this->assertStringNotContainsString('value="entrada" selected', $bloco);
    }

    public function test_edit_perfil_modal_preselects_current_perfil_for_entrada_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Logado']);
        $entrada = User::factory()->create(['is_admin' => false, 'role' => 'entrada', 'name' => 'Fulano Entrada']);
        User::factory()->create(['is_admin' => false, 'role' => 'conferente', 'name' => 'Fulano Conferente']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $bloco = $this->extrairBlocoModalPerfil($response->getContent(), $entrada->id);

        $this->assertStringContainsString('value="entrada" selected', $bloco);
        $this->assertStringNotContainsString('value="vendedor" selected', $bloco);
        $this->assertStringNotContainsString('value="conferente" selected', $bloco);
        $this->assertStringNotContainsString('value="admin" selected', $bloco);
    }

    public function test_edit_perfil_modal_preselects_current_perfil_for_vendedor_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Logado']);
        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null, 'name' => 'Fulano Vendedor']);
        User::factory()->create(['is_admin' => false, 'role' => 'conferente', 'name' => 'Fulano Conferente']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $bloco = $this->extrairBlocoModalPerfil($response->getContent(), $vendedor->id);

        $this->assertStringContainsString('value="vendedor" selected', $bloco);
        $this->assertStringNotContainsString('value="conferente" selected', $bloco);
        $this->assertStringNotContainsString('value="entrada" selected', $bloco);
        $this->assertStringNotContainsString('value="admin" selected', $bloco);
    }
}
