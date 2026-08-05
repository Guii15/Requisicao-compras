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
}
