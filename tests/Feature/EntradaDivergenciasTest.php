<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Entrada enxerga as divergências que a conferência registrou, mesmo antes de o item ser liberado
 * (aba "Divergências", só para consulta, sem botão de dar entrada).
 */
class EntradaDivergenciasTest extends TestCase
{
    use RefreshDatabase;

    private const DIVERGENCIA = 'Chegou caixa amassada e 2 peças a menos';
    private const OBS = 'Filial 31 - entregar no balcão';

    private User $entrada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entrada = User::factory()->create(['role' => 'entrada']);
    }

    private function divergente(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'estoque',
            'quantity' => 10, 'quantidade_recebida' => 8, 'product_name' => 'Cabo Divergente', 'supplier' => 'Kabum',
            'observacao_conferencia' => self::DIVERGENCIA, 'justification' => self::OBS, 'reason' => 'Reposição',
        ], $attrs));
    }

    private function aba(string $aba = 'divergencias', array $extra = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->entrada)->get(route('entrada.index', ['aba' => $aba] + $extra))->assertOk();
    }

    public function test_aba_divergencias_lista_o_item_com_a_observacao_e_os_detalhes(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente', 'name' => 'Carlos Conferente']);
        $this->divergente(['conferente_id' => $conferente->id]);

        $this->aba()
            ->assertSee('Cabo Divergente')
            ->assertSee(self::DIVERGENCIA)
            ->assertSee('Divergência (Conferência)')
            ->assertSee(self::OBS)
            ->assertSee('Obs (Vendedor)')
            ->assertSee('Carlos Conferente')
            ->assertSee('Kabum');
    }

    public function test_mostra_quantidade_pedida_e_recebida(): void
    {
        $this->divergente();

        $html = $this->aba()->getContent();

        $this->assertMatchesRegularExpression('/Pedido[^<]*<[^>]*>\s*10\b/u', $html);
        $this->assertMatchesRegularExpression('/Recebido[^<]*<[^>]*>\s*8\b/u', $html);
    }

    public function test_item_em_divergencia_nao_tem_botao_de_dar_entrada(): void
    {
        $this->divergente();

        $html = $this->aba()->getContent();

        $this->assertStringNotContainsString('Dar Entrada', $html);
        $this->assertStringNotContainsString(route('entrada.darEntrada', PurchaseRequest::first()), $html);
        $this->assertStringContainsString('Aguardando decisão do admin', $html);
    }

    public function test_entrega_direta_divergente_tambem_aparece_com_o_aviso_certo(): void
    {
        $this->divergente(['tipo_entrega' => 'entrega_direta', 'product_name' => 'Item Direto']);

        $this->aba()->assertSee('Item Direto')->assertSee('Venda Casada');
    }

    public function test_so_lista_divergentes_aprovados(): void
    {
        $this->divergente(['product_name' => 'Item Divergente']);
        $this->divergente(['product_name' => 'Item Cancelado', 'status_conferencia' => 'cancelado']);
        $this->divergente(['product_name' => 'Item Avancado', 'status_conferencia' => 'avancado_mesmo_assim']);
        $this->divergente(['product_name' => 'Item OK', 'status_conferencia' => 'conferido_ok']);
        $this->divergente(['product_name' => 'Item Pendente', 'status' => 'pendente']);
        $this->divergente(['product_name' => 'Item Sem Conferir', 'status_conferencia' => null]);

        $html = $this->aba()->getContent();

        $this->assertStringContainsString('Item Divergente', $html);
        foreach (['Item Cancelado', 'Item Avancado', 'Item OK', 'Item Pendente', 'Item Sem Conferir'] as $fora) {
            $this->assertStringNotContainsString($fora, $html, $fora);
        }
    }

    public function test_aguardando_continua_sem_os_divergentes(): void
    {
        $this->divergente(['product_name' => 'Item Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 5, 'quantity' => 5, 'product_name' => 'Item Liberado']);

        $html = $this->aba('aguardando')->getContent();

        $this->assertStringContainsString('Item Liberado', $html);
        $this->assertStringNotContainsString('Item Divergente', $html);
    }

    public function test_todas_as_abas_mostram_a_quantidade_de_divergencias(): void
    {
        $this->divergente();
        $this->divergente(['product_name' => 'Outro Divergente']);

        foreach (['aguardando', 'concluidas', 'divergencias'] as $aba) {
            $this->aba($aba)->assertSee('Divergências')->assertSee('>2</span>', false);
        }
    }

    public function test_sem_divergencias_nao_mostra_o_numero_mas_mostra_a_aba_e_a_mensagem(): void
    {
        $this->aba('divergencias')->assertSee('Divergências')->assertSee('Nenhuma divergência em análise');
    }

    public function test_busca_funciona_na_aba(): void
    {
        $this->divergente(['product_name' => 'Teclado Quebrado']);
        $this->divergente(['product_name' => 'Mouse Quebrado']);

        $this->aba('divergencias', ['q' => 'teclado'])->assertSee('Teclado Quebrado')->assertDontSee('Mouse Quebrado');
    }

    public function test_quando_o_admin_aceita_o_item_sai_das_divergencias_e_vai_para_aguardando_com_a_observacao(): void
    {
        $item = $this->divergente(['product_name' => 'Item Que Sera Aceito']);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $item), ['decisao' => 'aceitar'])->assertSessionHasNoErrors();

        $this->assertStringNotContainsString('Item Que Sera Aceito', $this->aba('divergencias')->getContent());
        $this->aba('aguardando')->assertSee('Item Que Sera Aceito')->assertSee(self::DIVERGENCIA);
    }

    public function test_quando_o_admin_cancela_o_item_some_da_entrada(): void
    {
        $item = $this->divergente(['product_name' => 'Item Cancelado Pelo Admin']);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $item), ['decisao' => 'cancelar', 'observacao' => 'Devolver ao fornecedor']);

        $this->assertStringNotContainsString('Item Cancelado Pelo Admin', $this->aba('divergencias')->getContent());
        $this->assertStringNotContainsString('Item Cancelado Pelo Admin', $this->aba('aguardando')->getContent());
    }

    public function test_a_aba_so_para_quem_acessa_a_entrada(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'conferente']))->get(route('entrada.index', ['aba' => 'divergencias']))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => null, 'is_admin' => false]))->get(route('entrada.index', ['aba' => 'divergencias']))->assertForbidden();
    }
}
