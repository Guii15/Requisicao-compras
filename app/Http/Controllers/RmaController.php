<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Models\RmaEnvio;
use Illuminate\Validation\Rule;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;

/**
 * Tela do setor RMA (só leitura): o que foi comprado e já chegou — produto, fornecedor,
 * data da compra, quantidade, fotos da conferência e pedido de compra. Nunca mostra valores.
 */
class RmaController extends Controller
{
    use BuscaCaseInsensitive;

    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q'            => 'nullable|string|max:100',
            'data_inicial' => 'nullable|date',
            'data_final'   => 'nullable|date',
        ]);

        $q = trim((string) ($filtros['q'] ?? ''));

        $query = PurchaseRequest::with('fotosConferencia')
            ->where('status', 'aprovado')
            ->whereNotNull('entrada_concluida_em');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $this->whereLikeInsensitive($sub, 'product_name', $q);
                $this->orWhereLikeInsensitive($sub, 'supplier', $q);
                $this->orWhereLikeInsensitive($sub, 'empresa', $q);
                $this->orWhereLikeInsensitive($sub, 'product_code', $q);
            });
        }

        if (!empty($filtros['data_inicial'])) {
            $query->whereDate('data_compra', '>=', $filtros['data_inicial']);
        }

        if (!empty($filtros['data_final'])) {
            $query->whereDate('data_compra', '<=', $filtros['data_final']);
        }

        $itens = $query->orderByDesc('data_compra')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('rma.index', [
            'itens'       => $itens,
            'q'           => $q,
            'dataInicial' => $filtros['data_inicial'] ?? '',
            'dataFinal'   => $filtros['data_final'] ?? '',
        ]);
    }

    /**
     * "Enviar ao RMA" (Conferência, Entrada e admin): item com defeito vai para o setor RMA devolver ao fornecedor.
     * Só item já conferido, e nunca mais do que chegou (somando o que já foi mandado antes).
     */
    public function enviar(Request $request, PurchaseRequest $purchaseRequest)
    {
        $conferido = $purchaseRequest->status === 'aprovado'
            && in_array($purchaseRequest->status_conferencia, ['conferido_ok', 'divergente', 'avancado_mesmo_assim'], true);
        $chegou = $conferido
            ? (int) ($purchaseRequest->entrada_concluida_em ? ($purchaseRequest->quantidade_entrada ?? $purchaseRequest->quantidade_recebida) : $purchaseRequest->quantidade_recebida)
            : 0;
        $disponivel = max(0, $chegou - (int) $purchaseRequest->enviosRma()->sum('quantidade'));

        $dados = $request->validate([
            'quantidade' => ['required', 'integer', 'min:1', 'max:' . max(1, $disponivel)],
            'defeito'    => 'required|string|max:2000',
            'foto'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:15360',
        ], [
            'quantidade.required' => 'Diga quantas unidades vão para o RMA.',
            'quantidade.max'      => 'Só dá para mandar até ' . $disponivel . ' unidade(s) deste item ao RMA.',
            'defeito.required'    => 'Descreva o defeito.',
            'foto.image'          => 'A foto precisa ser uma imagem.',
            'foto.max'            => 'A foto deve ter no máximo 15MB.',
        ]);

        if ($disponivel < 1) {
            return back()->withErrors(['quantidade' => $conferido
                ? 'Todas as unidades deste item já foram mandadas ao RMA.'
                : 'Só dá para mandar ao RMA um item que já foi conferido.']);
        }

        RmaEnvio::create([
            'purchase_request_id' => $purchaseRequest->id,
            'quantidade'          => $dados['quantidade'],
            'defeito'             => $dados['defeito'],
            'foto_path'           => $request->hasFile('foto') ? $request->file('foto')->store('rma', 'public') : null,
            'enviado_por_id'      => $request->user()->id,
        ]);

        return back()->with('success', 'Item enviado ao RMA.');
    }

    /** Aba "Enviados ao RMA": o que foi mandado para devolver ao fornecedor. Nunca mostra valores. */
    public function envios(Request $request)
    {
        $filtros = $request->validate([
            'q'        => 'nullable|string|max:100',
            'situacao' => 'nullable|in:abertos,finalizados,todos',
        ]);
        $q = trim((string) ($filtros['q'] ?? ''));
        $situacao = $filtros['situacao'] ?? 'abertos';

        $query = RmaEnvio::with(['purchaseRequest.fotosConferencia', 'enviadoPor', 'atualizadoPor']);

        if ($situacao === 'abertos') {
            $query->whereNotIn('status', RmaEnvio::FINALIZADOS);
        } elseif ($situacao === 'finalizados') {
            $query->whereIn('status', RmaEnvio::FINALIZADOS);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->whereHas('purchaseRequest', function ($item) use ($q) {
                    $this->whereLikeInsensitive($item, 'product_name', $q);
                    $this->orWhereLikeInsensitive($item, 'supplier', $q);
                    $this->orWhereLikeInsensitive($item, 'product_code', $q);
                });
                $this->orWhereLikeInsensitive($sub, 'numero_serie', $q);
                $this->orWhereLikeInsensitive($sub, 'protocolo_fornecedor', $q);
                $this->orWhereLikeInsensitive($sub, 'codigo_rastreio', $q);
            });
        }

        $envios = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('rma.envios', [
            'envios'   => $envios,
            'q'        => $q,
            'situacao' => $situacao,
            'abertos'  => RmaEnvio::whereNotIn('status', RmaEnvio::FINALIZADOS)->count(),
        ]);
    }

    /** O RMA preenche o que precisa para devolver ao fornecedor. */
    public function atualizarEnvio(Request $request, RmaEnvio $envio)
    {
        $dados = $request->validate([
            'status'               => ['required', Rule::in(RmaEnvio::STATUS)],
            'numero_serie'         => 'nullable|string|max:120',
            'protocolo_fornecedor' => 'nullable|string|max:120',
            'data_envio'           => 'nullable|date',
            'codigo_rastreio'      => 'nullable|string|max:120',
            'observacao'           => 'nullable|string|max:2000',
        ], [
            'status.in' => 'Escolha um status da lista.',
        ]);

        $envio->update($dados + ['atualizado_por_id' => $request->user()->id]);

        return back()->with('success', 'RMA atualizado.');
    }
}
