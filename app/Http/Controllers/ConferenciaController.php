<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseRequestApproved;
use App\Models\PurchaseRequest;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ConferenciaController extends Controller
{
    use AgrupaRequisicoesPorGrupoId, BuscaCaseInsensitive;

    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'conferidos' ? 'conferidos' : ($request->query('aba') === 'coleta' ? 'coleta' : 'aguardando');
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
        } elseif ($aba === 'coleta') {
            // Mostra tudo - aguardando e coletados
        } else {
            $query->whereNull('status_conferencia');
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $this->whereLikeInsensitive($sub, 'product_name', $q);
                $this->orWhereLikeInsensitive($sub, 'requester_name', $q);
                $this->orWhereLikeInsensitive($sub, 'supplier', $q);
            });
        }

        $ordenarPor = 'created_at';
        $requests = $this->paginarAgrupadoPorGrupoId($query, 15, 'page', ['user', 'conferente'], $ordenarPor, null, true)->withQueryString();

        return view('conferencia.index', compact('requests', 'aba', 'resultado', 'q'));
    }

    public function conferir(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'aprovado' || $purchaseRequest->status_conferencia !== null) {
            return redirect()->route('conferencia.index')
                ->with('aviso', 'Este item já foi conferido (provavelmente um clique duplicado) — nada foi alterado.');
        }

        $request->validate([
            'quantidade_recebida'     => 'required|integer|min:0',
            'foto'                    => 'required|image|mimes:jpg,jpeg,png,webp|max:15360',
            'resultado'               => 'required|in:ok,divergente',
            'observacao_conferencia'  => 'required_if:resultado,divergente|nullable|string|max:500',
            'obs'                     => 'nullable|string|max:500',
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
            'obs'                    => $request->obs,
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

    public function registrarColeta(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->atraso === false) {
            return redirect()->route('conferencia.index', ['aba' => 'coleta'])
                ->with('aviso', 'Este item já foi coletado (provavelmente um clique duplicado) — nada foi alterado.');
        }

        if ($purchaseRequest->status !== 'aprovado') {
            return redirect()->route('conferencia.index', ['aba' => 'coleta'])
                ->with('aviso', 'Este item não foi aprovado — não é possível registrar a coleta.');
        }

        $purchaseRequest->update(['atraso' => false]);

        return redirect()->route('conferencia.index', ['aba' => 'coleta'])->with('success', 'Coleta registrada com sucesso!');
    }
}
