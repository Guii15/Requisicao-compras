<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Na aba Coleta da Conferência, cada item mostra as observações que acompanham a requisição
 * (principalmente a do admin), na linha, no card do celular e no quadro "Registrar Coleta".
 */
class ObsNaColetaTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'Coletar na doca 3, falar com o Marcos';
    private const OBS = 'Filial 31 - entregar no balcão';
    private const MOTIVO = 'Reposição do estoque XYZ';
    private const CONFERENTE = 'Caixa lacrada, conferir lote';
    private const DIVERGENCIA = 'Chegou 2 peças a menos';
    private const ENTRADA = 'Veio sem nota fiscal';

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_coleta' => 'aguardando', 'status_conferencia' => null, 'quantity' => 5,
            'product_name' => 'Item Da Coleta',
            'admin_note' => self::ADMIN, 'justification' => self::OBS, 'reason' => self::MOTIVO,
        ], $attrs));
    }

    private function coleta(string $resultado = 'aguardando', string $role = 'conferente'): string
    {
        return $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('conferencia.index', ['aba' => 'coleta', 'resultado' => $resultado]))->assertOk()->getContent();
    }

    public function test_coleta_mostra_a_obs_do_admin_o_motivo_e_a_obs_do_vendedor(): void
    {
        $this->item();

        $html = $this->coleta();

        $this->assertStringContainsString('>ADMIN</span>', $html);
        $this->assertStringContainsString('>VENDEDOR</span>', $html);
        $this->assertStringContainsString('<strong>Motivo:</strong>', $html);
        // linha do desktop + card do celular + quadro Registrar Coleta
        $this->assertSame(3, substr_count($html, self::ADMIN));
        $this->assertSame(3, substr_count($html, self::OBS));
        $this->assertSame(3, substr_count($html, self::MOTIVO));
    }

    public function test_coleta_mostra_tambem_conferente_divergencia_e_entrada_quando_existem(): void
    {
        $this->item(['obs' => self::CONFERENTE, 'observacao_conferencia' => self::DIVERGENCIA, 'obs_entrada' => self::ENTRADA]);

        $html = $this->coleta();

        $this->assertStringContainsString('>CONFERÊNCIA</span>', $html);
        $this->assertStringContainsString('>DIVERGÊNCIA</span>', $html);
        $this->assertStringContainsString('>ENTRADA</span>', $html);
        $this->assertStringContainsString(self::CONFERENTE, $html);
        $this->assertStringContainsString(self::DIVERGENCIA, $html);
        $this->assertStringContainsString(self::ENTRADA, $html);
    }

    public function test_aba_de_coletados_tambem_mostra_na_linha_e_no_card(): void
    {
        $this->item(['status_coleta' => 'coletado', 'data_coleta' => now()]);

        $html = $this->coleta('coletado');

        $this->assertSame(2, substr_count($html, self::ADMIN)); // sem o quadro de coletar
    }

    public function test_quem_e_da_entrada_tambem_ve(): void
    {
        $this->item();

        $this->assertStringContainsString(self::ADMIN, $this->coleta('aguardando', 'entrada'));
    }

    public function test_item_sem_nenhuma_observacao_nao_mostra_blocos(): void
    {
        $this->item(['admin_note' => null, 'justification' => '', 'reason' => '', 'obs' => null, 'observacao_conferencia' => null, 'obs_entrada' => null]);

        $html = $this->coleta();

        foreach (['Notas e Ocorrências', '>ADMIN</span>', '>VENDEDOR</span>', '<strong>Motivo:</strong>', '>CONFERÊNCIA</span>', '>DIVERGÊNCIA</span>', '>ENTRADA</span>'] as $bloco) {
            $this->assertStringNotContainsString($bloco, $html, $bloco);
        }
    }

    public function test_cada_item_mostra_a_propria_observacao(): void
    {
        $this->item(['product_name' => 'Item A', 'admin_note' => 'Nota do item A']);
        $this->item(['product_name' => 'Item B', 'admin_note' => 'Nota do item B']);

        $html = $this->coleta();

        $this->assertSame(3, substr_count($html, 'Nota do item A'));
        $this->assertSame(3, substr_count($html, 'Nota do item B'));
    }

    public function test_so_com_a_obs_do_admin_aparece_so_ela(): void
    {
        $this->item(['justification' => '', 'reason' => '']);

        $html = $this->coleta();

        $this->assertStringContainsString('>ADMIN</span>', $html);
        $this->assertStringNotContainsString('>VENDEDOR</span>', $html);
    }
}
