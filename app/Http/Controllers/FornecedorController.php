<?php

namespace App\Http\Controllers;

use App\Models\Fornecedor;
use App\Models\FornecedorMesclagem;
use App\Services\FornecedorResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FornecedorController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $chave = Fornecedor::normalizar($q);

        $fornecedores = Fornecedor::withCount('compras')
            ->when($chave !== '', fn ($query) => $query->where('nome_normalizado', 'like', '%' . $chave . '%'))
            ->orderBy('nome')
            ->paginate(30)
            ->withQueryString();

        $grafias = DB::table('purchase_requests')
            ->whereIn('fornecedor_id', $fornecedores->pluck('id'))
            ->whereNotNull('supplier_original')
            ->select('fornecedor_id', 'supplier_original')
            ->distinct()
            ->get()
            ->groupBy('fornecedor_id')
            ->map(fn ($linhas) => $linhas->pluck('supplier_original')->map(fn ($s) => trim($s))->filter()->unique()->values());

        $todos = Fornecedor::withCount('compras')->orderBy('nome')->get(['id', 'nome']);
        $mesclagens = FornecedorMesclagem::with('user')->latest()->limit(20)->get();

        return view('admin.fornecedores.index', compact('fornecedores', 'grafias', 'todos', 'mesclagens', 'q'));
    }

    public function mesclar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'origem_id'  => 'required|integer|exists:fornecedores,id',
            'destino_id' => 'required|integer|exists:fornecedores,id|different:origem_id',
            'confirmar'  => 'accepted',
        ], [
            'destino_id.different' => 'Escolha dois fornecedores diferentes.',
            'confirmar.accepted'   => 'Marque a confirmação para mesclar.',
        ]);

        $origem = Fornecedor::findOrFail($dados['origem_id']);
        $destino = Fornecedor::findOrFail($dados['destino_id']);

        $afetadas = DB::transaction(function () use ($origem, $destino, $request) {
            // supplier_original guarda o texto antigo de quem ainda não tinha (nunca se perde).
            $afetadas = DB::table('purchase_requests')
                ->where('fornecedor_id', $origem->id)
                ->update([
                    'supplier_original' => DB::raw('COALESCE(supplier_original, supplier)'),
                    'supplier' => $destino->nome,
                    'fornecedor_id' => $destino->id,
                ]);

            FornecedorMesclagem::create([
                'origem_nome' => $origem->nome,
                'origem_normalizado' => $origem->nome_normalizado,
                'destino_id' => $destino->id,
                'destino_nome' => $destino->nome,
                'compras_afetadas' => $afetadas,
                'user_id' => $request->user()->id,
            ]);

            $origem->delete();

            return $afetadas;
        });

        return redirect()->route('admin.fornecedores.index')
            ->with('success', "\"{$origem->nome}\" foi mesclado em \"{$destino->nome}\" ({$afetadas} compra(s) movida(s)).");
    }

    public function buscar(Request $request, FornecedorResolver $resolver): JsonResponse
    {
        $termo = mb_substr((string) $request->query('q', ''), 0, 255);
        $exato = $resolver->exato($termo);

        return response()->json([
            'resultados' => $resolver->buscar($termo)->map->only(['id', 'nome'])->values(),
            'exato' => $exato?->only(['id', 'nome']),
            'parecidos' => $exato ? [] : $resolver->parecidos($termo)->map->only(['id', 'nome'])->values(),
        ]);
    }
}
