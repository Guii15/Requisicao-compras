<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush;
use Tests\TestCase;

class PushGatilhosTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $endpoints = [];

    private User $vendedor;
    private User $admin;
    private User $conferente;
    private User $entrada;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->vendedor = $this->usuario('vendedor');
        $this->admin = $this->usuario('admin', null, true);
        $this->conferente = $this->usuario('conferente', 'conferente');
        $this->entrada = $this->usuario('entrada', 'entrada');

        $this->usarEnvioFalso();
    }

    private function usuario(string $nome, ?string $role = null, bool $admin = false): User
    {
        $user = User::factory()->create(['name' => $nome, 'role' => $role, 'is_admin' => $admin]);
        PushSubscription::create(['user_id' => $user->id, 'endpoint' => 'https://push.exemplo/' . $nome, 'public_key' => 'k', 'auth_token' => 'a']);

        return $user;
    }

    private function usarEnvioFalso(): void
    {
        $webPush = Mockery::mock(WebPush::class);
        $webPush->shouldReceive('queueNotification')->andReturnUsing(function (SubscriptionInterface $sub) {
            $this->endpoints[] = $sub->getEndpoint();
        });
        $webPush->shouldReceive('flush')->andReturnUsing(function () {
            yield from [];
        });

        $this->app->instance(PushNotifier::class, new PushNotifier($webPush));
    }

    private function recebidos(): array
    {
        $lista = array_map(fn ($e) => str_replace('https://push.exemplo/', '', $e), $this->endpoints);
        sort($lista);

        return $lista;
    }

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge(['user_id' => $this->vendedor->id, 'status' => 'pendente'], $attrs));
    }

    private function adminMuda(PurchaseRequest $item, string $status)
    {
        return $this->actingAs($this->admin)->patch(route('admin.requests.update', $item), ['status' => $status, 'supplier' => 'Fornecedor Teste']);
    }

    public function test_aprovar_avisa_a_conferencia_e_a_entrada_que_tambem_confere(): void
    {
        $item = $this->item();

        $this->adminMuda($item, 'aprovado')->assertSessionHasNoErrors();

        $this->assertSame(['conferente', 'entrada'], $this->recebidos());
    }

    public function test_salvar_de_novo_um_item_ja_aprovado_nao_reenvia(): void
    {
        $item = $this->item();
        $this->adminMuda($item, 'aprovado');
        $this->endpoints = [];

        $this->adminMuda($item->fresh(), 'aprovado');

        $this->assertSame([], $this->recebidos());
    }

    public function test_rejeitar_ou_manter_pendente_nao_avisa_ninguem(): void
    {
        $item = $this->item();

        $this->adminMuda($item, 'rejeitado');
        $this->adminMuda($item->fresh(), 'pendente');

        $this->assertSame([], $this->recebidos());
    }

    public function test_conferir_ok_avisa_entrada_e_o_vendedor(): void
    {
        $item = $this->item(['status' => 'aprovado', 'status_conferencia' => null]);

        $this->actingAs($this->conferente)->patch(route('conferencia.conferir', $item), [
            'quantidade_recebida' => $item->quantity,
            'foto' => UploadedFile::fake()->image('p.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['entrada', 'vendedor'], $this->recebidos());
    }

    public function test_conferir_divergente_avisa_so_o_vendedor(): void
    {
        $item = $this->item(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque']);

        $this->actingAs($this->conferente)->patch(route('conferencia.conferir', $item), [
            'quantidade_recebida' => 0,
            'foto' => UploadedFile::fake()->image('p.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'veio quebrado',
            'acao' => 'salvar',
        ])->assertSessionHasNoErrors();

        $this->assertSame(['vendedor'], $this->recebidos());
    }

    public function test_dar_entrada_avisa_o_vendedor(): void
    {
        $item = $this->item(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 2]);

        $this->actingAs($this->entrada)->patch(route('entrada.darEntrada', $item), [
            'vendedor_destino' => 'Fulano',
            'quantidade_entrada' => 2,
        ])->assertSessionHasNoErrors();

        $this->assertSame(['vendedor'], $this->recebidos());
    }

    public function test_falha_do_push_nao_quebra_o_fluxo(): void
    {
        $webPush = Mockery::mock(WebPush::class);
        $webPush->shouldReceive('queueNotification')->andThrow(new \RuntimeException('provedor fora do ar'));
        $this->app->instance(PushNotifier::class, new PushNotifier($webPush));
        $item = $this->item();

        $this->adminMuda($item, 'aprovado')->assertSessionHasNoErrors();

        $this->assertSame('aprovado', $item->fresh()->status);
    }
}
