<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Relatório do Financeiro de um período: o que foi pago (pela data do pagamento), o que foi comprado
 * (pela data da compra), os totais por forma, banco e fornecedor, e o que está em aberto hoje.
 */
class RelatorioFinanceiro
{
    public function __construct(private readonly SaldoFornecedores $saldos)
    {
    }

    /**
     * Datas de um período pronto: semana (segunda a domingo), semana passada, mês, mês passado.
     * "personalizado" usa as datas digitadas (trocadas se vierem invertidas); sem datas válidas, vale o mês.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    public function periodo(?string $periodo, ?string $de = null, ?string $ate = null, ?CarbonImmutable $hoje = null): array
    {
        $hoje = ($hoje ?? CarbonImmutable::now('America/Sao_Paulo'))->startOfDay();

        [$inicio, $fim] = match ($periodo) {
            'semana' => [$hoje->startOfWeek(CarbonImmutable::MONDAY), $hoje->endOfWeek(CarbonImmutable::SUNDAY)],
            'semana_passada' => [$hoje->subWeek()->startOfWeek(CarbonImmutable::MONDAY), $hoje->subWeek()->endOfWeek(CarbonImmutable::SUNDAY)],
            'mes_passado' => [$hoje->subMonthNoOverflow()->startOfMonth(), $hoje->subMonthNoOverflow()->endOfMonth()],
            'personalizado' => $this->datas($de, $ate) ?? [$hoje->startOfMonth(), $hoje->endOfMonth()],
            default => [$hoje->startOfMonth(), $hoje->endOfMonth()],
        };

        $nome = in_array($periodo, ['semana', 'semana_passada', 'mes_passado', 'personalizado'], true) ? $periodo : 'mes';

        return [$inicio->startOfDay(), $fim->startOfDay(), $nome];
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable}|null */
    private function datas(?string $de, ?string $ate): ?array
    {
        $ler = function (?string $data): ?CarbonImmutable {
            if (!is_string($data) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
                return null;
            }

            $d = CarbonImmutable::createFromFormat('!Y-m-d', $data, 'America/Sao_Paulo');

            return $d && $d->format('Y-m-d') === $data ? $d : null;
        };

        $inicio = $ler($de);
        $fim = $ler($ate);

        if (!$inicio && !$fim) {
            return null;
        }

        $inicio ??= $fim;
        $fim ??= $inicio;

        return $inicio <= $fim ? [$inicio, $fim] : [$fim, $inicio];
    }

    public function gerar(CarbonImmutable $de, CarbonImmutable $ate, ?CarbonImmutable $hoje = null): array
    {
        $de = $de->startOfDay();
        $ate = $ate->startOfDay();
        $dentro = fn ($data) => $data !== null && ($d = $data->format('Y-m-d')) >= $de->format('Y-m-d') && $d <= $ate->format('Y-m-d');

        $linhas = $this->saldos->linhas($hoje);

        $pagamentos = $linhas
            ->flatMap(fn (array $l) => $l['compra']->pagamentos
                ->filter(fn ($pg) => $dentro($pg->data_pagamento))
                ->map(fn ($pg) => [
                    'data' => $pg->data_pagamento,
                    'fornecedor' => $l['fornecedor'],
                    'chave' => $l['chave'],
                    'requisicao' => $l['compra']->id,
                    'produto' => $l['compra']->product_name,
                    'tipo' => $pg->forma === 'a_vista' ? 'À vista' : 'Parcelado',
                    'meio' => $pg->meioRotulo() ?? 'Não informada',
                    'banco' => $pg->banco ?? '—',
                    'valor' => round((float) $pg->valor, 2),
                    'obs' => $pg->obs,
                    'usuario' => $pg->user?->name,
                    'id' => $pg->id,
                ]))
            ->sortBy(fn ($p) => $p['data']->format('Ymd') . str_pad((string) $p['id'], 10, '0', STR_PAD_LEFT))
            ->values();

        $compras = $linhas
            ->filter(fn (array $l) => $dentro($l['compra']->data_compra))
            ->sortBy(fn ($l) => $l['compra']->data_compra->format('Ymd') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        $abertas = $linhas->where('aberto', '>', 0)
            ->sortBy(fn ($l) => ($l['proximo_vencimento']?->format('Ymd') ?? '99999999') . $l['compra']->data_compra->format('Ymd'))
            ->values();

        $comprado = round($compras->sum('custo'), 2);
        $acrescimos = round($compras->sum('acrescimo'), 2);

        return [
            'de' => $de,
            'ate' => $ate,
            'resumo' => [
                'pago' => round($pagamentos->sum('valor'), 2),
                'pagamentos' => $pagamentos->count(),
                'compras' => $compras->count(),
                'comprado' => $comprado,
                'acrescimos' => $acrescimos,
                'custo_real' => round($comprado + $acrescimos, 2),
                'saldo_hoje' => round($linhas->sum('aberto'), 2),
                'vencido_hoje' => round($linhas->sum('vencido'), 2),
                'abertas' => $abertas->count(),
            ],
            'pagamentos' => $pagamentos->all(),
            'compras' => $compras->all(),
            'por_meio' => $this->agrupar($pagamentos, 'meio'),
            'por_banco' => $this->agrupar($pagamentos, 'banco'),
            'por_fornecedor' => $this->porFornecedor($linhas, $compras, $pagamentos),
            'abertas' => $abertas->all(),
        ];
    }

    /** Total e quantidade de pagamentos por forma ou por banco, do maior para o menor. */
    private function agrupar(Collection $pagamentos, string $campo): array
    {
        return $pagamentos->groupBy($campo)
            ->map(fn (Collection $g, $nome) => ['nome' => (string) $nome, 'pagamentos' => $g->count(), 'valor' => round((float) $g->sum('valor'), 2)])
            ->sortBy([['valor', 'desc'], ['nome', 'asc']])
            ->values()
            ->all();
    }

    /** Cada fornecedor que comprou ou recebeu no período: comprado e pago no período e o saldo devedor de hoje. */
    private function porFornecedor(Collection $linhas, Collection $compras, Collection $pagamentos): array
    {
        $saldoHoje = $this->saldos->resumo($linhas)->keyBy('chave');
        $chaves = $compras->pluck('chave')->merge($pagamentos->pluck('chave'))->unique();

        return $chaves
            ->map(fn ($chave) => [
                'nome' => $saldoHoje[$chave]['nome'],
                'chave' => $chave,
                'compras' => $compras->where('chave', $chave)->count(),
                'comprado' => round($compras->where('chave', $chave)->sum('custo'), 2),
                'acrescimos' => round($compras->where('chave', $chave)->sum('acrescimo'), 2),
                'pago' => round($pagamentos->where('chave', $chave)->sum('valor'), 2),
                'saldo' => $saldoHoje[$chave]['saldo'],
            ])
            ->sortBy([['pago', 'desc'], ['comprado', 'desc'], ['nome', 'asc']])
            ->values()
            ->all();
    }
}
