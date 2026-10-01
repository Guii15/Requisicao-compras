<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Contas a pagar por fornecedor. Cada compra feita (aprovada, com data e preço) soma no saldo do
 * fornecedor; cada pagamento registrado dá baixa. O fornecedor é agrupado pelo nome normalizado
 * (caixa, acento, pontuação e LTDA não diferenciam), sem depender da ligação com a tabela fornecedores.
 */
class SaldoFornecedores
{
    /** Chave do grupo das compras sem fornecedor informado. */
    public const SEM_FORNECEDOR = '-';

    /** Só conta compra aprovada com os dados da compra preenchidos (as importadas do histórico ficam de fora). */
    public function conta(PurchaseRequest $compra): bool
    {
        return $compra->status === 'aprovado' && $compra->data_compra !== null && $compra->preco_unitario !== null;
    }

    public function chave(?string $fornecedor): string
    {
        return Fornecedor::normalizar($fornecedor) ?: self::SEM_FORNECEDOR;
    }

    /**
     * Quanto custou a compra: o total digitado pelo comprador ou, sem ele, preço unitário × quantidade.
     * Em recebimento parcial o total digitado vale para o pedido inteiro, então cada parte usa
     * preço unitário × a quantidade dela (assim o valor não é contado duas vezes).
     */
    public function custo(PurchaseRequest $compra): float
    {
        $partido = $compra->quantidade_original !== null;

        if (!$partido && $compra->valor !== null && (float) $compra->valor > 0) {
            return round((float) $compra->valor, 2);
        }

        return round((float) $compra->preco_unitario * (int) $compra->quantity, 2);
    }

    /**
     * Custo, pago e em aberto da compra, a condição negociada e os vencimentos.
     *
     * Parcelado em N vezes: as parcelas vencem de mês em mês a partir do 1º vencimento. O que já foi pago
     * quita as parcelas na ordem (um pagamento maior que uma parcela adianta as seguintes). "Vencido" é o
     * que já deveria ter sido pago até hoje e ainda não foi.
     *
     * @return array{custo: float, pago: float, aberto: float, situacao: string, condicao: ?string, condicao_codigo: ?string, parcelas: int, valor_parcela: float, proximo_vencimento: ?CarbonImmutable, dias_ate_vencimento: ?int, vencido: float, vencida: bool, parcela_sugerida: float}
     */
    public function situacao(PurchaseRequest $compra, ?CarbonImmutable $hoje = null): array
    {
        $hoje = ($hoje ?? CarbonImmutable::now('America/Sao_Paulo'))->startOfDay();

        $custo = $this->custo($compra);
        $pago = round((float) $compra->pagamentos->sum('valor'), 2);
        $aberto = round(max(0, $custo - $pago), 2);

        $parcelado = $compra->condicao_pagamento === 'parcelado' && (int) $compra->parcelas >= 2;
        $n = $parcelado ? (int) $compra->parcelas : 1;
        $valorParcela = round($custo / $n, 2);

        $primeiro = $compra->primeiro_vencimento
            ? CarbonImmutable::parse($compra->primeiro_vencimento->format('Y-m-d'), 'America/Sao_Paulo')
            : null;

        $proximo = null;
        $vencido = 0.0;

        if ($primeiro && $aberto > 0) {
            $cobertas = $valorParcela > 0 ? min($n - 1, (int) floor(($pago + 0.004) / $valorParcela)) : 0;
            $proximo = $primeiro->addMonthsNoOverflow($cobertas);

            $venceram = 0;
            for ($k = 0; $k < $n; $k++) {
                if ($primeiro->addMonthsNoOverflow($k) < $hoje) {
                    $venceram++;
                }
            }

            $devido = $venceram >= $n ? $custo : round($venceram * $valorParcela, 2);
            $vencido = round(max(0, min($aberto, $devido - $pago)), 2);
        }

        return [
            'custo' => $custo,
            'pago' => $pago,
            'aberto' => $aberto,
            'situacao' => $aberto <= 0 ? 'Pago' : ($pago > 0 ? 'Parcial' : 'Em aberto'),
            'condicao' => match ($compra->condicao_pagamento) {
                'a_vista' => 'À vista',
                'parcelado' => $parcelado ? "Parcelado em {$n}x" : 'Parcelado',
                default => null,
            },
            'condicao_codigo' => $compra->condicao_pagamento,
            'parcelas' => $n,
            'valor_parcela' => $valorParcela,
            'proximo_vencimento' => $proximo,
            'dias_ate_vencimento' => $proximo ? (int) $hoje->diffInDays($proximo, false) : null,
            'vencido' => $vencido,
            'vencida' => $vencido > 0,
            'parcela_sugerida' => $aberto > 0 ? round(min($valorParcela, $aberto), 2) : 0.0,
        ];
    }

