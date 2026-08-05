<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class PendenciaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
            ->where('status', 'aprovado')
            ->where('status_conferencia', 'divergente')
            ->where('tipo_entrega', 'estoque')
            ->oldest()
            ->paginate(15);

        return view('pendencias.index', compact('requests'));
    }

    public function resolver(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'aprovado'
            || $purchaseRequest->status_conferencia !== 'divergente'
            || $purchaseRequest->tipo_entrega !== 'estoque') {
            abort(409, 'Esta pendência já foi resolvida ou não está mais nesse estado.');
        }

        $request->validate([
            'decisao'    => 'required|in:aceitar,cancelar',
            'observacao' => 'required_if:decisao,cancelar|nullable|string|max:500',
        ], [
            'decisao.required'       => 'Selecione uma decisão.',
            'observacao.required_if' => 'A observação é obrigatória ao cancelar o item.',
        ]);

        $novoStatusConferencia = $request->decisao === 'aceitar' ? 'avancado_mesmo_assim' : 'cancelado';

        $notaAnexada = trim(
            ($purchaseRequest->admin_note ? $purchaseRequest->admin_note . "\n" : '')
            . '[Pendência ' . ($request->decisao === 'aceitar' ? 'aceita' : 'cancelada') . '] '
            . ($request->observacao ?: '')
        );

        $purchaseRequest->update([
            'status_conferencia' => $novoStatusConferencia,
            'admin_note'         => $notaAnexada,
        ]);

        return redirect()->route('pendencias.index')->with('success', 'Pendência resolvida com sucesso!');
    }
}
