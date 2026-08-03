<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class ConferenciaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with('user')
            ->where('status', 'aprovado')
            ->whereNull('status_conferencia')
            ->latest()
            ->paginate(15);

        return view('conferencia.index', compact('requests'));
    }

    public function conferir(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'quantidade_recebida'     => 'required|integer|min:0',
            'foto'                    => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'resultado'               => 'required|in:ok,divergente',
            'observacao_conferencia'  => 'required_if:resultado,divergente|nullable|string|max:500',
            'acao'                    => 'required|in:salvar,avancar_mesmo_assim',
        ], [
            'quantidade_recebida.required'       => 'Informe a quantidade recebida.',
            'foto.required'                      => 'A foto é obrigatória.',
            'foto.image'                          => 'O arquivo precisa ser uma imagem.',
            'foto.mimes'                          => 'Formatos aceitos: jpg, jpeg, png, webp.',
            'foto.max'                            => 'A foto deve ter no máximo 5MB.',
            'resultado.required'                 => 'Selecione o resultado da conferência.',
            'observacao_conferencia.required_if' => 'A observação é obrigatória quando divergente.',
        ]);

        $podeAvancarMesmoAssim = $request->resultado === 'divergente' && $purchaseRequest->tipo_entrega === 'entrega_direta';

        if ($request->acao === 'avancar_mesmo_assim' && !$podeAvancarMesmoAssim) {
            abort(403, 'Ação não permitida para esta combinação de resultado e tipo de entrega.');
        }

        if ($request->resultado === 'ok') {
            $statusConferencia = 'conferido_ok';
        } elseif ($request->acao === 'avancar_mesmo_assim') {
            $statusConferencia = 'avancado_mesmo_assim';
        } else {
            $statusConferencia = 'divergente';
        }

        $purchaseRequest->update([
            'quantidade_recebida'    => $request->quantidade_recebida,
            'status_conferencia'     => $statusConferencia,
            'observacao_conferencia' => $request->observacao_conferencia,
            'conferente_id'          => auth()->id(),
        ]);

        $path = $request->file('foto')->store('conferencia', 'public');
        $purchaseRequest->fotosConferencia()->create([
            'caminho_arquivo' => $path,
            'nome_original'   => $request->file('foto')->getClientOriginalName(),
        ]);

        return redirect()->route('conferencia.index')->with('success', 'Conferência registrada com sucesso!');
    }
}