    /** @return Collection<int, PurchaseRequest> */
    private function compras(): Collection
    {
        return PurchaseRequest::with(['pagamentos', 'fornecedor'])
            ->where('status', 'aprovado')
            ->whereNotNull('data_compra')
            ->whereNotNull('preco_unitario')
            ->get();
    }

    /** Quantas compras ainda têm algo a pagar (número da aba Aguardando). */
    public function quantidadeAguardando(): int
    {
        return $this->linhas()->where('aberto', '>', 0)->count();
    }

    /**
     * Todas as compras que contam no financeiro, cada uma com o fornecedor, o custo, o que já foi pago
     * e o que falta.
     *
     * @return Collection<int, array{compra: PurchaseRequest, chave: string, fornecedor: string, custo: float, pago: float, aberto: float, situacao: string, ultimo_pagamento: mixed}>
     */
    public function linhas(?CarbonImmutable $hoje = null): Collection
    {
        $compras = $this->compras();

        $nomes = $compras
            ->groupBy(fn (PurchaseRequest $c) => (string) $this->chave($c->supplier))
            ->map(fn (Collection $grupo, $chave) => $this->nome((string) $chave, $grupo));

        return $compras->map(function (PurchaseRequest $c) use ($nomes, $hoje) {
            $chave = $this->chave($c->supplier);

            return [
                'compra' => $c,
                'chave' => $chave,
                'fornecedor' => $nomes[$chave],
            ] + $this->situacao($c, $hoje) + [
                'ultimo_pagamento' => $c->pagamentos->max('data_pagamento'),
            ];
        })->values();
    }

    /**
     * Uma linha por fornecedor, do maior saldo devedor para o menor.
     *
     * @param  Collection|null  $linhas  resultado de linhas() (para não consultar o banco de novo)
     * @return Collection<int, array{chave: string, nome: string, compras: int, comprado: float, pago: float, saldo: float}>
     */
    public function resumo(?Collection $linhas = null): Collection
    {
        return ($linhas ?? $this->linhas())
            ->groupBy('chave')
            ->map(fn (Collection $grupo, $chave) => [
                'chave' => (string) $chave,
                'nome' => $grupo->first()['fornecedor'],
                'compras' => $grupo->count(),
                'comprado' => round($grupo->sum('custo'), 2),
                'pago' => round($grupo->sum('pago'), 2),
                'saldo' => round($grupo->sum('aberto'), 2),
            ])
            ->sortBy([['saldo', 'desc'], ['nome', 'asc']])
            ->values();
    }

    /**
     * As compras de um fornecedor, com o que já foi pago e o que falta.
     *
     * @return Collection<int, array{compra: PurchaseRequest, custo: float, pago: float, aberto: float, situacao: string}>
     */
    public function comprasDoFornecedor(string $chave): Collection
    {
        return $this->linhas()
            ->where('chave', $chave)
            ->sortByDesc(fn (array $l) => $l['compra']->data_compra?->format('Ymd') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->values();
    }

    /** Nome para mostrar: o cadastrado (se houver) ou a grafia mais usada nas compras. */
    public function nome(string $chave, Collection $compras): string
    {
        if ($chave === self::SEM_FORNECEDOR) {
            return 'Sem fornecedor';
        }

        $cadastrado = $compras->first(fn (PurchaseRequest $c) => $c->fornecedor)?->fornecedor?->nome;

        if ($cadastrado) {
            return $cadastrado;
        }

        // Grafia mais usada; no empate, ordem alfabética (para o nome não mudar de uma tela para outra).
        $grafias = $compras->map(fn (PurchaseRequest $c) => trim((string) $c->supplier))->countBy()->all();
        uksort($grafias, fn ($x, $y) => [$grafias[$y], $x] <=> [$grafias[$x], $y]);
        $nome = (string) array_key_first($grafias);

        // Tudo em maiúsculas ou minúsculas vira "Joyce"; grafias já bem escritas ficam como estão.
        if ($nome === mb_strtoupper($nome) || $nome === mb_strtolower($nome)) {
            $nome = mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');
        }

        return $nome;
    }
}
