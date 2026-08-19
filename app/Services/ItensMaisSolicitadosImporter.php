<?php

namespace App\Services;

use App\Models\ItemMaisSolicitado;
use Illuminate\Support\Facades\DB;

class ItensMaisSolicitadosImporter
{
    /**
     * Substitui todo o ranking atual pelo ranking informado.
     *
     * @param  array<int, array{nome_canonico: string, capacidade: ?string, total_pedidos: int, variacoes: array}>  $ranking
     */
    public function importar(array $ranking): int
    {
        return DB::transaction(function () use ($ranking) {
            ItemMaisSolicitado::query()->delete();

            $agora = now();

            foreach ($ranking as $grupo) {
                ItemMaisSolicitado::create([
                    'nome_canonico'       => $grupo['nome_canonico'],
                    'capacidade'          => $grupo['capacidade'] ?? null,
                    'total_pedidos'       => $grupo['total_pedidos'],
                    'variacoes_agrupadas' => $grupo['variacoes'],
                    'atualizado_em'       => $agora,
                ]);
            }

            return count($ranking);
        });
    }
}
