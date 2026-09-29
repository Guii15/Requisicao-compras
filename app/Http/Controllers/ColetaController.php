<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use App\Support\AgrupaRequisicoesPorGrupoId;
use App\Support\BuscaCaseInsensitive;
use Illuminate\Http\Request;

class ColetaController extends Controller
{
    use AgrupaRequisicoesPorGrupoId, BuscaCaseInsensitive;

    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'coletados' ? 'coletados' : 'aguardando';
        $q = trim((string) $request->query('q', ''));

        $query = PurchaseRequest::where('status', 'aprovado')
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim']);

        if ($aba === 'coletados') {
            $query->whereNotNull('data_coleta');
            $ordenarPor = 'data_coleta';
        } else {
            $query->whereNull('data_coleta');
            $ordenarPor = 'created_at';
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $this->whereLikeInsensitive($sub, 'product_name', $q);
                $this->orWhereLikeInsensitive($sub, 'requester_name', $q);
                $this->orWhereLikeInsensitive($sub, 'supplier', $q);
            });
        }

        $requests = $this->paginarAgrupadoPorGrupoId($query, 15, 'page', ['user', 'conferente'], $ordenarPor, null, true)->withQueryString();

        return view('coleta.index', compact('requests', 'aba', 'q'));
    }

    public function registrar(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->data_coleta !== null) {
            return redirect()->route('coleta.index')
                ->with('aviso', 'Este item já teve a coleta registrada (provavelmente um clique duplicado) — nada foi alterado.');
        }

        if ($purchaseRequest->status !== 'aprovado'
            || !in_array($purchaseRequest->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)) {
            return redirect()->route('coleta.index')
                ->with('aviso', 'Este item ainda não foi aprovado/conferido — não é possível registrar a coleta ainda.');
        }

        $request->validate([
            'coletado_por' => 'required|string|max:255',
            'data_coleta'  => 'required|date',
        ], [
            'coletado_por.required' => 'Informe quem coletou.',
            'data_coleta.required'  => 'Informe a data da coleta.',
        ]);

        $purchaseRequest->update([
            'coletado_por' => $request->coletado_por,
            'data_coleta'  => $request->data_coleta,
        ]);

        return redirect()->route('coleta.index')->with('success', 'Coleta registrada com sucesso!');
    }
}
