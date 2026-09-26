<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Dados da compra que o comprador (Admin) registra DEPOIS de aprovar a requisicao:
 * data da compra, preco unitario (o total e' calculado: quantidade x unitario),
 * codigo do produto no fornecedor, fornecedor, data da coleta e o pedido de compra anexado.
 */
class DadosCompraController extends Controller
{
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
            $query->where('product_name', 'like', '%' . $request->produto . '%');
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
            $query->where('product_name', 'like', '%' . $request->produto . '%');
        }

        $itens = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('admin.compras.feitas', compact('itens'));
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $this->garantirAprovada($purchaseRequest);

        return view('admin.compras.edit', ['item' => $purchaseRequest]);
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->garantirAprovada($purchaseRequest);

        if ($request->filled('preco_unitario')) {
            $request->merge(['preco_unitario' => $this->decimalBrasileiro($request->input('preco_unitario'))]);
        }

        $dados = $request->validate([
            'data_compra'       => 'required|date',
            'preco_unitario'    => 'required|numeric|min:0',
            'codigo_fornecedor' => 'nullable|string|max:255',
            'supplier'          => 'required|string|max:255',
            'data_coleta'       => 'nullable|date|after_or_equal:data_compra',
            'pedido_compra'     => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ], [
            'data_coleta.after_or_equal' => 'A data da coleta não pode ser antes da data da compra.',
            'pedido_compra.mimes'        => 'O pedido de compra precisa ser PDF ou imagem (JPG, PNG, WEBP).',
            'pedido_compra.max'          => 'O pedido de compra pode ter no máximo 10 MB.',
        ]);

        $atualizacao = [
            'data_compra'       => $dados['data_compra'],
            'preco_unitario'    => $dados['preco_unitario'],
            'valor'             => round((float) $dados['preco_unitario'] * (int) $purchaseRequest->quantity, 2),
            'codigo_fornecedor' => $dados['codigo_fornecedor'] ?? null,
            'supplier'          => mb_convert_case(mb_strtolower(trim($dados['supplier'])), MB_CASE_TITLE, 'UTF-8'),
            'data_coleta'       => $dados['data_coleta'] ?? null,
        ];

        if ($request->hasFile('pedido_compra')) {
            $arquivo = $request->file('pedido_compra');
            $caminhoAntigo = $purchaseRequest->pedido_compra_path;

            $atualizacao['pedido_compra_path'] = $arquivo->store('pedidos-compra', self::DISCO);
            $atualizacao['pedido_compra_nome'] = $arquivo->getClientOriginalName();

            if ($caminhoAntigo) {
                Storage::disk(self::DISCO)->delete($caminhoAntigo);
            }
        }

        $purchaseRequest->update($atualizacao);

        return redirect()->route('admin.compras.edit', $purchaseRequest)
            ->with('success', 'Dados da compra salvos.');
    }

    public function baixarPedido(PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $caminho = $purchaseRequest->pedido_compra_path;

        if (!$caminho || !Storage::disk(self::DISCO)->exists($caminho)) {
            abort(404, 'Nenhum pedido de compra anexado.');
        }

        return Storage::disk(self::DISCO)->download($caminho, $purchaseRequest->pedido_compra_nome ?? basename($caminho));
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
