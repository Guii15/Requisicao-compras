<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\HistoricoComprasImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportarHistoricoCompras extends Command
{
    public const EMAIL_USUARIO_HISTORICO = 'historico-compras@sistema.local';

    protected $signature = 'compras:importar-historico
        {--csv= : Caminho do CSV consolidado (default: importacao compras/compras_consolidado.csv)}
        {--dry-run : Mostra o resumo da importação sem gravar no banco}';

    protected $description = 'Importa o histórico de compras da planilha consolidada para purchase_requests';

    public function handle(HistoricoComprasImporter $importer): int
    {
        $caminho = $this->option('csv') ?: base_path('importacao compras/compras_consolidado.csv');

        if (!file_exists($caminho)) {
            $this->error("Arquivo não encontrado: {$caminho}");
            return self::FAILURE;
        }

        $linhasCsv = $this->lerCsv($caminho);
        if ($linhasCsv === null) {
            $this->error("Não foi possível ler o cabeçalho do CSV: {$caminho}");
            return self::FAILURE;
        }

        $mapeadas = array_map(fn (array $linha) => $importer->mapear($linha), $linhasCsv);

        $this->mostrarResumo($mapeadas);

        if ($this->option('dry-run')) {
            $this->comment('Modo --dry-run: nada foi gravado no banco.');
            return self::SUCCESS;
        }

        $usuarioHistorico = User::firstOrCreate(
            ['email' => self::EMAIL_USUARIO_HISTORICO],
            [
                'name' => 'Histórico de Compras',
                'password' => bcrypt(Str::random(40)),
                'role' => 'historico_importacao',
                'is_admin' => false,
            ]
        );

        $criados = 0;
        $atualizados = 0;

        DB::transaction(function () use ($mapeadas, $usuarioHistorico, &$criados, &$atualizados) {
            foreach ($mapeadas as $atributos) {
                $atributos['user_id'] = $usuarioHistorico->id;

                $existia = PurchaseRequest::withoutGlobalScope('apenasFluxoAtivo')
                    ->where('origem_id', $atributos['origem_id'])
                    ->exists();

                PurchaseRequest::withoutGlobalScope('apenasFluxoAtivo')
                    ->updateOrCreate(['origem_id' => $atributos['origem_id']], $atributos);

                $existia ? $atualizados++ : $criados++;
            }
        });

        $this->info("Importação concluída: {$criados} novo(s), {$atualizados} atualizado(s).");
        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>|null
     */
    private function lerCsv(string $caminho): ?array
    {
        $fh = fopen($caminho, 'r');
        if ($fh === false) {
            return null;
        }

        $cabecalho = fgetcsv($fh, 0, ',', '"', '\\');
        if ($cabecalho === false) {
            fclose($fh);
            return null;
        }
        $cabecalho[0] = preg_replace('/^\xEF\xBB\xBF/', '', $cabecalho[0]);

        $linhas = [];
        while (($linha = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
            if ($linha === [null]) {
                continue;
            }
            $linhas[] = array_combine($cabecalho, $linha);
        }
        fclose($fh);

        return $linhas;
    }

    private function mostrarResumo(array $mapeadas): void
    {
        $porAba = [];
        $totalValor = 0.0;
        $totalCotacoes = 0;
        $totalComAlerta = 0;

        foreach ($mapeadas as $item) {
            $aba = $item['aba_origem'] ?: '(sem aba)';
            $porAba[$aba] = ($porAba[$aba] ?? 0) + 1;
            $totalValor += (float) ($item['valor'] ?? 0);
            if ($item['tipo_registro'] === 'cotacao_historica') {
                $totalCotacoes++;
            }
            if (!empty($item['dados_importacao']['flags_qualidade'] ?? null)) {
                $totalComAlerta++;
            }
        }

        $linhasTabela = [];
        foreach ($porAba as $aba => $qtd) {
            $linhasTabela[] = [$aba, $qtd];
        }
        $this->table(['Aba', 'Linhas'], $linhasTabela);

        $this->info('Total de linhas: ' . count($mapeadas));
        $this->info('Valor total (compras confirmadas): R$ ' . number_format($totalValor, 2, ',', '.'));
        $this->info('Cotações (Compra Pati): ' . $totalCotacoes);
        $this->info('Linhas com alerta de qualidade: ' . $totalComAlerta);
    }
}
