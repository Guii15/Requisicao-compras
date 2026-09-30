<?php

namespace App\Console\Commands;

use App\Services\UnificadorFornecedores;
use Illuminate\Console\Command;

class UnificarFornecedores extends Command
{
    protected $signature = 'fornecedores:unificar
        {--dry-run : Só mostra o mapeamento e exporta o CSV, sem gravar nada}
        {--mapa= : CSV revisado (coluna fornecedor_final) a aplicar no lugar do mapeamento automático}
        {--exportar= : Onde salvar o CSV do mapeamento (padrão: storage/app/fornecedores-mapa.csv)}';

    protected $description = 'Junta grafias diferentes do mesmo fornecedor num fornecedor só (idempotente)';

    private const SEPARADOR = ';';

    public function handle(UnificadorFornecedores $unificador): int
    {
        $grupos = $unificador->grupos();
        $sugestoes = $unificador->sugestoes();

        if ($grupos->isEmpty()) {
            $this->info('Nenhuma compra com fornecedor preenchido.');
            return self::SUCCESS;
        }

        $mapa = $this->option('mapa') ? $this->lerMapa($this->option('mapa')) : $unificador->mapaPadrao();
        if ($mapa === null) {
            return self::FAILURE;
        }

        $this->mostrarMapeamento($grupos, $mapa);
        $this->mostrarSugestoes($grupos, $sugestoes);

        if ($this->option('dry-run')) {
            $caminho = $this->option('exportar') ?: storage_path('app/fornecedores-mapa.csv');
            $this->exportarCsv($caminho, $grupos, $sugestoes, $unificador);
            $this->info("CSV do mapeamento salvo em: {$caminho}");
            $this->line('Edite a coluna fornecedor_final para juntar sugestões ou trocar nomes e aplique com --mapa=<arquivo>.');
            $this->warn('Nada foi gravado (--dry-run).');

            return self::SUCCESS;
        }

        if (!$this->confirm('Gravar esse mapeamento no banco?')) {
            $this->warn('Cancelado. Nada foi gravado.');
            return self::SUCCESS;
        }

        $resultado = $unificador->aplicar($mapa);
        $this->info("{$resultado['fornecedores_criados']} fornecedor(es) criado(s), {$resultado['compras_alteradas']} compra(s) alterada(s).");

        return self::SUCCESS;
    }

    private function mostrarMapeamento($grupos, array $mapa): void
    {
        $contagem = [];
        foreach ($grupos as $grupo) {
            foreach ($grupo['variantes'] as $v) {
                $contagem[$v['original']] = $v['compras'];
            }
        }

        $linhas = collect($mapa)
            ->map(fn ($final, $original) => [$final, '"' . $original . '"', $contagem[$original] ?? 0])
            ->sortBy(fn ($l) => mb_strtoupper($l[0]) . "\0" . $l[1])
            ->values()
            ->all();

        $this->info('Mapeamento: nome original → fornecedor final');
        $this->table(['Fornecedor final', 'Nome original (como foi digitado)', 'Compras'], $linhas);
        $this->line(count($mapa) . ' grafia(s) → ' . collect($mapa)->unique()->count() . ' fornecedor(es).');
    }

    private function mostrarSugestoes($grupos, $sugestoes): void
    {
        $this->newLine();
        if ($sugestoes->isEmpty()) {
            $this->info('Sugestões de parecidos: nenhuma.');
            return;
        }

        $porChave = $grupos->keyBy('normalizado');
        $nome = fn ($chave) => $porChave[$chave]['nome_final'] . ' (' . $porChave[$chave]['compras'] . ')';

        $this->warn('Sugestões de parecidos (NÃO foram juntados, você decide):');
        $this->table(['Fornecedor', 'Parecido com', 'Motivo'], $sugestoes->map(fn ($s) => [$nome($s['a']), $nome($s['b']), $s['motivo']])->all());
    }

    private function exportarCsv(string $caminho, $grupos, $sugestoes, UnificadorFornecedores $unificador): void
    {
        $porChave = $grupos->keyBy('normalizado');
        $parecidos = [];
        foreach ($sugestoes as $s) {
            $parecidos[$s['a']][] = $porChave[$s['b']]['nome_final'];
            $parecidos[$s['b']][] = $porChave[$s['a']]['nome_final'];
        }

        if (!is_dir(dirname($caminho))) {
            mkdir(dirname($caminho), 0775, true);
        }

        $arquivo = fopen($caminho, 'w');
        fwrite($arquivo, "\u{FEFF}");
        fputcsv($arquivo, ['nome_original', 'normalizado', 'fornecedor_final', 'compras', 'sugestao'], self::SEPARADOR, '"', '');

        foreach ($grupos as $grupo) {
            foreach ($grupo['variantes'] as $v) {
                $sugestao = isset($parecidos[$grupo['normalizado']]) ? 'parecido com: ' . implode(' | ', $parecidos[$grupo['normalizado']]) : '';
                fputcsv($arquivo, [$v['original'], $grupo['normalizado'], $grupo['nome_final'], $v['compras'], $sugestao], self::SEPARADOR, '"', '');
            }
        }

        fclose($arquivo);
    }

    /** @return array<string, string>|null */
    private function lerMapa(string $caminho): ?array
    {
        if (!is_readable($caminho)) {
            $this->error("Não consegui ler o arquivo: {$caminho}");
            return null;
        }

        $arquivo = fopen($caminho, 'r');
        $cabecalho = fgetcsv($arquivo, null, self::SEPARADOR, '"', '');
        $cabecalho[0] = ltrim($cabecalho[0] ?? '', "\u{FEFF}");
        $colunas = array_flip($cabecalho);

        if (!isset($colunas['nome_original'], $colunas['fornecedor_final'])) {
            $this->error('O CSV precisa das colunas nome_original e fornecedor_final.');
            fclose($arquivo);
            return null;
        }

        $mapa = [];
        while (($linha = fgetcsv($arquivo, null, self::SEPARADOR, '"', '')) !== false) {
            $original = $linha[$colunas['nome_original']] ?? null;
            $final = trim($linha[$colunas['fornecedor_final']] ?? '');
            if ($original !== null && $original !== '' && $final !== '') {
                $mapa[$original] = $final;
            }
        }
        fclose($arquivo);

        return $mapa;
    }
}
