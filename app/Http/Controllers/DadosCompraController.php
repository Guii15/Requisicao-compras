<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Services\FornecedorResolver;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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

    /** A tela "Compras" antiga saiu: tudo fica em Compras Feitas. O endereço antigo leva para o filtro "Falta registrar". */
    public function index(Request $request)
    {
        return redirect()->route('admin.compras.feitas', $request->query('situacao') === 'com_dados' ? [] : ['situacao' => 'falta']);
    }

    public function feitas(Request $request)
    {
        // "Falta registrar" mostra toda aprovada sem data ou preço (inclusive as de antes do corte), para registrar aqui mesmo.
        $situacao = $request->query('situacao') === 'falta' ? 'falta' : null;
        $semDados = fn ($q) => $q->whereNull('data_compra')->orWhereNull('preco_unitario');

        $query = $situacao === 'falta'
            ? PurchaseRequest::with('user')->where('status', 'aprovado')->where($semDados)
            : PurchaseRequest::with('user')->naListaDeComprasFeitas();

        // ?abrir=ID (atalho do histórico e dos endereços antigos): mostra só a requisição desse item e já abre a janela dele.
        $abrir = PurchaseRequest::where('status', 'aprovado')->find((int) $request->query('abrir'));
        if ($abrir) {
            $situacao = null;
            $query = PurchaseRequest::with('user')->where('status', 'aprovado')->where('grupo_id', $abrir->grupo_id);
        }

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

        $requests = $this->paginarAgrupadoPorGrupoId($query, 20, 'page', ['user', 'fotosConferencia'], 'updated_at')->withQueryString();

        // As aprovadas antes do corte e sem data/preço não aparecem aqui (só em Compras): o aviso explica isso.
        $totalSemDados = PurchaseRequest::aprovadasAntigasSemDados()->count();
        $dataCorte = PurchaseRequest::inicioComprasFeitas()->timezone('America/Sao_Paulo')->format('d/m/Y');

        $totalFalta = PurchaseRequest::where('status', 'aprovado')->where($semDados)->numRequisicoes();

        $fornecedoresUsados = PurchaseRequest::whereNotNull('supplier')->where('supplier', '!=', '')->distinct()->orderBy('supplier')->pluck('supplier');

        return view('admin.compras.feitas', compact('requests', 'totalSemDados', 'dataCorte', 'situacao', 'totalFalta', 'fornecedoresUsados', 'abrir'));
    }

    /** A tela separada de "Dados da compra" saiu: o endereço antigo abre a janela do item em Compras Feitas. */
    public function edit(PurchaseRequest $purchaseRequest)
    {
        $this->garantirAprovada($purchaseRequest);

        return redirect()->route('admin.compras.feitas', ['abrir' => $purchaseRequest->id]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest, FornecedorResolver $fornecedores)
    {
        $this->garantirAprovada($purchaseRequest);

        // Envio feito pela janela da lista de Compras Feitas: volta para a lista (e reabre a janela se algo falhar).
        $daLista = $request->input('origem') === 'lista';
        if ($daLista) {
            session()->flash('compra_aberta', $purchaseRequest->id);
        }

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
            'quantity'          => 'nullable|integer|min:1|max:1000000',
            'empresa'           => 'nullable|string|max:100',
            'condicao_pagamento' => 'required|in:a_vista,parcelado',
            'parcelas'          => 'required_if:condicao_pagamento,parcelado|nullable|integer|min:2|max:36',
            'primeiro_vencimento' => 'nullable|date',
            'pedido_compra'     =>'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'pedido_compra.mimes'        => 'O pedido de compra precisa ser PDF ou imagem (JPG, PNG, WEBP).',
            'pedido_compra.max'          => 'O pedido de compra pode ter no máximo 10 MB.',
            'quantity.integer'           => 'A quantidade precisa ser um número inteiro.',
            'quantity.min'               => 'A quantidade precisa ser pelo menos 1.',
            'quantity.max'               => 'Quantidade grande demais.',
            'condicao_pagamento.required' => 'Informe a condição de pagamento negociada (à vista ou parcelado).',
            'condicao_pagamento.in'      => 'Condição de pagamento inválida.',
            'parcelas.required_if'       => 'Informe em quantas parcelas.',
            'parcelas.integer'           => 'O número de parcelas precisa ser um número inteiro.',
            'parcelas.min'               => 'Parcelado precisa ter pelo menos 2 parcelas.',
            'parcelas.max'               => 'O máximo é 36 parcelas.',
            'primeiro_vencimento.date'   => 'Data de vencimento inválida.',
        ]);

        // Quantidade realmente comprada: a Conferência e o Financeiro leem este mesmo número.
        $novaQuantidade = isset($dados['quantity']) ? (int) $dados['quantity'] : $purchaseRequest->quantity;

        if ($novaQuantidade !== $purchaseRequest->quantity) {
            if ($purchaseRequest->status_conferencia !== null) {
                throw ValidationException::withMessages(['quantity' => 'Este item já foi conferido; a quantidade não pode mais ser alterada aqui.']);
            }

            if ($purchaseRequest->quantidade_original !== null || $purchaseRequest->restante_de_id !== null) {
                throw ValidationException::withMessages(['quantity' => 'Este item é de um recebimento parcial; ajuste a quantidade pelo botão Editar da Conferência.']);
            }
        }

        $digitado = trim($dados['supplier']);
        $fornecedor = $fornecedores->exato($digitado);

        $atualizacao = [
            'quantity'          => $novaQuantidade,
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
                \App\Support\LixeiraDeArquivos::descartar(self::DISCO, $caminhoAntigo);
            }
        }

        // Só mexe na empresa quando o campo veio no envio (formulários antigos não apagam o que já está salvo).
        if ($request->has('empresa')) {
            $atualizacao['empresa'] = PurchaseRequest::nomeCanonicoEmpresa($dados['empresa'] ?? null);
        }

        $purchaseRequest->update($atualizacao);

        if ($daLista) {
            session()->forget('compra_aberta');

            return back()->with('success', 'Dados da compra de "' . $purchaseRequest->product_name . '" salvos.');
        }

        return redirect()->route('admin.compras.feitas', ['abrir' => $purchaseRequest->id])
            ->with('success', 'Dados da compra salvos.');
    }

    /** O admin tira o pedido de compra anexado (também limpa a referência de um arquivo que já não existe). */
    public function removerPedido(PurchaseRequest $purchaseRequest)
    {
        if (!$purchaseRequest->pedido_compra_path) {
            return back()->with('aviso', 'Esta compra não tem pedido de compra anexado.');
        }

        $purchaseRequest->removerArquivo('pedido_compra_path', 'pedido_compra_nome', self::DISCO);

        return back()->with('success', 'Pedido de compra removido.');
    }

    public function baixarPedido(PurchaseRequest $purchaseRequest)
    {
        $user = auth()->user();
        $podeVer = $purchaseRequest->user_id === $user->id
            || $user->isAdmin()
            || in_array($user->role, ['conferente', 'entrada', 'financeiro', 'rma'], true);

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
