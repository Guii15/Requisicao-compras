<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Na aba Aguardando da Entrada, o que acabou de ficar liberado (conferido, ou aceito em Pendências) tem que
 * aparecer no topo. Antes a fila era ordenada pela data em que a requisição foi CRIADA: um item criado dias atrás
 * e liberado hoje caía na página 2, 3... e parecia ter sumido (caso da requisição #4113).
 */
class EntradaOrdemAguardandoTest extends TestCase
{
    use RefreshDatabase;

    private function entrada(): User
    {
        return User::factory()->create(['role' => 'entrada']);
    }

    /** Cria $qtd requisições novas já liberadas para a Entrada (cada uma num grupo próprio). */
    private function filaDeNovas(int $qtd): void
    {
        for ($i = 1; $i <= $qtd; $i++) {
            PurchaseRequest::factory()->aprovado()->create([
                'product_name'       => sprintf('Fila %02d', $i),
                'status_conferencia' => 'conferido_ok',
                'created_at'         => now()->subHours($i),
                'updated_at'         => now()->subHours($i),
            ]);
        }
    }

    private function pagina(array $query = []): string
    {
        return $this->actingAs($this->entrada())->get(route('entrada.index', ['aba' => 'aguardando'] + $query))->assertOk()->getContent();
    }

    public function test_item_antigo_liberado_agora_aparece_na_primeira_pagina(): void
    {
        $this->filaDeNovas(20); // mais que uma página (15 por página)

        PurchaseRequest::factory()->aprovado()->create([
            'product_name'       => 'DRIFT 300W',
            'status_conferencia' => 'avancado_mesmo_assim', // aceito em Pendências agora
            'created_at'         => now()->subDays(6),
            'updated_at'         => now(),
        ]);

        $this->assertStringContainsString('DRIFT 300W', $this->pagina());
    }

    public function test_o_liberado_mais_recentemente_vem_primeiro(): void
    {
        $this->filaDeNovas(3);

        PurchaseRequest::factory()->aprovado()->create([
            'product_name'       => 'Recem Liberado',
            'status_conferencia' => 'conferido_ok',
            'created_at'         => now()->subDays(10),
            'updated_at'         => now(),
        ]);

        $html = $this->pagina();

        $this->assertLessThan(strpos($html, 'Fila 01'), strpos($html, 'Recem Liberado'));
        $this->assertLessThan(strpos($html, 'Fila 02'), strpos($html, 'Fila 01'));
        $this->assertLessThan(strpos($html, 'Fila 03'), strpos($html, 'Fila 02'));
    }

    public function test_a_fila_continua_paginada_e_nada_se_perde_entre_as_paginas(): void
    {
        $this->filaDeNovas(20);

        $p1 = $this->pagina();
        $p2 = $this->pagina(['page' => 2]);

        for ($i = 1; $i <= 20; $i++) {
            $nome = sprintf('Fila %02d', $i);
            $this->assertSame(1, (int) str_contains($p1, $nome) + (int) str_contains($p2, $nome), "$nome precisa estar em exatamente uma das páginas");
        }
    }

    public function test_a_busca_continua_achando_o_item_antigo(): void
    {
        $this->filaDeNovas(20);
        PurchaseRequest::factory()->aprovado()->create([
            'product_name' => 'DRIFT 300W', 'status_conferencia' => 'avancado_mesmo_assim',
            'created_at' => now()->subDays(6), 'updated_at' => now(),
        ]);

        $this->assertStringContainsString('DRIFT 300W', $this->pagina(['q' => 'drift']));
    }

    public function test_as_outras_abas_nao_mudam_de_ordem(): void
    {
        // Concluídas: pela data da entrada; Divergências: pela última alteração (como já era).
        PurchaseRequest::factory()->aprovado()->create([
            'product_name' => 'Entrada Velha', 'status_conferencia' => 'conferido_ok', 'entrada_concluida_em' => now()->subDays(5),
        ]);
        PurchaseRequest::factory()->aprovado()->create([
            'product_name' => 'Entrada Nova', 'status_conferencia' => 'conferido_ok', 'entrada_concluida_em' => now()->subDay(),
        ]);

        $html = $this->actingAs($this->entrada())->get(route('entrada.index', ['aba' => 'concluidas']))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Entrada Velha'), strpos($html, 'Entrada Nova'));
    }
}
