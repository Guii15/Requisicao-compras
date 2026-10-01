<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\PushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Minishlink\WebPush\SubscriptionInterface;
use Minishlink\WebPush\WebPush;
use Mockery;
use Tests\TestCase;

/**
 * Chegou só parte da compra (ex: 50 de 100): a conferência marca "aguardar restante". O que chegou
 * segue para a Entrada; o que falta vira um item novo, que será conferido (e receberá entrada)
 * quando chegar.
 */
class RecebimentoParcialTest extends TestCase
{
    use RefreshDatabase;

    private User $conferente;
    private User $entrada;
    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->conferente = User::factory()->create(['role' => 'conferente', 'name' => 'Carlos Conferente']);
        $this->entrada = User::factory()->create(['role' => 'entrada']);
        $this->vendedor = User::factory()->create(['role' => null]);
    }

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'user_id' => $this->vendedor->id, 'status' => 'aprovado', 'status_conferencia' => null,
            'quantity' => 100, 'product_name' => 'Cabo HDMI', 'supplier' => 'Kabum', 'tipo_entrega' => 'estoque',
            'preco_unitario' => 10, 'valor' => 1000, 'status_coleta' => 'coletado', 'data_coleta' => now(), 'coletado_por' => 'Maria',
        ], $attrs));
    }

    private function aguardarRestante(PurchaseRequest $item, int $recebida, array $extra = [])
    {
        return $this->actingAs($this->conferente)->patch(route('conferencia.conferir', $item), array_merge([
            'quantidade_recebida' => $recebida,
            'foto' => UploadedFile::fake()->image('lote.jpg'),
            'resultado' => 'divergente',
            'acao' => 'aguardar_restante',
        ], $extra));
    }

    private function darEntrada(PurchaseRequest $item, int $quantidade)
    {
        return $this->actingAs($this->entrada)->patch(route('entrada.darEntrada', $item), [
            'vendedor_destino' => 'Fulano', 'quantidade_entrada' => $quantidade,
        ]);
    }

    public function test_aguardar_restante_divide_o_item_em_recebido_e_restante(): void
    {
        $item = $this->item();

        $this->aguardarRestante($item, 50)->assertSessionHasNoErrors()->assertSessionHas('success');

        $pai = $item->fresh();
        $this->assertSame(50, $pai->quantity);
        $this->assertSame(50, $pai->quantidade_recebida);
        $this->assertSame(100, $pai->quantidade_original);
        $this->assertSame('conferido_ok', $pai->status_conferencia);
        $this->assertSame($this->conferente->id, $pai->conferente_id);
        $this->assertSame(1, $pai->fotosConferencia()->count());
        $this->assertStringContainsString('Recebimento parcial', $pai->observacao_conferencia);

        $filho = PurchaseRequest::where('restante_de_id', $pai->id)->firstOrFail();
        $this->assertSame(50, $filho->quantity);
        $this->assertSame(100, $filho->quantidade_original);
        $this->assertSame($pai->grupo_id, $filho->grupo_id);
        $this->assertSame($this->vendedor->id, $filho->user_id);
        $this->assertSame('Cabo HDMI', $filho->product_name);
        $this->assertSame('Kabum', $filho->supplier);
        $this->assertSame('aprovado', $filho->status);
        $this->assertNull($filho->status_conferencia);
        $this->assertNull($filho->quantidade_recebida);
        $this->assertNull($filho->entrada_concluida_em);
        $this->assertNull($filho->obs_entrada);
        $this->assertNull($filho->valor);
        $this->assertSame('aguardando', $filho->status_coleta);
        $this->assertNull($filho->data_coleta);
    }

    public function test_observacao_informada_e_mantida(): void
    {
        $item = $this->item();

        $this->aguardarRestante($item, 50, ['observacao_conferencia' => 'O resto vem na terça'])->assertSessionHasNoErrors();

        $this->assertSame('O resto vem na terça', $item->fresh()->observacao_conferencia);
    }

    public function test_o_que_chegou_vai_para_a_entrada_e_o_restante_so_depois_de_conferido(): void
    {
        $item = $this->item();
        $this->aguardarRestante($item, 50);
        $filho = PurchaseRequest::where('restante_de_id', $item->id)->firstOrFail();

        $lista = $this->actingAs($this->entrada)->get(route('entrada.index'))->assertOk();
        $this->assertSame(1, substr_count($lista->getContent(), 'id="modal-entrada-' . $item->id . '"'));
        $this->assertSame(0, substr_count($lista->getContent(), 'id="modal-entrada-' . $filho->id . '"'));

        $this->darEntrada($item, 50)->assertSessionHasNoErrors();
        $this->assertNotNull($item->fresh()->entrada_concluida_em);

        $this->darEntrada($filho, 50)->assertSessionHas('aviso');
        $this->assertNull($filho->fresh()->entrada_concluida_em);
    }

    public function test_restante_aparece_na_conferencia_e_fecha_o_ciclo_quando_chega(): void
    {
        $item = $this->item();
        $this->aguardarRestante($item, 50);
        $this->darEntrada($item, 50);
        $filho = PurchaseRequest::where('restante_de_id', $item->id)->firstOrFail();

        $this->actingAs($this->conferente)->get(route('conferencia.index'))->assertOk()->assertSee('modal-conferir-' . $filho->id, false);

        $this->actingAs($this->conferente)->patch(route('conferencia.conferir', $filho), [
            'quantidade_recebida' => 50, 'foto' => UploadedFile::fake()->image('resto.jpg'), 'resultado' => 'ok', 'acao' => 'salvar',
        ])->assertSessionHasNoErrors();
        $this->darEntrada($filho, 50)->assertSessionHasNoErrors();

        $todos = PurchaseRequest::where('grupo_id', $item->grupo_id)->get();
        $this->assertCount(2, $todos);
        $this->assertSame(100, $todos->sum('quantidade_entrada'));
        $this->assertTrue($todos->every(fn ($r) => $r->entrada_concluida_em !== null));
    }

    public function test_restante_pode_chegar_parcial_de_novo_em_cadeia(): void
    {
        $item = $this->item();
        $this->aguardarRestante($item, 50);
        $segundo = PurchaseRequest::where('restante_de_id', $item->id)->firstOrFail();

        $this->aguardarRestante($segundo, 30)->assertSessionHasNoErrors();

        $segundo->refresh();
        $terceiro = PurchaseRequest::where('restante_de_id', $segundo->id)->firstOrFail();
        $this->assertSame(30, $segundo->quantity);
        $this->assertSame(20, $terceiro->quantity);
        $this->assertSame([100, 100, 100], [$item->fresh()->quantidade_original, $segundo->quantidade_original, $terceiro->quantidade_original]);
        $this->assertSame(100, $item->fresh()->quantity + $segundo->quantity + $terceiro->quantity);
    }

    public function test_so_aceita_aguardar_restante_com_quantidade_entre_1_e_menos_que_a_solicitada(): void
    {
        $item = $this->item();

        foreach ([0, 100, 150] as $invalida) {
            $this->aguardarRestante($item, $invalida)->assertSessionHasErrors('quantidade_recebida');
        }

        $this->assertSame(1, PurchaseRequest::count());
        $this->assertNull($item->fresh()->status_conferencia);
        $this->assertSame(100, $item->fresh()->quantity);
    }

    public function test_conferencia_divergente_comum_continua_igual_e_nao_cria_restante(): void
    {
        $item = $this->item();

        $this->actingAs($this->conferente)->patch(route('conferencia.conferir', $item), [
            'quantidade_recebida' => 50, 'foto' => UploadedFile::fake()->image('a.jpg'), 'resultado' => 'divergente',
            'observacao_conferencia' => 'veio metade', 'acao' => 'salvar',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, PurchaseRequest::count());
        $this->assertSame('divergente', $item->fresh()->status_conferencia);
        $this->assertNull($item->fresh()->quantidade_original);
    }

    public function test_so_quem_confere_pode_aguardar_restante(): void
    {
        $item = $this->item();

        $this->actingAs(User::factory()->create(['role' => null]))->patch(route('conferencia.conferir', $item), [
            'quantidade_recebida' => 50, 'foto' => UploadedFile::fake()->image('a.jpg'), 'resultado' => 'divergente', 'acao' => 'aguardar_restante',
        ])->assertForbidden();

        $this->assertSame(1, PurchaseRequest::count());
    }

    public function test_restante_entra_na_aba_coleta_como_aguardando(): void
    {
        $item = $this->item();
        $this->aguardarRestante($item, 50);
        $filho = PurchaseRequest::where('restante_de_id', $item->id)->firstOrFail();

        $this->actingAs($this->conferente)->get(route('conferencia.index', ['aba' => 'coleta', 'resultado' => 'aguardando']))
            ->assertSee('Cabo HDMI')
            ->assertSee('abrirModalColeta(' . $filho->id . ')', false);
        $this->assertSame('coletado', $item->fresh()->status_coleta);
    }

    public function test_telas_mostram_que_e_recebimento_parcial(): void
    {
        $item = $this->item();
        $this->aguardarRestante($item, 50);
        $this->darEntrada($item, 50);
        $filho = PurchaseRequest::where('restante_de_id', $item->id)->firstOrFail();

        $this->actingAs($this->conferente)->get(route('conferencia.index'))
            ->assertSee('Parcial · 50 de 100 un.')
            ->assertSee('restante da requisição #' . $item->id);
        $this->actingAs($this->entrada)->get(route('entrada.index', ['aba' => 'concluidas']))->assertSee('Parcial · 50 de 100 un.');
        $this->actingAs($this->vendedor)->get(route('requests.index'))->assertSee('Parcial · 50 de 100 un.');
        $this->assertNotNull($filho);
    }

    public function test_modal_da_conferencia_tem_o_botao_aguardar_restante(): void
    {
        $this->item();

        $this->actingAs($this->conferente)->get(route('conferencia.index'))
            ->assertSee('Aguardar restante')
            ->assertSee('value="aguardar_restante"', false);
    }

    public function test_substituir_o_pedido_de_compra_do_restante_nao_apaga_o_arquivo_do_original(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $item = $this->item(['pedido_compra_path' => 'pedidos-compra/pedido-original.pdf', 'pedido_compra_nome' => 'pedido.pdf']);
        Storage::disk('local')->put('pedidos-compra/pedido-original.pdf', 'conteudo');
        $this->aguardarRestante($item, 50);
        $filho = PurchaseRequest::where('restante_de_id', $item->id)->firstOrFail();
        $this->assertSame('pedidos-compra/pedido-original.pdf', $filho->pedido_compra_path);

        $this->actingAs($admin)->patch(route('admin.requests.update', $filho), [
            'status' => 'aprovado', 'pedido_compra' => UploadedFile::fake()->create('novo.pdf', 10, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $this->assertNotSame('pedidos-compra/pedido-original.pdf', $filho->fresh()->pedido_compra_path);
        Storage::disk('local')->assertExists('pedidos-compra/pedido-original.pdf');
    }

    public function test_aviso_push_ao_vendedor_explica_que_e_parcial(): void
    {
        $item = $this->item();
        PushSubscription::create(['user_id' => $this->vendedor->id, 'endpoint' => 'https://push.exemplo/vendedor', 'public_key' => 'k', 'auth_token' => 'a']);
        $this->aguardarRestante($item, 50);

        $mensagens = [];
        $webPush = Mockery::mock(WebPush::class);
        $webPush->shouldReceive('queueNotification')->andReturnUsing(function (SubscriptionInterface $s, ?string $payload) use (&$mensagens) {
            $mensagens[] = json_decode($payload, true);
        });
        $webPush->shouldReceive('flush')->andReturnUsing(function () {
            yield from [];
        });

        (new PushNotifier($webPush))->conferida($item->fresh());

        $this->assertCount(1, $mensagens);
        $this->assertStringContainsString('50 de 100', $mensagens[0]['body']);
        $this->assertStringContainsString('restante', $mensagens[0]['body']);
    }
}
