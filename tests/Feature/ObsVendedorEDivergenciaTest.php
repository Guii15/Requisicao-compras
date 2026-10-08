<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O motivo e a obs que o vendedor escreve ao pedir acompanham a requisição até o fim (admin, compras,
 * conferência e entrada), e a observação da divergência aparece para admin, conferência e entrada.
 */
class ObsVendedorEDivergenciaTest extends TestCase
{
    use RefreshDatabase;

    private const MOTIVO = 'Reposição do estoque XYZ';
    private const OBS = 'Filial 31 - entregar no balcão';
    private const DIVERGENCIA = 'Chegou caixa amassada e 2 peças a menos';

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'quantity' => 5,
            'reason' => self::MOTIVO, 'justification' => self::OBS,
        ], $attrs));
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function conta(string $html, string $texto): int
    {
        return substr_count($html, $texto);
    }

    // ---------- admin ----------

    public function test_painel_de_requisicoes_do_admin_mostra_motivo_e_obs_do_vendedor(): void
    {
        $this->item(['status' => 'pendente']);

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()->getContent();

        // cartão do item + janela Atualizar (uma marcação só para PC e celular), as notas vêm com etiquetas
        $this->assertSame(2, $this->conta($html, '>VENDEDOR</span>'));
        $this->assertSame(2, $this->conta($html, '<strong>Motivo:</strong>'));
        $this->assertSame(2, $this->conta($html, self::OBS));
        $this->assertSame(2, $this->conta($html, self::MOTIVO));
        $this->assertStringNotContainsString('adm-mobile-cards', $html);
        $this->assertStringNotContainsString('id="modal-m-', $html);
    }

    public function test_painel_do_admin_mostra_a_divergencia(): void
    {
        $grupo = $this->item(['status' => 'pendente']);
        $this->item(['grupo_id' => $grupo->grupo_id, 'status_conferencia' => 'divergente', 'observacao_conferencia' => self::DIVERGENCIA]);

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->getContent();

        // cartão do item + janela Atualizar (uma marcação só para PC e celular)
        $this->assertSame(2, $this->conta($html, '>DIVERGÊNCIA</span>'));
        $this->assertSame(2, $this->conta($html, self::DIVERGENCIA));
    }

    public function test_compras_feitas_mostra_motivo_obs_e_divergencia_tambem_no_filtro_falta_registrar(): void
    {
        $this->item(['product_name' => 'Sem Dados', 'observacao_conferencia' => self::DIVERGENCIA, 'status_conferencia' => 'divergente']);
        $this->item(['product_name' => 'Com Dados', 'data_compra' => '2026-09-20', 'preco_unitario' => 10, 'valor' => 50]);

        // O filtro "Falta registrar" ficou no lugar da tela "Compras": lista o item sem dados; a lista normal traz o que já tem.
        $falta = route('admin.compras.feitas', ['situacao' => 'falta']);

        foreach ([$falta => 'Sem Dados', route('admin.compras.feitas') => 'Com Dados'] as $url => $produto) {
            $html = $this->actingAs($this->admin())->get($url)->assertOk()->getContent();

            $this->assertStringContainsString($produto, $html, $url);
            $this->assertStringContainsString(self::MOTIVO, $html, $url);
            $this->assertStringContainsString(self::OBS, $html, $url);
            $this->assertStringContainsString('>VENDEDOR</span>', $html, $url);
            $this->assertStringContainsString('<strong>Motivo:</strong>', $html, $url);
        }

        $html = $this->actingAs($this->admin())->get($falta)->getContent();
        $this->assertStringNotContainsString('Com Dados', $html);
        $this->assertStringContainsString('>DIVERGÊNCIA</span>', $html);
        $this->assertSame(1, $this->conta($html, self::DIVERGENCIA)); // no cartão do item
    }

    // ---------- conferência ----------

    public function test_conferencia_mostra_motivo_e_obs_do_vendedor_na_lista_e_no_quadro_de_conferir(): void
    {
        $this->item(['status_conferencia' => null]);

        $html = $this->actingAs(User::factory()->create(['role' => 'conferente']))->get(route('conferencia.index'))->assertOk()->getContent();

        // cartão do item + janela Conferir Item (uma marcação só para PC e celular)
        $this->assertSame(2, $this->conta($html, self::OBS));
        $this->assertSame(2, $this->conta($html, self::MOTIVO));
        $this->assertSame(2, $this->conta($html, '>VENDEDOR</span>'));
        $this->assertSame(2, $this->conta($html, '<strong>Motivo:</strong>'));
    }

    public function test_conferencia_mostra_a_divergencia_nos_conferidos(): void
    {
        $this->item(['status_conferencia' => 'divergente', 'observacao_conferencia' => self::DIVERGENCIA, 'quantidade_recebida' => 3]);

        $html = $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index', ['aba' => 'conferidos']))->assertOk()->getContent();

        $this->assertStringContainsString('>DIVERGÊNCIA</span>', $html);
        $this->assertSame(1, $this->conta($html, self::DIVERGENCIA)); // cartão do item (o mesmo no PC e no celular)
        $this->assertStringContainsString(self::OBS, $html);
    }

    // ---------- entrada ----------

    public function test_entrada_mostra_motivo_obs_do_vendedor_e_divergencia(): void
    {
        $this->item([
            'status_conferencia' => 'avancado_mesmo_assim', 'tipo_entrega' => 'entrega_direta',
            'observacao_conferencia' => self::DIVERGENCIA, 'quantidade_recebida' => 3,
        ]);

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))->assertOk()->getContent();

        // cartão do item + janela Dar Entrada (uma marcação só para PC e celular)
        $this->assertSame(2, $this->conta($html, self::OBS));
        $this->assertSame(2, $this->conta($html, self::MOTIVO));
        $this->assertSame(2, $this->conta($html, self::DIVERGENCIA));
        $this->assertSame(2, $this->conta($html, '>DIVERGÊNCIA</span>'));
    }

    public function test_entrada_ja_realizada_continua_mostrando(): void
    {
        $this->item(['status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 5, 'entrada_concluida_em' => now(), 'quantidade_entrada' => 5, 'vendedor_destino' => 'Loja']);

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index', ['aba' => 'concluidas']))->getContent();

        $this->assertStringContainsString(self::OBS, $html);
    }

    // ---------- quando está vazio ----------

    public function test_sem_motivo_obs_ou_divergencia_os_blocos_nao_aparecem(): void
    {
        $this->item(['status' => 'pendente', 'reason' => '', 'justification' => '', 'observacao_conferencia' => null]);
        $this->item(['status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 5, 'reason' => '', 'justification' => '', 'observacao_conferencia' => null]);

        $paginas = [
            [$this->admin(), route('admin.index')],
            [$this->admin(), route('admin.compras.feitas', ['situacao' => 'falta'])],
            [User::factory()->create(['role' => 'conferente']), route('conferencia.index')],
            [User::factory()->create(['role' => 'entrada']), route('entrada.index')],
        ];

        foreach ($paginas as [$usuario, $url]) {
            $html = $this->actingAs($usuario)->get($url)->assertOk()->getContent();

            $this->assertStringNotContainsString('Obs (Vendedor)', $html, $url);
            $this->assertStringNotContainsString('Motivo (Vendedor)', $html, $url);
            $this->assertStringNotContainsString('Divergência (Conferência)', $html, $url);
            // nas telas de cartão (compras feitas, conferência e entrada) as notas vêm com etiquetas
            $this->assertStringNotContainsString('>VENDEDOR</span>', $html, $url);
            $this->assertStringNotContainsString('<strong>Motivo:</strong>', $html, $url);
            $this->assertStringNotContainsString('>DIVERGÊNCIA</span>', $html, $url);
        }
    }

    public function test_so_com_motivo_aparece_so_o_motivo(): void
    {
        $this->item(['status' => 'pendente', 'justification' => '']);

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->getContent();

        // a nota do vendedor traz só o motivo, sem o separador nem a obs
        $this->assertSame(2, $this->conta($html, '>VENDEDOR</span>'));
        $this->assertSame(2, preg_match_all('/<strong>Motivo:<\/strong> ' . preg_quote(self::MOTIVO, '/') . '\s*<\/div>/u', $html));
        $this->assertStringNotContainsString(self::OBS, $html);
    }
}
