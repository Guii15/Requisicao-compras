<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotifier;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class PushNotifierTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{endpoint: string, payload: array}> */
    private array $enviados = [];

    private function fakeWebPush(array $endpointsExpirados = []): WebPush
    {
        $webPush = Mockery::mock(WebPush::class);

        $webPush->shouldReceive('queueNotification')->andReturnUsing(function (SubscriptionInterface $sub, ?string $payload) {
            $this->enviados[] = ['endpoint' => $sub->getEndpoint(), 'payload' => json_decode($payload, true)];
        });

        $webPush->shouldReceive('flush')->andReturnUsing(function () use ($endpointsExpirados) {
            foreach ($endpointsExpirados as $endpoint) {
                yield new MessageSentReport(new Request('POST', $endpoint), new Response(410), false, 'Gone');
            }
        });

        return $webPush;
    }

    private function notifier(array $endpointsExpirados = []): PushNotifier
    {
        return new PushNotifier($this->fakeWebPush($endpointsExpirados));
    }

    private function usuarioComAparelho(string $nome, ?string $role = null, bool $admin = false): User
    {
        $user = User::factory()->create(['name' => $nome, 'role' => $role, 'is_admin' => $admin]);
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://push.exemplo/' . $nome,
            'public_key' => 'chave-' . $nome,
            'auth_token' => 'auth-' . $nome,
        ]);

        return $user;
    }

    private function endpointsEnviados(): array
    {
        $endpoints = array_column($this->enviados, 'endpoint');
        sort($endpoints);

        return $endpoints;
    }

    public function test_envia_para_todos_os_aparelhos_dos_usuarios_pedidos(): void
    {
        $ana = $this->usuarioComAparelho('ana');
        PushSubscription::create(['user_id' => $ana->id, 'endpoint' => 'https://push.exemplo/ana-celular', 'public_key' => 'x', 'auth_token' => 'y']);
        $this->usuarioComAparelho('bia');

        $this->notifier()->enviar(collect([$ana]), 'Titulo', 'Texto', '/destino', 'minha-tag');

        $this->assertSame(['https://push.exemplo/ana', 'https://push.exemplo/ana-celular'], $this->endpointsEnviados());
        $this->assertSame(
            ['title' => 'Titulo', 'body' => 'Texto', 'url' => '/destino', 'tag' => 'minha-tag'],
            $this->enviados[0]['payload']
        );
    }

    public function test_remove_inscricao_expirada(): void
    {
        $ana = $this->usuarioComAparelho('ana');
        $bia = $this->usuarioComAparelho('bia');

        $this->notifier(['https://push.exemplo/ana'])->enviar(collect([$ana, $bia]), 'T', 'B', '/');

        $this->assertDatabaseMissing('push_subscriptions', ['endpoint' => 'https://push.exemplo/ana']);
        $this->assertDatabaseHas('push_subscriptions', ['endpoint' => 'https://push.exemplo/bia']);
    }

    public function test_sem_chaves_vapid_nao_envia_nem_quebra(): void
    {
        $ana = $this->usuarioComAparelho('ana');

        (new PushNotifier(null))->enviar(collect([$ana]), 'T', 'B', '/');

        $this->assertSame([], $this->enviados);
    }

    public function test_falha_no_envio_e_engolida(): void
    {
        $ana = $this->usuarioComAparelho('ana');
        $webPush = Mockery::mock(WebPush::class);
        $webPush->shouldReceive('queueNotification')->andThrow(new \RuntimeException('provedor fora do ar'));

        (new PushNotifier($webPush))->enviar(collect([$ana]), 'T', 'B', '/');

        $this->assertTrue(true);
    }

    public function test_aprovada_avisa_so_a_conferencia(): void
    {
        $this->usuarioComAparelho('conferente1', 'conferente');
        $this->usuarioComAparelho('entrada1', 'entrada');
        $this->usuarioComAparelho('admin1', null, true);
        $dono = $this->usuarioComAparelho('vendedor1');
        $item = PurchaseRequest::factory()->create(['user_id' => $dono->id, 'status' => 'aprovado', 'product_name' => 'Amortecedor', 'quantity' => 4]);

        $this->notifier()->aprovada($item);

        $this->assertSame(['https://push.exemplo/conferente1'], $this->endpointsEnviados());
        $payload = $this->enviados[0]['payload'];
        $this->assertSame('Nova requisição aprovada', $payload['title']);
        $this->assertStringContainsString('Amortecedor', $payload['body']);
        $this->assertSame('/conferencia', $payload['url']);
        $this->assertSame('conferencia-' . $item->grupo_id, $payload['tag']);
    }

    public function test_conferida_ok_avisa_entrada_e_o_dono(): void
    {
        $this->usuarioComAparelho('conferente1', 'conferente');
        $this->usuarioComAparelho('entrada1', 'entrada');
        $dono = $this->usuarioComAparelho('vendedor1');
        $item = PurchaseRequest::factory()->create(['user_id' => $dono->id, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Filtro']);

        $this->notifier()->conferida($item);

        $this->assertSame(['https://push.exemplo/entrada1', 'https://push.exemplo/vendedor1'], $this->endpointsEnviados());
    }

    public function test_conferida_avancado_mesmo_assim_tambem_avisa_a_entrada(): void
    {
        $this->usuarioComAparelho('entrada1', 'entrada');
        $dono = $this->usuarioComAparelho('vendedor1');
        $item = PurchaseRequest::factory()->create(['user_id' => $dono->id, 'status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim']);

        $this->notifier()->conferida($item);

        $this->assertSame(['https://push.exemplo/entrada1', 'https://push.exemplo/vendedor1'], $this->endpointsEnviados());
    }

    public function test_conferida_divergente_avisa_so_o_dono(): void
    {
        $this->usuarioComAparelho('entrada1', 'entrada');
        $dono = $this->usuarioComAparelho('vendedor1');
        $item = PurchaseRequest::factory()->create(['user_id' => $dono->id, 'status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Pneu']);

        $this->notifier()->conferida($item);

        $this->assertSame(['https://push.exemplo/vendedor1'], $this->endpointsEnviados());
        $this->assertSame('Conferência com divergência', $this->enviados[0]['payload']['title']);
    }

    public function test_entrada_concluida_avisa_o_dono(): void
    {
        $this->usuarioComAparelho('entrada1', 'entrada');
        $dono = $this->usuarioComAparelho('vendedor1');
        $item = PurchaseRequest::factory()->create(['user_id' => $dono->id, 'status' => 'aprovado', 'product_name' => 'Graxa']);

        $this->notifier()->entradaConcluida($item);

        $this->assertSame(['https://push.exemplo/vendedor1'], $this->endpointsEnviados());
        $this->assertSame('Entrada concluída', $this->enviados[0]['payload']['title']);
        $this->assertStringContainsString('Graxa', $this->enviados[0]['payload']['body']);
    }
}
