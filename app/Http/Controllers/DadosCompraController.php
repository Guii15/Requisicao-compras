<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Services\FornecedorResolver;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Dados da compra que o comprador (Admin) registra DEPOIS de aprovar a requisicao:
 * data da compra, preco unitario, total (digitado a mao pelo admin, nao e' calculado),
 * codigo do produto no fornecedor, fornecedor, data da coleta e o pedido de compra anexado.
 */
class DadosCompraController extends Controller
{
    use AgrupaRequisicoesPorGrupoId, BuscaCaseInsensitive;

    private const DISCO = 'local';

    public function index(Request $request)
    {
        $query = PurchaseRequest::with('user')->where('status', 'aprovado');

        $situacao = $request->query('situacao');
        if ($situacao === 'sem_dados') {
            $query->where(fn ($q) => $q->whereNull('data_compra')->orWhereNull('preco_unitario'));
        } elseif ($situacao === 'com_dados') {
            $query->whereNotNull('data_compra')->whereNotNull('preco_unitario');
        }

        if ($request->filled('produto')) {
            $this->whereLikeInsensitive($query, 'product_name', $request->produto);
        }

        $itens = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        $totalSemDados = PurchaseRequest::where('status', 'aprovado')
            ->where(fn ($q) => $q->whereNull('data_compra')->orWhereNull('preco_unitario'))
            ->count();

        return view('admin.compras.index', compact('itens', 'situacao', 'totalSemDados'));
    }

    public function feitas(Request $request)
    {
        $query = PurchaseRequest::with('user')->where('status', 'aprovado')
            ->whereNotNull('data_compra')->whereNotNull('preco_unitario');

        if ($request->filled('produto')) {
            $this->whereLikeInsensitive($query, 'product_name', $request->produto);
        }

        if ($request->filled('vendedor')) {
            $this->whereLikeInsensitive($query, 'requester_name', $request->vendedor);
        }

        if ($request->filled('data_inicial')) {
            $query->whereDate('data_compra', '>=', $request->data_inicial);
        }

        if ($request->filled('data_final')) {
            $query->whereDate('data_compra', '<=', $request->data_final);
        }

        $requests = $this->paginarAgrupadoPorGrupoId($query, 20, 'page', ['user'], 'updated_at')->withQueryString();

        return view('admin.compras.feitas', compact('requests'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $this->garantirAprovada($purchaseRequest);

        return view('admin.compras.edit', ['item' => $purchaseRequest]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest, FornecedorResolver $fornecedores)
    {
        $this->garantirAprovada($purchaseRequest);

        if ($request->filled('preco_unitario')) {
            $request->merge(['preco_unitario' => $this->decimalBrasileiro($request->input('preco_unitario'))]);
        }

        if ($request->filled('preco_caixa')) {
            $request->merge(['preco_caixa' => $this->decimalBrasileiro($request->input('preco_caixa'))]);
        }

        if ($request->filled('valor')) {
            $request->merge(['valor' => $this->decimalBrasileiro($request->input('valor'))]);
        }

        $dados = $request->validate([
            'data_compra'       => 'required|date',
            'preco_unitario'    => 'required|numeric|min:0',
            'preco_caixa'       => 'nullable|numeric|min:0',
            'valor'             => 'nullable|numeric|min:0',
            'codigo_fornecedor' => 'nullable|string|max:255',
            'supplier'          => 'required|string|max:255',
            'condicao_pagamento' => 'required|in:a_vista,parcelado',
            'parcelas'          => 'required_if:condicao_pagamento,parcelado|nullable|integer|min:2|max:36',
            'primeiro_vencimento' => 'nullable|date',
            'pedido_compra'     =>'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'pedido_compra.mimes'        => 'O pedido de compra precisa ser PDF ou imagem (JPG, PNG, WEBP).',
            'pedido_compra.max'          => 'O pedido de compra pode ter no máximo 10 MB.',
            'condicao_pagamento.required' => 'Informe a condição de pagamento negociada (à vista ou parcelado).',
            'condicao_pagamento.in'      => 'Condição de pagamento inválida.',
            'parcelas.required_if'       => 'Informe em quantas parcelas.',
            'parcelas.integer'           => 'O número de parcelas precisa ser um número inteiro.',
            'parcelas.min'               => 'Parcelado precisa ter pelo menos 2 parcelas.',
            'parcelas.max'               => 'O máximo é 36 parcelas.',
            'primeiro_vencimento.date'   => 'Data de vencimento inválida.',
        ]);

        $digitado = trim($dados['supplier']);
        $fornecedor = $fornecedores->exato($digitado);

        $atualizacao = [
            'data_compra'       => $dados['data_compra'],
            'preco_unitario'    => $dados['preco_unitario'],
            'preco_caixa'       => $dados['preco_caixa'] ?? null,
            'valor'             => $dados['valor'] ?? null,
            'codigo_fornecedor' => $dados['codigo_fornecedor'] ?? null,
            'condicao_pagamento' => $dados['condicao_pagamento'],
            'parcelas'          => $dados['condicao_pagamento'] === 'parcelado' ? (int) $dados['parcelas'] : null,
            'primeiro_vencimento' => $dados['primeiro_vencimento'] ?? null,
            'supplier'          => $fornecedor?->nome ?? mb_convert_case(mb_strtolower($digitado), MB_CASE_TITLE, 'UTF-8'),
            'fornecedor_id'     => $fornecedor?->id,
            'supplier_original' => $purchaseRequest->supplier_original ?? ($purchaseRequest->supplier ?: $digitado),
        ];

        if ($request->hasFile('pedido_compra')) {
            $arquivo = $request->file('pedido_compra');
            $caminhoAntigo = $purchaseRequest->pedido_compra_path;

            $atualizacao['pedido_compra_path'] = $arquivo->store('pedidos-compra', self::DISCO);
            $atualizacao['pedido_compra_nome'] = $arquivo->getClientOriginalName();

            if ($caminhoAntigo && !$purchaseRequest->arquivoUsadoPorOutro('pedido_compra_path', $caminhoAntigo)) {
                Storage::disk(self::DISCO)->delete($caminhoAntigo);
            }
        }

        $purchaseRequest->update($atualizacao);

        return redirect()->route('admin.compras.edit', $purchaseRequest)
            ->with('success', 'Dados da compra salvos.');
    }

    public function baixarPedido(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        $podeVer = $purchaseRequest->user_id === $user->id
            || $user->isAdmin()
            || in_array($user->role, ['conferente', 'entrada', 'financeiro'], true);

        if (!$podeVer) {
            abort(403);
        }

        $caminho = $purchaseRequest->pedido_compra_path;

        if (!$caminho || !Storage::disk(self::DISCO)->exists($caminho)) {
            abort(404, 'Nenhum pedido de compra anexado.');
        }

        return Storage::disk(self::DISCO)->response($caminho, $purchaseRequest->pedido_compra_nome ?? basename($caminho));
    }

    private function garantirAprovada(PurchaseRequest $purchaseRequest): void
    {
        if ($purchaseRequest->status !== 'aprovado') {
            abort(404);
        }
    }

    /** Converte "1.250,50" em "1250.50"; valor ja' com ponto decimal passa direto. */
    private function decimalBrasileiro(string $valor): string
    {
        $valor = trim(str_replace(['R$', ' '], '', $valor));

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return $valor;
    }
}
