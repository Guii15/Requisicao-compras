<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    use AgrupaRequisicoesPorGrupoId, BuscaCaseInsensitive;

    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'concluidas' ? 'concluidas' : 'aguardando';
        $q = trim((string) $request->query('q', ''));

        $query = PurchaseRequest::where('status', 'aprovado')
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim']);

        if ($aba === 'concluidas') {
            $query->whereNotNull('entrada_concluida_em');
            $ordenarPor = 'entrada_concluida_em';
        } else {
            $query->whereNull('entrada_concluida_em');
            $ordenarPor = 'created_at';
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $this->whereLikeInsensitive($sub, 'product_name', $q);
                $this->orWhereLikeInsensitive($sub, 'requester_name', $q);
                $this->orWhereLikeInsensitive($sub, 'supplier', $q);
            });
        }

        $requests = $this->paginarAgrupadoPorGrupoId($query, 15, 'page', ['user', 'conferente', 'fotosConferencia'], $ordenarPor, null, true)->withQueryString();

        return view('entrada.index', compact('requests', 'aba', 'q'));
    }

    public function darEntrada(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->entrada_concluida_em !== null) {
            return redirect()->route('entrada.index')
                ->with('aviso', 'Este item já teve entrada registrada (provavelmente um clique duplicado) — nada foi alterado.');
        }

        if ($purchaseRequest->status !== 'aprovado'
            || !in_array($purchaseRequest->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)) {
            return redirect()->route('entrada.index')
                ->with('aviso', 'Este item ainda não foi aprovado/conferido — não é possível dar entrada nele ainda.');
        }

        $quantidadeMaxima = $purchaseRequest->quantidade_recebida ?? $purchaseRequest->quantity;

        $request->validate([
            'vendedor_destino'   => 'required|string|max:255',
            'quantidade_entrada' => 'required|integer|min:' . $quantidadeMaxima . '|max:' . $quantidadeMaxima,
        ], [
            'vendedor_destino.required'   => 'Informe o vendedor destino.',
            'quantidade_entrada.required' => 'Informe a quantidade que entrou.',
            'quantidade_entrada.min'      => 'A entrada precisa ser da quantidade cheia recebida na conferência (' . $quantidadeMaxima . '). Se faltou alguma unidade, resolva isso na conferência antes de dar entrada.',
            'quantidade_entrada.max'      => 'A entrada precisa ser da quantidade cheia recebida na conferência (' . $quantidadeMaxima . '). Se faltou alguma unidade, resolva isso na conferência antes de dar entrada.',
        ]);

        $purchaseRequest->update([
            'vendedor_destino'     => $request->vendedor_destino,
            'quantidade_entrada'   => $request->quantidade_entrada,
            'entrada_concluida_em' => now(),
        ]);

        return redirect()->route('entrada.index')->with('success', 'Entrada registrada com sucesso!');
    }
}
