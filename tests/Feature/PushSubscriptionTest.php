<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'chave-publica-do-aparelho', 'auth' => 'segredo-auth'],
        ], $overrides);
    }

    public function test_guest_nao_pode_se_inscrever(): void
    {
        $this->postJson(route('push.subscribe'), $this->payload())->assertUnauthorized();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_usuario_logado_salva_a_inscricao(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withHeader('User-Agent', 'Chrome de Teste')
            ->postJson(route('push.subscribe'), $this->payload())
            ->assertOk();

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'public_key' => 'chave-publica-do-aparelho',
            'auth_token' => 'segredo-auth',
            'user_agent' => 'Chrome de Teste',
        ]);
    }

    public function test_inscrever_duas_vezes_o_mesmo_aparelho_nao_duplica(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('push.subscribe'), $this->payload())->assertOk();
        $this->actingAs($user)->postJson(route('push.subscribe'), $this->payload(['keys' => ['p256dh' => 'nova-chave', 'auth' => 'novo-auth']]))->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['public_key' => 'nova-chave', 'auth_token' => 'novo-auth']);
    }

    public function test_aparelho_passa_para_o_usuario_que_logou_por_ultimo(): void
    {
        $primeiro = User::factory()->create();
        $segundo = User::factory()->create();

        $this->actingAs($primeiro)->postJson(route('push.subscribe'), $this->payload())->assertOk();
        $this->actingAs($segundo)->postJson(route('push.subscribe'), $this->payload())->assertOk();

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertSame($segundo->id, PushSubscription::first()->user_id);
    }

    public function test_rejeita_inscricao_invalida(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('push.subscribe'), ['endpoint' => 'nao-e-url'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_cancelar_remove_so_a_inscricao_da_propria_pessoa(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        PushSubscription::create(['user_id' => $dono->id, 'endpoint' => 'https://push.exemplo/1', 'public_key' => 'a', 'auth_token' => 'b']);

        $this->actingAs($outro)->deleteJson(route('push.unsubscribe'), ['endpoint' => 'https://push.exemplo/1'])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 1);

        $this->actingAs($dono)->deleteJson(route('push.unsubscribe'), ['endpoint' => 'https://push.exemplo/1'])->assertOk();
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_apagar_usuario_apaga_as_inscricoes(): void
    {
        $user = User::factory()->create();
        PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://push.exemplo/2', 'public_key' => 'a', 'auth_token' => 'b']);

        $user->delete();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }
}
