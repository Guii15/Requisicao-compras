<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Conferência renderiza UM modal de Conferir, UM de Editar parcial e UM de Coleta,
 * preenchidos por JS com os dados do item clicado (antes era um modal + um script por item,
 * o que deixava o DOM enorme e o celular descartava a página ao abrir a câmera).
 */
class ConferenciaModalUnicoTest extends TestCase
{
    use RefreshDatabase;

    private User $conferente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->conferente = User::factory()->create(['role' => 'conferente']);
    }

    private function aguardando(int $quantos, array $extra = []): array
    {
        $itens = [];
        for ($i = 0; $i < $quantos; $i++) {
            $itens[] = PurchaseRequest::factory()->create(array_merge(
                ['status' => 'aprovado', 'status_conferencia' => null, 'supplier' => 'Fornecedor X'],
                $extra
            ));
        }

        return $itens;
    }

    private function pagina(array $query = []): string
    {
        return $this->actingAs($this->conferente)->get(route('conferencia.index', $query))->assertOk()->getContent();
    }

    public function test_varios_itens_geram_um_unico_modal_conferir_e_um_unico_form(): void
    {
        $itens = $this->aguardando(5);

        $html = $this->pagina();

        $this->assertSame(1, substr_count($html, 'id="modal-conferir"'));
        $this->assertSame(1, substr_count($html, 'id="form-conferir"'));
        $this->assertSame(1, substr_count($html, 'name="foto"'));
        $this->assertSame(1, substr_count($html, 'name="fotos_extras[]"'));
        foreach ($itens as $item) {
            $this->assertStringNotContainsString('id="modal-conferir-' . $item->id . '"', $html);
            $this->assertStringNotContainsString('atualizaResultado' . $item->id, $html);
            $this->assertStringNotContainsString('verificaDivergencia' . $item->id, $html);
        }
    }

    public function test_campos_do_modal_mantem_os_names_que_o_controller_espera(): void
    {
        $this->aguardando(1);

        $html = $this->pagina();

        foreach (['quantidade_recebida', 'resultado', 'observacao_conferencia', 'obs', 'acao'] as $nome) {
            $this->assertStringContainsString('name="' . $nome . '"', $html);
        }
        $this->assertStringContainsString('name="_method" value="PATCH"', $html);
    }

    public function test_cada_item_carrega_seus_dados_num_template_e_o_botao_abre_o_modal(): void
    {
        [$a, $b] = $this->aguardando(2, ['tipo_entrega' => 'estoque']);
        $a->update(['tipo_entrega' => 'entrega_direta', 'product_name' => 'Produto Alfa', 'quantity' => 7]);

        $html = $this->pagina();

        $this->assertSame(1, substr_count($html, '<template id="conferir-item-' . $a->id . '"'));
        $this->assertSame(1, substr_count($html, '<template id="conferir-item-' . $b->id . '"'));
        $this->assertMatchesRegularExpression(
            '/<template id="conferir-item-' . $a->id . '"[^>]*data-url="' . preg_quote(e(route('conferencia.conferir', $a)), '/') . '"/',
            $html
        );
        $this->assertMatchesRegularExpression('/<template id="conferir-item-' . $a->id . '"[^>]*data-produto="Produto Alfa"/', $html);
        $this->assertMatchesRegularExpression('/<template id="conferir-item-' . $a->id . '"[^>]*data-qtd="7"/', $html);
        $this->assertMatchesRegularExpression('/<template id="conferir-item-' . $a->id . '"[^>]*data-entrega="entrega_direta"/', $html);
        $this->assertMatchesRegularExpression('/<template id="conferir-item-' . $b->id . '"[^>]*data-entrega="estoque"/', $html);
        $this->assertStringContainsString('onclick="abrirConferir(' . $a->id . ')"', $html);
        $this->assertStringContainsString('onclick="abrirConferir(' . $b->id . ')"', $html);
    }

    public function test_notas_do_item_ficam_dentro_do_template(): void
    {
        [$item] = $this->aguardando(1, ['admin_note' => 'NOTA-DO-ADMIN-XYZ']);

        $html = $this->pagina();

        $this->assertMatchesRegularExpression(
            '/<template id="conferir-item-' . $item->id . '".*?NOTA-DO-ADMIN-XYZ.*?<\/template>/s',
            $html
        );
    }

    public function test_sem_item_aguardando_nao_renderiza_modal_conferir(): void
    {
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);

        $html = $this->pagina(['aba' => 'conferidos']);

        $this->assertStringNotContainsString('id="modal-conferir"', $html);
        $this->assertStringNotContainsString('conferir-item-', $html);
    }

    public function test_rascunho_na_sessao_e_aviso_da_foto_estao_no_modal(): void
    {
        $this->aguardando(1);

        $html = $this->pagina();

        $this->assertStringContainsString('conferencia-rascunho', $html);
        $this->assertStringContainsString('sessionStorage', $html);
        $this->assertStringContainsString('A foto não foi anexada. Tire novamente.', $html);
    }

    public function test_editar_parcial_usa_um_unico_modal(): void
    {
        $pai = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 10]);
        $f1 = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'quantity' => 3, 'quantidade_original' => 10, 'restante_de_id' => $pai->id]);
        $f2 = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'quantity' => 4, 'quantidade_original' => 10, 'restante_de_id' => $pai->id]);

        $html = $this->pagina();

        $this->assertSame(1, substr_count($html, 'id="modal-editar-parcial"'));
        $this->assertStringNotContainsString('id="modal-editar-parcial-' . $f1->id . '"', $html);
        $this->assertStringContainsString('abrirEditarParcial(this)', $html);
        $this->assertStringContainsString('data-url="' . e(route('conferencia.editarParcial', $f1)) . '"', $html);
        $this->assertStringContainsString('data-url="' . e(route('conferencia.editarParcial', $f2)) . '"', $html);
    }

    public function test_sem_parcial_nao_renderiza_modal_de_editar_parcial(): void
    {
        $this->aguardando(2);

        $this->assertStringNotContainsString('id="modal-editar-parcial"', $this->pagina());
    }

    public function test_coleta_usa_um_unico_modal_com_template_por_item(): void
    {
        $itens = $this->aguardando(3, ['status_conferencia' => 'conferido_ok', 'status_coleta' => 'aguardando', 'obs' => 'OBS-COLETA-ABC']);

        $html = $this->pagina(['aba' => 'coleta', 'resultado' => 'aguardando']);

        $this->assertSame(1, substr_count($html, 'id="modal-coleta"'));
        $this->assertSame(1, substr_count($html, 'id="form-coleta"'));
        $this->assertSame(1, substr_count($html, 'name="data_coleta"'));
        foreach ($itens as $item) {
            $this->assertStringNotContainsString('id="modal-coleta-' . $item->id . '"', $html);
            $this->assertMatchesRegularExpression(
                '/<template id="coleta-item-' . $item->id . '"[^>]*data-url="' . preg_quote(e(route('conferencia.coleta', $item)), '/') . '"/',
                $html
            );
        }
        $this->assertMatchesRegularExpression('/<template id="coleta-item-' . $itens[0]->id . '".*?OBS-COLETA-ABC.*?<\/template>/s', $html);
    }

    public function test_coletados_nao_geram_modal_de_coleta(): void
    {
        $this->aguardando(1, ['status_conferencia' => 'conferido_ok', 'status_coleta' => 'coletado', 'data_coleta' => now()]);

        $html = $this->pagina(['aba' => 'coleta', 'resultado' => 'coletado']);

        $this->assertStringNotContainsString('id="modal-coleta"', $html);
        $this->assertStringNotContainsString('coleta-item-', $html);
    }

    public function test_dom_nao_cresce_com_o_numero_de_itens(): void
    {
        $this->aguardando(3);
        $pequeno = $this->pagina();

        $this->aguardando(10);
        $grande = $this->pagina();

        // Elementos que existiam 1x POR ITEM e agora são fixos.
        foreach (['<form', 'type="file"', '<script'] as $marca) {
            $this->assertSame(
                substr_count($pequeno, $marca),
                substr_count($grande, $marca),
                "A quantidade de '{$marca}' não pode depender do número de itens."
            );
        }
    }
}
