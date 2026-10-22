<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
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
}
