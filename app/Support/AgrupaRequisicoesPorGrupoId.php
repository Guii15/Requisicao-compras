<?php

namespace App\Support;

use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

trait AgrupaRequisicoesPorGrupoId
{
    /**
     * Pagina a query por grupo_id (uma requisicao = varios itens) em vez de por linha.
     * Assume que todo registro visivel ja tem grupo_id preenchido (migration +
     * comando de backfill garantem isso). Cada pagina traz o item mais recente
     * de cada grupo pra ordenar/paginar, depois busca TODOS os itens dos grupos
     * daquela pagina, ja que grupo_id e' unico por usuario/submissao.
     */
    protected function paginarAgrupadoPorGrupoId(
        Builder $query,
        int $porPagina = 15,
        string $pageName = 'page',
        array $with = [],
        string $ordenarPor = 'created_at',
        ?string $ordenarPorDesempate = null
    ): LengthAwarePaginator {
        $paginadorDeGrupos = (clone $query)
            ->select('grupo_id')
            ->selectRaw("MAX({$ordenarPor}) as ultima_data");

        if ($ordenarPorDesempate !== null) {
            // Desempate: quando varios grupos tem o mesmo "ultima_data" (ex: um lote
            // inteiro importado de uma vez, todos com o mesmo updated_at), usa essa
            // segunda coluna pra nao deixar a ordem entre eles ficar arbitraria.
            $paginadorDeGrupos->selectRaw("MAX({$ordenarPorDesempate}) as desempate");
        }

        $paginadorDeGrupos = $paginadorDeGrupos->groupBy('grupo_id')->orderByDesc('ultima_data');

        if ($ordenarPorDesempate !== null) {
            $paginadorDeGrupos->orderByDesc('desempate');
        }

        $paginadorDeGrupos = $paginadorDeGrupos->paginate($porPagina, ['*'], $pageName);

        $grupoIds = $paginadorDeGrupos->pluck('grupo_id')->all();

        // withoutGlobalScope: os grupo_id ja vieram da query original (ja filtrada pelo
        // chamador); reaplicar o escopo padrao aqui excluiria grupos de historico importado.
        $itensPorGrupo = empty($grupoIds)
            ? collect()
            : PurchaseRequest::withoutGlobalScope('apenasFluxoAtivo')
                ->whereIn('grupo_id', $grupoIds)
                ->with($with)
                ->orderBy('created_at')
                ->get()
                ->groupBy('grupo_id');

        $gruposOrdenados = collect($grupoIds)->map(fn ($id) => $itensPorGrupo->get($id, collect()));

        $paginadorDeGrupos->setCollection($gruposOrdenados);

        return $paginadorDeGrupos;
    }
}
