<?php

namespace Tests\Feature;

use App\Models\ItemMaisSolicitado;
use App\Services\ItensMaisSolicitadosImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItensMaisSolicitadosImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_ranking_criando_registros(): void
    {
        $ranking = [
            [
                'nome_canonico' => 'SSD',
                'capacidade'    => null,
                'total_pedidos' => 8,
                'variacoes'     => [
                    ['texto' => 'SSD', 'qtd' => 4],
                    ['texto' => 'ssd', 'qtd' => 4],
                ],
            ],
            [
                'nome_canonico' => 'SSD 240GB',
                'capacidade'    => '240gb',
                'total_pedidos' => 3,
                'variacoes'     => [
                    ['texto' => 'SSD 240GB', 'qtd' => 3],
                ],
            ],
        ];

        $total = (new ItensMaisSolicitadosImporter())->importar($ranking);

        $this->assertSame(2, $total);
        $this->assertDatabaseCount('itens_mais_solicitados', 2);

        $ssd = ItemMaisSolicitado::where('nome_canonico', 'SSD')->first();
        $this->assertNotNull($ssd);
        $this->assertNull($ssd->capacidade);
        $this->assertSame(8, $ssd->total_pedidos);
        $this->assertCount(2, $ssd->variacoes_agrupadas);

        $ssdCapacidade = ItemMaisSolicitado::where('nome_canonico', 'SSD 240GB')->first();
        $this->assertSame('240gb', $ssdCapacidade->capacidade);
    }

    public function test_importar_substitui_ranking_anterior_por_completo(): void
    {
        ItemMaisSolicitado::create([
            'nome_canonico'        => 'Item Antigo',
            'capacidade'           => null,
            'total_pedidos'        => 99,
            'variacoes_agrupadas'  => [['texto' => 'Item Antigo', 'qtd' => 99]],
            'atualizado_em'        => now()->subDay(),
        ]);

        $ranking = [
            [
                'nome_canonico' => 'Item Novo',
                'capacidade'    => null,
                'total_pedidos' => 5,
                'variacoes'     => [['texto' => 'Item Novo', 'qtd' => 5]],
            ],
        ];

        (new ItensMaisSolicitadosImporter())->importar($ranking);

        $this->assertDatabaseCount('itens_mais_solicitados', 1);
        $this->assertDatabaseMissing('itens_mais_solicitados', ['nome_canonico' => 'Item Antigo']);
    }

    public function test_importar_com_ranking_vazio_esvazia_tabela(): void
    {
        ItemMaisSolicitado::create([
            'nome_canonico'        => 'Item Antigo',
            'capacidade'           => null,
            'total_pedidos'        => 1,
            'variacoes_agrupadas'  => [['texto' => 'Item Antigo', 'qtd' => 1]],
            'atualizado_em'        => now(),
        ]);

        $total = (new ItensMaisSolicitadosImporter())->importar([]);

        $this->assertSame(0, $total);
        $this->assertDatabaseCount('itens_mais_solicitados', 0);
    }
}
