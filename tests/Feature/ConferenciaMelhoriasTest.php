<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Melhorias da Conferência/Entrada: obs do admin no quadro de conferir, pedido de compra nas divergências,
 * várias fotos (ex.: código de barras) e a entrada visível (só leitura) na Conferência.
 */
class ConferenciaMelhoriasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque', 'quantity' => 10, 'product_name' => 'Cabo Teste',
        ], $attrs));
    }

    private function fotos(PurchaseRequest $item, array $caminhos): void
    {
        foreach ($caminhos as $c) {
            ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => $c, 'nome_original' => basename($c)]);
        }
    }

    private function conferente(): User
    {
        return User::factory()->create(['role' => 'conferente']);
    }

    private function conferir(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->conferente())->patch(route('conferencia.conferir', $item), array_merge([
            'quantidade_recebida' => 10, 'foto' => UploadedFile::fake()->image('principal.jpg'), 'resultado' => 'ok', 'acao' => 'salvar',
        ], $extra));
    }

    // ---------- 1. obs do admin no quadro Conferir Item ----------

    public function test_obs_do_admin_aparece_tambem_dentro_do_quadro_de_conferir(): void
    {
        $this->item(['admin_note' => 'Nota do admin XYZ']);

        $html = $this->actingAs($this->conferente())->get(route('conferencia.index'))->assertOk()->getContent();

        // cartão do item + janela Conferir Item (uma marcação só para PC e celular)
        $this->assertSame(2, substr_count($html, 'Nota do admin XYZ'));
        $this->assertSame(2, substr_count($html, '>ADMIN</span>'));
    }

    // ---------- 2. pedido de compra nas divergências ----------

    public function test_divergencia_mostra_o_pedido_de_compra_no_card_e_no_quadro_de_dar_entrada(): void
    {
        // Sem data nem preço de propósito: o pedido anexado aparece mesmo antes de registrar os dados da compra.
        $item = $this->item(['status_conferencia' => 'divergente', 'quantidade_recebida' => 8, 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'pedido 77.pdf']);

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index', ['aba' => 'divergencias']))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, route('admin.compras.pedido', $item)));
        $this->assertStringContainsString('Pedido de compra', $html);
        $this->assertStringContainsString('pedido 77.pdf', $html);
    }

    public function test_divergencia_sem_pedido_nao_mostra_o_link(): void
    {
        $this->item(['status_conferencia' => 'divergente', 'quantidade_recebida' => 8]);

        $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index', ['aba' => 'divergencias']))->assertDontSee('Pedido de compra');
    }

    public function test_entrada_consegue_baixar_o_pedido(): void
    {
        Storage::disk('local')->put('pedidos-compra/p.pdf', 'conteudo');
        $item = $this->item(['status_conferencia' => 'divergente', 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);

        $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('admin.compras.pedido', $item))->assertOk();
    }

    // ---------- 3. mais de uma foto ----------

    public function test_conferir_com_fotos_extras_guarda_todas(): void
    {
        $item = $this->item();

        $this->conferir($item, ['fotos_extras' => [UploadedFile::fake()->image('barras1.jpg'), UploadedFile::fake()->image('barras2.png')]])
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $fotos = $item->fresh()->fotosConferencia;
        $this->assertCount(3, $fotos);
        $this->assertSame('principal.jpg', $fotos->first()->nome_original);
        foreach ($fotos as $foto) {
            Storage::disk('public')->assertExists($foto->caminho_arquivo);
        }
    }

    public function test_fotos_extras_sao_opcionais(): void
    {
        $item = $this->item();

        $this->conferir($item)->assertSessionHasNoErrors();

        $this->assertCount(1, $item->fresh()->fotosConferencia);
    }

    public function test_fotos_extras_precisam_ser_imagens_e_no_maximo_cinco(): void
    {
        $item = $this->item();

        $this->conferir($item, ['fotos_extras' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]])->assertSessionHasErrors('fotos_extras.0');

        $seis = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 6));
        $this->conferir($item, ['fotos_extras' => $seis])->assertSessionHasErrors('fotos_extras');

        $this->assertNull($item->fresh()->status_conferencia);
        $this->assertCount(0, $item->fresh()->fotosConferencia);
    }

    public function test_foto_principal_continua_obrigatoria(): void
    {
        $item = $this->item();

        $this->actingAs($this->conferente())->patch(route('conferencia.conferir', $item), [
            'quantidade_recebida' => 10, 'resultado' => 'ok', 'acao' => 'salvar',
            'fotos_extras' => [UploadedFile::fake()->image('so-extra.jpg')],
        ])->assertSessionHasErrors('foto');
    }

    public function test_quadro_de_conferir_tem_o_campo_de_fotos_extras(): void
    {
        $this->item();

        $html = $this->actingAs($this->conferente())->get(route('conferencia.index'))->getContent();

        $this->assertSame(1, substr_count($html, 'name="fotos_extras[]"')); // uma janela só (a do celular saiu)
        $this->assertStringContainsString('Fotos extras', $html);
        $this->assertStringContainsString('(opcional — ex.: código de barras, até 5)', $html);
    }

    public function test_entrada_mostra_todas_as_fotos(): void
    {
        $item = $this->item(['status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 10]);
        $this->fotos($item, ['conferencia/a.jpg', 'conferencia/b.jpg', 'conferencia/c.jpg']);

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))->getContent();

        foreach (['a', 'b', 'c'] as $letra) {
            $this->assertStringContainsString("/conferencia/{$letra}.jpg", $html);
        }
    }

    public function test_divergencias_mostra_todas_as_fotos(): void
    {
        $item = $this->item(['status_conferencia' => 'divergente', 'quantidade_recebida' => 8]);
        $this->fotos($item, ['conferencia/a.jpg', 'conferencia/b.jpg']);

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index', ['aba' => 'divergencias']))->getContent();

        $this->assertStringContainsString('/conferencia/a.jpg', $html);
        $this->assertStringContainsString('/conferencia/b.jpg', $html);
    }

    public function test_pendencias_do_admin_mostra_todas_as_fotos(): void
    {
        $item = $this->item(['status_conferencia' => 'divergente', 'quantidade_recebida' => 8]);
        $this->fotos($item, ['conferencia/a.jpg', 'conferencia/b.jpg']);

        $html = $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('pendencias.index'))->getContent();

        $this->assertStringContainsString('/conferencia/a.jpg', $html);
        $this->assertStringContainsString('/conferencia/b.jpg', $html);
    }

    public function test_vendedor_ve_todas_as_fotos_no_quadro_da_conferencia(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);
        $item = $this->item(['user_id' => $vendedor->id, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 10]);
        $this->fotos($item, ['conferencia/a.jpg', 'conferencia/b.jpg']);

        $html = $this->actingAs($vendedor)->get(route('requests.index'))->getContent();

        $this->assertStringContainsString('/conferencia/a.jpg', $html);
        $this->assertStringContainsString('/conferencia/b.jpg', $html);
    }

    // ---------- 4. entrada visível na Conferência (só leitura) ----------

    public function test_conferencia_mostra_a_entrada_realizada_so_para_leitura(): void
    {
        $item = $this->item([
            'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 10,
            'entrada_concluida_em' => '2026-10-02 17:30:00', 'quantidade_entrada' => 10, 'vendedor_destino' => 'Loja Centro',
        ]);

        $html = $this->actingAs($this->conferente())->get(route('conferencia.index', ['aba' => 'conferidos']))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'Entrada realizada')); // o bloco só de leitura, abaixo do cartão do item
        $this->assertStringContainsString('Loja Centro', $html);
        $this->assertStringContainsString('02/10/2026', $html);
        $this->assertStringNotContainsString(route('entrada.darEntrada', $item), $html);
    }

    public function test_conferencia_mostra_aguardando_entrada_para_o_item_liberado(): void
    {
        $this->item(['status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 10]);

        $html = $this->actingAs($this->conferente())->get(route('conferencia.index', ['aba' => 'conferidos']))->getContent();

        $this->assertSame(1, substr_count($html, 'Aguardando entrada'));
        $this->assertStringNotContainsString('Entrada realizada', $html);
    }

    public function test_item_divergente_ou_cancelado_nao_mostra_o_bloco_de_entrada(): void
    {
        $this->item(['status_conferencia' => 'divergente', 'quantidade_recebida' => 8]);
        $this->item(['status_conferencia' => 'cancelado', 'quantidade_recebida' => 8]);

        $html = $this->actingAs($this->conferente())->get(route('conferencia.index', ['aba' => 'conferidos']))->getContent();

        $this->assertStringNotContainsString('Aguardando entrada', $html);
        $this->assertStringNotContainsString('Entrada realizada', $html);
    }

    public function test_quem_e_da_entrada_tambem_ve_o_bloco_na_conferencia(): void
    {
        $this->item(['status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 10, 'entrada_concluida_em' => now(), 'quantidade_entrada' => 10, 'vendedor_destino' => 'Loja']);

        $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('conferencia.index', ['aba' => 'conferidos']))->assertSee('Entrada realizada');
    }
}
