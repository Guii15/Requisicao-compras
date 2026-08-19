<?php

namespace App\Console\Commands;

use App\Models\PurchaseRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BackfillGrupoId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'requisicoes:backfill-grupo-id
        {--dry-run : Mostra o agrupamento encontrado sem gravar no banco}
        {--janela=120 : Janela em segundos entre registros consecutivos do mesmo usuario para considerar o mesmo grupo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preenche grupo_id em requisicoes antigas, agrupando por user_id + janela de tempo entre registros consecutivos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $janela = (int) $this->option('janela');
        $dryRun = (bool) $this->option('dry-run');

        $pendentes = PurchaseRequest::whereNull('grupo_id')
            ->orderBy('user_id')
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'created_at']);

        if ($pendentes->isEmpty()) {
            $this->info('Nenhum registro sem grupo_id encontrado.');
            return self::SUCCESS;
        }

        $grupos = [];
        $grupoAtual = null;
        $usuarioAnterior = null;
        $dataAnterior = null;

        foreach ($pendentes as $registro) {
            $novoGrupo = $usuarioAnterior !== $registro->user_id
                || $dataAnterior === null
                || abs($registro->created_at->getTimestamp() - $dataAnterior->getTimestamp()) > $janela;

            if ($novoGrupo) {
                $grupoAtual = (string) Str::uuid();
            }

            $grupos[$grupoAtual][] = $registro->id;

            $usuarioAnterior = $registro->user_id;
            $dataAnterior = $registro->created_at;
        }

        $this->info(count($grupos) . ' grupo(s) identificado(s) a partir de ' . $pendentes->count() . ' registro(s) sem grupo_id.');

        foreach ($grupos as $grupoId => $ids) {
            $this->line('  grupo ' . $grupoId . ': ' . count($ids) . ' item(ns) -> ids [' . implode(',', $ids) . ']');
            if (!$dryRun) {
                PurchaseRequest::whereIn('id', $ids)->update(['grupo_id' => $grupoId]);
            }
        }

        if ($dryRun) {
            $this->warn('Modo --dry-run: nada foi gravado no banco.');
        } else {
            $this->info('grupo_id gravado com sucesso.');
        }

        return self::SUCCESS;
    }
}
