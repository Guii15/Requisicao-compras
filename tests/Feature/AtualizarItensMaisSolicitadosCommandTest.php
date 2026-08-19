<?php

namespace Tests\Feature;

use App\Models\ItemMaisSolicitado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class AtualizarItensMaisSolicitadosCommandTest extends TestCase
{
    use RefreshDatabase;

    private function escreverArquivoFixture(array $bancoReal): string
    {
        $caminho = storage_path('app/testing_ranking_fixture.json');
        file_put_contents($caminho, json_encode(['banco_real' => $bancoReal], JSON_UNESCAPED_UNICODE));
        return $caminho;
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('app/testing_ranking_fixture.json'));
        parent::tearDown();
    }

    public function test_importa_ranking_a_partir_de_arquivo_sem_rodar_python(): void
    {
        $arquivo = $this->escreverArquivoFixture([
            [
                'nome_canonico' => 'SSD',
                'capacidade'    => null,
                'total_pedidos' => 8,
                'variacoes'     => [
                    ['texto' => 'SSD', 'qtd' => 4],
                    ['texto' => 'ssd', 'qtd' => 4],
                ],
            ],
        ]);

        $this->artisan('itens:atualizar-ranking', ['--arquivo' => $arquivo])
            ->assertExitCode(0);

        $this->assertDatabaseCount('itens_mais_solicitados', 1);
        $item = ItemMaisSolicitado::first();
        $this->assertSame('SSD', $item->nome_canonico);
        $this->assertSame(8, $item->total_pedidos);
    }

    public function test_arquivo_inexistente_falha_com_mensagem_clara(): void
    {
        $this->artisan('itens:atualizar-ranking', ['--arquivo' => storage_path('app/nao-existe.json')])
            ->assertExitCode(1);

        $this->assertDatabaseCount('itens_mais_solicitados', 0);
    }

    public function test_reimportar_substitui_ranking_anterior(): void
    {
        $arquivo1 = $this->escreverArquivoFixture([
            ['nome_canonico' => 'Item A', 'capacidade' => null, 'total_pedidos' => 1, 'variacoes' => [['texto' => 'Item A', 'qtd' => 1]]],
        ]);
        $this->artisan('itens:atualizar-ranking', ['--arquivo' => $arquivo1])->assertExitCode(0);

        $arquivo2 = $this->escreverArquivoFixture([
            ['nome_canonico' => 'Item B', 'capacidade' => null, 'total_pedidos' => 2, 'variacoes' => [['texto' => 'Item B', 'qtd' => 2]]],
        ]);
        $this->artisan('itens:atualizar-ranking', ['--arquivo' => $arquivo2])->assertExitCode(0);

        $this->assertDatabaseCount('itens_mais_solicitados', 1);
        $this->assertDatabaseHas('itens_mais_solicitados', ['nome_canonico' => 'Item B']);
    }

    public function test_sem_opcao_arquivo_roda_o_script_python_e_importa_saida(): void
    {
        $saida = storage_path('app/itens_mais_solicitados_ranking.json');
        file_put_contents($saida, json_encode([
            'banco_real' => [
                ['nome_canonico' => 'Pneu', 'capacidade' => null, 'total_pedidos' => 3, 'variacoes' => [['texto' => 'Pneu', 'qtd' => 3]]],
            ],
        ], JSON_UNESCAPED_UNICODE));

        Process::fake();

        $this->artisan('itens:atualizar-ranking')->assertExitCode(0);

        Process::assertRan(function ($process) {
            return str_contains($process->command[0] ?? '', 'python')
                && in_array('--pular-demo', $process->command, true);
        });

        $this->assertDatabaseHas('itens_mais_solicitados', ['nome_canonico' => 'Pneu']);

        @unlink($saida);
    }

    public function test_falha_do_processo_python_nao_apaga_ranking_existente(): void
    {
        ItemMaisSolicitado::create([
            'nome_canonico'       => 'Item Preservado',
            'capacidade'          => null,
            'total_pedidos'       => 1,
            'variacoes_agrupadas' => [['texto' => 'Item Preservado', 'qtd' => 1]],
            'atualizado_em'       => now(),
        ]);

        Process::fake([
            '*' => Process::result(errorOutput: 'python: command not found', exitCode: 127),
        ]);

        $this->artisan('itens:atualizar-ranking')->assertExitCode(1);

        $this->assertDatabaseHas('itens_mais_solicitados', ['nome_canonico' => 'Item Preservado']);
    }
}
