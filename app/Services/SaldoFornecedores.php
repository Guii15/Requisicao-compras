<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
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

    /** @return array{custo: float, pago: float, aberto: float, situacao: string} */
    public function situacao(PurchaseRequest $compra): array
    {
        $custo = $this->custo($compra);
        $pago = round((float) $compra->pagamentos->sum('valor'), 2);
        $aberto = round(max(0, $custo - $pago), 2);

        return [
            'custo' => $custo,
            'pago' => $pago,
            'aberto' => $aberto,
            'situacao' => $aberto <= 0 ? 'Pago' : ($pago > 0 ? 'Parcial' : 'Em aberto'),
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

    /**
     * Uma linha por fornecedor, do maior saldo devedor para o menor.
     *
     * @return Collection<int, array{chave: string, nome: string, compras: int, comprado: float, pago: float, saldo: float}>
     */
    public function resumo(): Collection
    {
        return $this->compras()
            ->groupBy(fn (PurchaseRequest $c) => $this->chave($c->supplier))
            ->map(function (Collection $grupo, $chave) {
                $linhas = $grupo->map(fn (PurchaseRequest $c) => $this->situacao($c));

                return [
                    'chave' => (string) $chave,
                    'nome' => $this->nome((string) $chave, $grupo),
                    'compras' => $grupo->count(),
                    'comprado' => round($linhas->sum('custo'), 2),
                    'pago' => round($linhas->sum('pago'), 2),
                    'saldo' => round($linhas->sum('aberto'), 2),
                ];
            })
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
        return $this->compras()
            ->filter(fn (PurchaseRequest $c) => $this->chave($c->supplier) === $chave)
            ->sortByDesc(fn (PurchaseRequest $c) => $c->data_compra?->format('Ymd') . str_pad((string) $c->id, 10, '0', STR_PAD_LEFT))
            ->map(fn (PurchaseRequest $c) => ['compra' => $c] + $this->situacao($c))
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
