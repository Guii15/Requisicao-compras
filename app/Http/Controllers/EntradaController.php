<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'concluidas' ? 'concluidas' : 'aguardando';

        $query = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
            ->where('status', 'aprovado')
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim']);

        if ($aba === 'concluidas') {
            $query->whereNotNull('entrada_concluida_em')->orderByDesc('entrada_concluida_em');
        } else {
            $query->whereNull('entrada_concluida_em')->oldest();
        }

        $requests = $query->paginate(15)->withQueryString();

        return view('entrada.index', compact('requests', 'aba'));
    }

    public function darEntrada(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'aprovado'
            || !in_array($purchaseRequest->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)
            || $purchaseRequest->entrada_concluida_em !== null) {
            abort(409, 'Este item já teve entrada registrada ou não está mais liberado pela conferência.');
        }

        $request->validate([
            'vendedor_destino'   => 'required|string|max:255',
            'quantidade_entrada' => 'required|integer|min:0',
        ], [
            'vendedor_destino.required'   => 'Informe o vendedor destino.',
            'quantidade_entrada.required' => 'Informe a quantidade que entrou.',
        ]);

        $purchaseRequest->update([
            'vendedor_destino'     => $request->vendedor_destino,
            'quantidade_entrada'   => $request->quantidade_entrada,
            'entrada_concluida_em' => now(),
        ]);

        return redirect()->route('entrada.index')->with('success', 'Entrada registrada com sucesso!');
    }
}
