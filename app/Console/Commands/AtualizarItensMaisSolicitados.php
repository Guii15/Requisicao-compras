<?php

namespace App\Console\Commands;

use App\Services\ItensMaisSolicitadosImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class AtualizarItensMaisSolicitados extends Command
{
    protected $signature = 'itens:atualizar-ranking
        {--limiar=0.85 : Limiar de similaridade do TF-IDF (0 a 1)}
        {--arquivo= : Importa direto de um JSON ja gerado, sem rodar o script Python}';

    protected $description = 'Agrupa variacoes de texto de product_name e atualiza o ranking de itens mais solicitados';

    public function handle(ItensMaisSolicitadosImporter $importer): int
    {
        $caminhoJson = $this->option('arquivo') ?: $this->rodarScriptPython();

        if ($caminhoJson === null) {
            return self::FAILURE;
        }

        if (!file_exists($caminhoJson)) {
            $this->error("Arquivo de saída não encontrado: {$caminhoJson}");
            return self::FAILURE;
        }

        $dados = json_decode(file_get_contents($caminhoJson), true);

        if (!is_array($dados) || !isset($dados['banco_real'])) {
            $this->error("Arquivo de saída inválido (chave 'banco_real' não encontrada): {$caminhoJson}");
            return self::FAILURE;
        }

        $total = $importer->importar($dados['banco_real']);

        $this->info("Ranking de itens mais solicitados atualizado: {$total} item(ns).");
        return self::SUCCESS;
    }

    private function rodarScriptPython(): ?string
    {
        $script = base_path('scripts/itens_mais_solicitados/agrupar_itens.py');
        $saida = storage_path('app/itens_mais_solicitados_ranking.json');

        $resultado = Process::timeout(180)->run([
            'python',
            $script,
            '--db', database_path('database.sqlite'),
            '--limiar', (string) $this->option('limiar'),
            '--pular-demo',
            '--saida', $saida,
        ]);

        if ($resultado->failed()) {
            $this->error('Falha ao rodar o script Python: ' . $resultado->errorOutput());
            return null;
        }

        return $saida;
    }
}
