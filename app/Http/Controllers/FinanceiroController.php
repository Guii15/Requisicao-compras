<?php

namespace App\Http\Controllers;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Services\SaldoFornecedores;
use App\Support\Dinheiro;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Contas a pagar por fornecedor: saldo devedor, compras em aberto e baixa por pagamento. */
class FinanceiroController extends Controller
{
    public function index(Request $request, SaldoFornecedores $saldos)
    {
        $fornecedores = $saldos->resumo();

        $totais = [
            'comprado' => round($fornecedores->sum('comprado'), 2),
            'pago' => round($fornecedores->sum('pago'), 2),
            'saldo' => round($fornecedores->sum('saldo'), 2),
        ];

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $chaveBusca = $saldos->chave($q);
            $fornecedores = $fornecedores
                ->filter(fn ($f) => str_contains($f['chave'], $chaveBusca) || stripos($f['nome'], $q) !== false)
                ->values();
        }

        return view('financeiro.index', compact('fornecedores', 'totais', 'q'));
    }

    public function fornecedor(string $chave, SaldoFornecedores $saldos)
    {
        $compras = $saldos->comprasDoFornecedor($chave);

        abort_if($compras->isEmpty(), 404);

        $nome = $saldos->nome($chave, $compras->pluck('compra'));
        $totais = [
            'comprado' => round($compras->sum('custo'), 2),
            'pago' => round($compras->sum('pago'), 2),
            'saldo' => round($compras->sum('aberto'), 2),
        ];

        return view('financeiro.fornecedor', compact('chave', 'nome', 'compras', 'totais'));
    }

    public function pagar(Request $request, PurchaseRequest $purchaseRequest, SaldoFornecedores $saldos)
    {
        $volta = redirect()->route('financeiro.fornecedor', $saldos->chave($purchaseRequest->supplier));

        if (!$saldos->conta($purchaseRequest)) {
            return redirect()->route('financeiro.index')->with('aviso', 'Esta compra não entra no financeiro — nada foi alterado.');
        }

        $situacao = $saldos->situacao($purchaseRequest->load('pagamentos'));

        if ($situacao['aberto'] <= 0) {
            return $volta->with('aviso', 'Esta compra já está paga — nada foi alterado.');
        }

        if ($request->filled('valor')) {
            $request->merge(['valor' => Dinheiro::decimal($request->input('valor'))]);
        }

        $dados = $request->validate([
            'forma' => 'required|in:a_vista,parcelado',
            'valor' => 'required_if:forma,parcelado|nullable|numeric|min:0.01',
            'data_pagamento' => 'required|date',
            'obs' => 'nullable|string|max:500',
        ], [
            'forma.required' => 'Informe se o pagamento é à vista ou parcelado.',
            'forma.in' => 'Forma de pagamento inválida.',
            'valor.required_if' => 'Informe o valor pago.',
            'valor.numeric' => 'O valor precisa ser um número.',
            'valor.min' => 'O valor precisa ser maior que zero.',
            'data_pagamento.required' => 'Informe a data do pagamento.',
            'data_pagamento.date' => 'Data do pagamento inválida.',
        ]);

        // À vista quita o que falta; parcelado abate só o que foi informado.
        $valor = $dados['forma'] === 'a_vista' ? $situacao['aberto'] : round((float) $dados['valor'], 2);

        if ($valor > $situacao['aberto'] + 0.004) {
            throw ValidationException::withMessages([
                'valor' => 'O valor é maior do que falta pagar nesta compra (' . Dinheiro::brl($situacao['aberto']) . ').',
            ]);
        }

        PagamentoCompra::create([
            'purchase_request_id' => $purchaseRequest->id,
            'valor' => $valor,
            'forma' => $dados['forma'],
            'data_pagamento' => $dados['data_pagamento'],
            'obs' => $dados['obs'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return $volta->with('success', 'Pagamento de ' . Dinheiro::brl($valor) . ' registrado.');
    }

    public function desfazer(PagamentoCompra $pagamento, SaldoFornecedores $saldos)
    {
        $chave = $saldos->chave($pagamento->compra?->supplier);
        $valor = $pagamento->valor;

        $pagamento->delete();

        return redirect()->route('financeiro.fornecedor', $chave)
            ->with('success', 'Pagamento de ' . Dinheiro::brl($valor) . ' desfeito; o valor voltou para o saldo.');
    }
}
