<?php

namespace App\Http\Controllers;

use App\Services\FornecedorResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
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
