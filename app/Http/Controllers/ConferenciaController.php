<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseRequestApproved;
use App\Models\PurchaseRequest;
use App\Services\PushNotifier;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ConferenciaController extends Controller
{
    use AgrupaRequisicoesPorGrupoId, BuscaCaseInsensitive;

    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'conferidos' ? 'conferidos' : ($request->query('aba') === 'coleta' ? 'coleta' : 'aguardando');
        $resultado = $aba === 'coleta'
            ? (in_array($request->query('resultado'), ['aguardando', 'coletado', 'atraso'], true) ? $request->query('resultado') : 'aguardando')
            : (in_array($request->query('resultado'), ['ok', 'divergente'], true) ? $request->query('resultado') : 'todos');
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
            $query->where('status_coleta', $resultado);
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

        $aguardarRestante = $request->acao === 'aguardar_restante';

        $request->validate([
            'quantidade_recebida'     => 'required|integer|min:0',
            'foto'                    => 'required|image|mimes:jpg,jpeg,png,webp|max:15360',
            'fotos_extras'            => 'nullable|array|max:5',
            'fotos_extras.*'          => 'image|mimes:jpg,jpeg,png,webp|max:15360',
            'resultado'               => 'required|in:ok,divergente',
            // Ao aguardar o restante a divergência já é explicada pela própria quantidade.
            'observacao_conferencia'  => [Rule::requiredIf(fn () => $request->resultado === 'divergente' && !$aguardarRestante), 'nullable', 'string', 'max:500'],
            'obs'                     => 'nullable|string|max:500',
            'acao'                    => 'required|in:salvar,avancar_mesmo_assim,aguardar_restante',
        ], [
            'quantidade_recebida.required'       => 'Informe a quantidade recebida.',
            'foto.required'                      => 'A foto é obrigatória.',
            'foto.image'                          => 'O arquivo precisa ser uma imagem.',
            'foto.mimes'                          => 'Formatos aceitos: jpg, jpeg, png, webp.',
            'foto.max'                            => 'A foto deve ter no máximo 15MB.',
            'fotos_extras.max'                    => 'Envie no máximo 5 fotos extras.',
            'fotos_extras.*.image'                => 'As fotos extras precisam ser imagens.',
            'fotos_extras.*.mimes'                => 'Formatos aceitos nas fotos extras: jpg, jpeg, png, webp.',
            'fotos_extras.*.max'                  => 'Cada foto extra deve ter no máximo 15MB.',
            'resultado.required'                 => 'Selecione o resultado da conferência.',
            'observacao_conferencia.required'    => 'A observação é obrigatória quando divergente.',
        ]);

        if ($aguardarRestante && ((int) $request->quantidade_recebida < 1 || (int) $request->quantidade_recebida >= $purchaseRequest->quantity)) {
            throw ValidationException::withMessages([
                'quantidade_recebida' => 'Para aguardar o restante, a quantidade recebida precisa ser maior que 0 e menor que a solicitada (' . $purchaseRequest->quantity . ').',
            ]);
        }

        $podeAvancarMesmoAssim = $request->resultado === 'divergente' && $purchaseRequest->tipo_entrega === 'entrega_direta';

        if ($request->acao === 'avancar_mesmo_assim' && !$podeAvancarMesmoAssim) {
            abort(403, 'Ação não permitida para esta combinação de resultado e tipo de entrega.');
        }

        if ($request->resultado === 'ok' || $aguardarRestante) {
            $statusConferencia = 'conferido_ok';
        } elseif ($request->acao === 'avancar_mesmo_assim') {
            $statusConferencia = 'avancado_mesmo_assim';
        } else {
            $statusConferencia = 'divergente';
        }

        $observacao = $request->observacao_conferencia;
        $dadosParcial = [];

        if ($aguardarRestante) {
            $totalPedido = $purchaseRequest->quantidade_original ?? $purchaseRequest->quantity;
            $this->criarRestante($purchaseRequest, (int) $request->quantidade_recebida, $totalPedido);

            $dadosParcial = ['quantity' => (int) $request->quantidade_recebida, 'quantidade_original' => $totalPedido];
            $observacao = $observacao ?: 'Recebimento parcial: chegaram ' . $request->quantidade_recebida . ' de ' . $totalPedido . '; aguardando o restante.';
        }

        $purchaseRequest->update($dadosParcial + [
            'quantidade_recebida'    => $request->quantidade_recebida,
            'status_conferencia'     => $statusConferencia,
            'observacao_conferencia' => $observacao,
            'obs'                    => $request->obs,
            'conferente_id'          => auth()->id(),
        ]);

        $path = $request->file('foto')->store('conferencia', 'public');
        $purchaseRequest->fotosConferencia()->create([
            'caminho_arquivo' => $path,
            'nome_original'   => $request->file('foto')->getClientOriginalName(),
        ]);

        // Fotos extras (ex.: código de barras), guardadas junto com a principal.
        foreach ($request->file('fotos_extras', []) as $extra) {
            $purchaseRequest->fotosConferencia()->create([
                'caminho_arquivo' => $extra->store('conferencia', 'public'),
                'nome_original'   => $extra->getClientOriginalName(),
            ]);
        }

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

        defer(fn () => app(PushNotifier::class)->conferida($purchaseRequest));

        return redirect()->route('conferencia.index')->with('success', 'Conferência registrada com sucesso!');
    }

    /**
     * O item "restante" (parte que ainda não chegou) pode ter a quantidade ajustada enquanto
     * espera, porque não se sabe quanto nem quando vai chegar. Só diminui: o total pedido é o mesmo.
     */
    public function editarParcial(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->restante_de_id === null || $purchaseRequest->status !== 'aprovado' || $purchaseRequest->status_conferencia !== null) {
            return redirect()->route('conferencia.index')
                ->with('aviso', 'Só dá para editar um item parcial que ainda está aguardando o restante — nada foi alterado.');
        }

        $dados = $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $purchaseRequest->quantity,
        ], [
            'quantity.required' => 'Informe a quantidade que falta chegar.',
            'quantity.integer'  => 'A quantidade precisa ser um número inteiro.',
            'quantity.min'      => 'A quantidade que falta precisa ser pelo menos 1.',
            'quantity.max'      => 'A quantidade não pode ser maior do que a que estava faltando (' . $purchaseRequest->quantity . ').',
        ]);

        $purchaseRequest->update(['quantity' => (int) $dados['quantity']]);

        return redirect()->route('conferencia.index')->with('success', 'Quantidade que falta chegar atualizada.');
    }

    /**
     * Cria o item da parte que ainda não chegou (mesmo grupo, mesma compra), para ser conferido
     * e receber entrada quando chegar. Nada da conferência/entrada/coleta do original é copiado.
     */
    private function criarRestante(PurchaseRequest $item, int $recebida, int $totalPedido): PurchaseRequest
    {
        $restante = $item->replicate([
            'status_conferencia', 'quantidade_recebida', 'conferente_id', 'observacao_conferencia', 'obs',
            'quantidade_entrada', 'obs_entrada', 'vendedor_destino', 'entrada_concluida_em',
            'data_coleta', 'coletado_por', 'valor',
        ]);

        $restante->quantity = $item->quantity - $recebida;
        $restante->quantidade_original = $totalPedido;
        $restante->restante_de_id = $item->id;
        $restante->status_coleta = 'aguardando';
        $restante->save();

        return $restante;
    }

    public function registrarColeta(Request $request, PurchaseRequest $purchaseRequest)
    {
        $dados = $request->validate([
            'status_coleta' => 'required|in:coletado,atraso',
            'data_coleta'   => 'required|date',
        ], [
            'status_coleta.required' => 'Informe se o item foi coletado ou está em atraso.',
            'data_coleta.required'   => 'Informe a data da coleta.',
        ]);

        if ($purchaseRequest->status_coleta === 'coletado') {
            return redirect()->route('conferencia.index', ['aba' => 'coleta'])
                ->with('aviso', 'Este item já foi coletado (provavelmente um clique duplicado) — nada foi alterado.');
        }

        if ($purchaseRequest->status !== 'aprovado') {
            return redirect()->route('conferencia.index', ['aba' => 'coleta'])
                ->with('aviso', 'Este item não foi aprovado — não é possível registrar a coleta.');
        }

        $purchaseRequest->update([
            'status_coleta' => $dados['status_coleta'],
            'data_coleta'   => $dados['data_coleta'],
            'coletado_por'  => $dados['status_coleta'] === 'coletado' ? auth()->user()->name : null,
        ]);

        return redirect()->route('conferencia.index', ['aba' => 'coleta'])->with('success', 'Coleta registrada com sucesso!');
    }
}
