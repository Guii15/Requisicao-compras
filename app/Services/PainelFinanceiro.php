<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Números do painel (dashboard) do Financeiro. */
class PainelFinanceiro
{
    private const MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

    private const FAIXAS_DE_IDADE = [
        ['rotulo' => 'Até 7 dias', 'ate' => 7],
        ['rotulo' => '8 a 15 dias', 'ate' => 15],
        ['rotulo' => '16 a 30 dias', 'ate' => 30],
        ['rotulo' => 'Mais de 30 dias', 'ate' => PHP_INT_MAX],
    ];

    public function __construct(private readonly SaldoFornecedores $saldos)
    {
    }

    public function dados(?CarbonImmutable $hoje = null): array
    {
        $hoje ??= CarbonImmutable::now('America/Sao_Paulo');
        $linhas = $this->saldos->linhas();
        $abertas = $linhas->where('aberto', '>', 0);
        $mesAtual = $hoje->format('Y-m');

        $comprado = round($linhas->sum('custo'), 2);
        $pago = round($linhas->sum('pago'), 2);
        $pagamentos = $linhas->flatMap(fn (array $l) => $l['compra']->pagamentos->map(fn ($p) => ['pg' => $p, 'linha' => $l]));

        return [
            'saldo' => round($linhas->sum('aberto'), 2),
            'comprado' => $comprado,
            'pago' => $pago,
            'percentual_pago' => $comprado > 0 ? round($pago / $comprado * 100, 1) : 0.0,
            'comprado_mes' => round($linhas->filter(fn ($l) => $l['compra']->data_compra->format('Y-m') === $mesAtual)->sum('custo'), 2),
            'pago_mes' => round($pagamentos->filter(fn ($x) => $x['pg']->data_pagamento->format('Y-m') === $mesAtual)->sum(fn ($x) => (float) $x['pg']->valor), 2),
            'aguardando' => [
                'compras' => $abertas->count(),
                'fornecedores' => $abertas->pluck('chave')->unique()->count(),
            ],
            'pagas' => $linhas->where('aberto', '<=', 0)->count(),
            'meses' => $this->meses($hoje, $linhas, $pagamentos),
            'top' => $this->saldos->resumo($linhas)->where('saldo', '>', 0)->take(8)->values()->all(),
            'idade' => $this->idade($hoje, $abertas),
            'ultimos' => $this->ultimos($pagamentos),
        ];
    }

    /** Comprado (pela data da compra) e pago (pela data do pagamento) nos últimos 6 meses. */
    private function meses(CarbonImmutable $hoje, Collection $linhas, Collection $pagamentos): array
    {
        $comprado = $linhas->groupBy(fn ($l) => $l['compra']->data_compra->format('Y-m'))->map(fn ($g) => $g->sum('custo'));
        $pago = $pagamentos->groupBy(fn ($x) => $x['pg']->data_pagamento->format('Y-m'))->map(fn ($g) => $g->sum(fn ($x) => (float) $x['pg']->valor));

        return collect(range(5, 0))->map(function (int $atras) use ($hoje, $comprado, $pago) {
            $mes = $hoje->startOfMonth()->subMonths($atras);
            $chave = $mes->format('Y-m');

            return [
                'chave' => $chave,
                'rotulo' => self::MESES[$mes->month - 1],
                'ano' => $mes->year,
                'comprado' => round((float) ($comprado[$chave] ?? 0), 2),
                'pago' => round((float) ($pago[$chave] ?? 0), 2),
            ];
        })->all();
    }

    /** O que está em aberto, agrupado por quantos dias se passaram desde a compra. */
    private function idade(CarbonImmutable $hoje, Collection $abertas): array
    {
        $fim = CarbonImmutable::parse($hoje->format('Y-m-d'), 'America/Sao_Paulo')->timestamp;

        $faixas = collect(self::FAIXAS_DE_IDADE)->map(fn ($f) => $f + ['compras' => 0, 'valor' => 0.0]);

        foreach ($abertas as $linha) {
            $inicio = CarbonImmutable::parse($linha['compra']->data_compra->format('Y-m-d'), 'America/Sao_Paulo')->timestamp;
            $dias = max(0, intdiv($fim - $inicio, 86400));

            $indice = $faixas->search(fn ($f) => $dias <= $f['ate']);
            $faixas[$indice] = array_merge($faixas[$indice], [
                'compras' => $faixas[$indice]['compras'] + 1,
                'valor' => round($faixas[$indice]['valor'] + $linha['aberto'], 2),
            ]);
        }

        return $faixas->map(fn ($f) => ['rotulo' => $f['rotulo'], 'compras' => $f['compras'], 'valor' => (float) $f['valor']])->all();
    }

    /** Os 6 pagamentos mais recentes. */
    private function ultimos(Collection $pagamentos): array
    {
        return $pagamentos
            ->sortByDesc(fn ($x) => $x['pg']->data_pagamento->format('Ymd') . str_pad((string) $x['pg']->id, 10, '0', STR_PAD_LEFT))
            ->take(6)
            ->map(fn ($x) => [
                'fornecedor' => $x['linha']['fornecedor'],
                'chave' => $x['linha']['chave'],
                'produto' => $x['linha']['compra']->product_name,
                'requisicao' => $x['linha']['compra']->id,
                'valor' => (float) $x['pg']->valor,
                'forma' => $x['pg']->forma,
                'data' => $x['pg']->data_pagamento,
            ])
            ->values()
            ->all();
    }
}
