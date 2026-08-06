<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseRequestApproved;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConferenciaController extends Controller
{
    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'conferidos' ? 'conferidos' : 'aguardando';
        $resultado = in_array($request->query('resultado'), ['ok', 'divergente'], true) ? $request->query('resultado') : 'todos';
        $q = trim((string) $request->query('q', ''));

        $query = PurchaseRequest::with(['user', 'conferente'])->where('status', 'aprovado');

        if ($aba === 'conferidos') {
            $query->whereNotNull('status_conferencia')->where('status_conferencia', '!=', 'legado');

            if ($resultado === 'ok') {
                $query->where('status_conferencia', 'conferido_ok');
            } elseif ($resultado === 'divergente') {
                $query->whereIn('status_conferencia', ['divergente', 'avancado_mesmo_assim', 'cancelado']);
            }
        } else {
            $query->whereNull('status_conferencia');
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('product_name', 'like', '%' . $q . '%')
                    ->orWhere('requester_name', 'like', '%' . $q . '%')
                    ->orWhere('supplier', 'like', '%' . $q . '%');
            });
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return view('conferencia.index', compact('requests', 'aba', 'resultado', 'q'));
    }

    public function conferir(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'aprovado' || $purchaseRequest->status_conferencia !== null) {
            abort(409, 'Esta requisição já foi conferida ou não está mais aprovada.');
        }

        $request->validate([
            'quantidade_recebida'     => 'required|integer|min:0',
            'foto'                    => 'required|image|mimes:jpg,jpeg,png,webp|max:15360',
            'resultado'               => 'required|in:ok,divergente',
            'observacao_conferencia'  => 'required_if:resultado,divergente|nullable|string|max:500',
            'acao'                    => 'required|in:salvar,avancar_mesmo_assim',
        ], [
            'quantidade_recebida.required'       => 'Informe a quantidade recebida.',
            'foto.required'                      => 'A foto é obrigatória.',
            'foto.image'                          => 'O arquivo precisa ser uma imagem.',
            'foto.mimes'                          => 'Formatos aceitos: jpg, jpeg, png, webp.',
            'foto.max'                            => 'A foto deve ter no máximo 15MB.',
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

        if ($statusConferencia === 'conferido_ok') {
            $destinatarios = array_filter([env('ENTRADA_EMAIL'), env('ENTRADA_EMAIL_2')]);
            if (!empty($destinatarios)) {
                try {
                    Mail::to($destinatarios)->send(new PurchaseRequestApproved($purchaseRequest));
                } catch (\Exception $e) {
                    \Log::error('Falha ao enviar e-mail de conferência: ' . $e->getMessage());
                }
            }
        }

        return redirect()->route('conferencia.index')->with('success', 'Conferência registrada com sucesso!');
    }
}
