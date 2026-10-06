<?php

namespace App\Http\Controllers;

use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Services\PainelFinanceiro;
use App\Services\SaldoFornecedores;
use App\Support\Dinheiro;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Contas a pagar por fornecedor: painel, compras aguardando/pagas, saldo por fornecedor e baixa por pagamento. */
class FinanceiroController extends Controller
{
    private const POR_PAGINA = 25;

    public function index(Request $request, PainelFinanceiro $painel)
    {
        $d = $painel->dados(null, $request->query('empresa'));

        return view('financeiro.index', ['d' => $d, 'empresas' => collect($d['empresas']), 'empresaSel' => $d['empresa']]);
    }

    public function aguardando(Request $request, SaldoFornecedores $saldos)
    {
        $linhas = $saldos->somenteEmpresa($saldos->linhas(), $request->query('empresa'))
            ->where('aberto', '>', 0)
            ->sortBy(fn ($l) => $l['compra']->data_compra->format('Ymd') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        return $this->lista($request, $linhas, 'aguardando');
    }

    public function pagos(Request $request, SaldoFornecedores $saldos)
    {
        $linhas = $saldos->somenteEmpresa($saldos->linhas(), $request->query('empresa'))
            ->where('aberto', '<=', 0)
            ->sortByDesc(fn ($l) => ($l['ultimo_pagamento']?->format('Ymd') ?? '0') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        return $this->lista($request, $linhas, 'pagos');
    }

    public function fornecedores(Request $request, SaldoFornecedores $saldos)
    {
        $todas = $saldos->linhas();
        $empresas = $saldos->empresas($todas);
        $empresaSel = $this->empresaEscolhida($request);
        $fornecedores = $saldos->resumo($saldos->somenteEmpresa($todas, $empresaSel));

        $totais = [
            'comprado' => round($fornecedores->sum('comprado'), 2),
            'pago' => round($fornecedores->sum('pago'), 2),
            'saldo' => round($fornecedores->sum('saldo'), 2),
        ];

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            $fornecedores = $fornecedores
                ->filter(fn ($f) => $this->contem($f['nome'], $q) || str_contains($f['chave'], $saldos->chave($q)))
                ->values();
        }

        return view('financeiro.fornecedores', compact('fornecedores', 'totais', 'q', 'empresas', 'empresaSel'));
    }

    public function fornecedor(Request $request, string $chave, SaldoFornecedores $saldos)
    {
        $todas = $saldos->linhas();
        $empresas = $saldos->empresas($todas);
        $empresaSel = $this->empresaEscolhida($request);
        $compras = $saldos->comprasDoFornecedor($chave, $empresaSel, $todas);

        if ($compras->isEmpty()) {
            // O fornecedor existe, mas não comprou nessa empresa: volta para a lista em vez de dar erro.
            abort_if($empresaSel === null || $saldos->comprasDoFornecedor($chave, null, $todas)->isEmpty(), 404);

            return redirect()->route('financeiro.fornecedores', ['empresa' => $empresaSel])
                ->with('aviso', 'Este fornecedor não tem compras nessa empresa.');
        }

        $nome = $compras->first()['fornecedor'];
        $totais = [
            'comprado' => round($compras->sum('custo'), 2),
            'pago' => round($compras->sum('pago'), 2),
            'saldo' => round($compras->sum('aberto'), 2),
        ];

        return view('financeiro.fornecedor', compact('chave', 'nome', 'compras', 'totais', 'empresas', 'empresaSel'));
    }

    public function pagar(Request $request, PurchaseRequest $purchaseRequest, SaldoFornecedores $saldos)
    {
        // Volta para a tela de onde o pagamento foi feito (lista ou fornecedor).
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($purchaseRequest->supplier))));

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
            'meio' => ['required', Rule::in(array_keys(PagamentoCompra::MEIOS))],
            'banco' => 'required_unless:meio,dinheiro|nullable|string|max:100',
            'data_pagamento' => 'required|date',
            'obs' => 'nullable|string|max:500',
        ], [
            'forma.required' => 'Informe se o pagamento é à vista ou parcelado.',
            'forma.in' => 'Forma de pagamento inválida.',
            'valor.required_if' => 'Informe o valor pago.',
            'valor.numeric' => 'O valor precisa ser um número.',
            'valor.min' => 'O valor precisa ser maior que zero.',
            'meio.required' => 'Informe a forma de pagamento (PIX, boleto, transferência...).',
            'meio.in' => 'Forma de pagamento inválida.',
            'banco.required_unless' => 'Informe de qual banco saiu o pagamento.',
            'banco.max' => 'O nome do banco pode ter no máximo 100 caracteres.',
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
            'meio' => $dados['meio'],
            'banco' => $dados['meio'] === 'dinheiro' ? null : (trim((string) ($dados['banco'] ?? '')) ?: null),
            'data_pagamento' => $dados['data_pagamento'],
            'obs' => $dados['obs'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return $volta->with('success', 'Pagamento de ' . Dinheiro::brl($valor) . ' registrado.');
    }

    public function desfazer(PagamentoCompra $pagamento, SaldoFornecedores $saldos)
    {
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($pagamento->compra?->supplier))));
        $valor = $pagamento->valor;

        $pagamento->delete();

        return $volta->with('success', 'Pagamento de ' . Dinheiro::brl($valor) . ' desfeito; o valor voltou para o saldo.');
    }

    /** Lista paginada de compras (abas Aguardando e Pagos), com busca por fornecedor, produto, comprador ou nº da requisição. */
    private function lista(Request $request, Collection $linhas, string $modo)
    {
        $q = trim((string) $request->query('q', ''));
        $empresaSel = $this->empresaEscolhida($request);
        $empresas = app(SaldoFornecedores::class)->empresas(app(SaldoFornecedores::class)->linhas());

        if ($q !== '') {
            $linhas = $linhas->filter(fn ($l) => $this->contem($l['fornecedor'], $q)
                || $this->contem((string) $l['compra']->product_name, $q)
                || $this->contem((string) $l['compra']->requester_name, $q)
                || ltrim($q, '#') === (string) $l['compra']->id)->values();
        }

        $total = round($linhas->sum($modo === 'aguardando' ? 'aberto' : 'pago'), 2);

        $pagina = max(1, (int) $request->query('page', 1));
        $itens = new LengthAwarePaginator(
            $linhas->forPage($pagina, self::POR_PAGINA)->values(),
            $linhas->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('financeiro.compras', compact('itens', 'modo', 'q', 'total', 'empresas', 'empresaSel'));
    }

    /** A empresa escolhida no filtro (a chave), ou null para "todas". */
    private function empresaEscolhida(Request $request): ?string
    {
        $empresa = trim((string) $request->query('empresa', ''));

        return $empresa === '' ? null : $empresa;
    }

    /** Busca sem diferenciar maiúsculas, acentos ou espaços nas pontas. */
    private function contem(string $texto, string $busca): bool
    {
        $normalizar = fn (string $s) => mb_strtolower(Str::ascii(trim($s)));

        return str_contains($normalizar($texto), $normalizar($busca));
    }
}
