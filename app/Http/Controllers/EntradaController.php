<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Support\AgrupaRequisicoesPorGrupoId;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    use AgrupaRequisicoesPorGrupoId;

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
                $sub->where('product_name', 'like', '%' . $q . '%')
                    ->orWhere('requester_name', 'like', '%' . $q . '%')
                    ->orWhere('supplier', 'like', '%' . $q . '%');
            });
        }

        $requests = $this->paginarAgrupadoPorGrupoId($query, 15, 'page', ['user', 'conferente', 'fotosConferencia'], $ordenarPor)->withQueryString();

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
            'quantidade_entrada' => 'required|integer|min:0|max:' . $quantidadeMaxima,
        ], [
            'vendedor_destino.required'   => 'Informe o vendedor destino.',
            'quantidade_entrada.required' => 'Informe a quantidade que entrou.',
            'quantidade_entrada.max'      => 'A quantidade não pode ser maior que a recebida na conferência (' . $quantidadeMaxima . ').',
        ]);

        $purchaseRequest->update([
            'vendedor_destino'     => $request->vendedor_destino,
            'quantidade_entrada'   => $request->quantidade_entrada,
            'entrada_concluida_em' => now(),
        ]);

        return redirect()->route('entrada.index')->with('success', 'Entrada registrada com sucesso!');
    }
}
