<?php

namespace App\Support;

use App\Models\PurchaseRequest;
use Illuminate\Database\Eloquent\Builder;

trait BuscaCaseInsensitive
{
    /**
     * LIKE com LOWER() dos dois lados, pra "iphone" achar "IPHONE" independente
     * de collation do banco (SQLite local e' case-insensitive so' pra ASCII, MySQL
     * de producao depende da collation da coluna — LOWER() garante o mesmo
     * resultado nos dois).
     */
    protected function whereLikeInsensitive(Builder $query, string $coluna, string $termo, string $booleano = 'and'): Builder
    {
        return $query->whereRaw('LOWER(' . $coluna . ') LIKE ?', ['%' . mb_strtolower($termo) . '%'], $booleano);
    }

    protected function orWhereLikeInsensitive(Builder $query, string $coluna, string $termo): Builder
    {
        return $this->whereLikeInsensitive($query, $coluna, $termo, 'or');
    }

    /**
     * Busca pelo Nº da requisicao: "4505" ou "#4505" acha o grupo inteiro que tem o
     * item 4505 (o Nº mostrado nas listas e' o id do primeiro item do grupo). Texto
     * que nao e' so' numero nao muda nada.
     */
    protected function orWhereNumeroRequisicao(Builder $query, string $termo): Builder
    {
        $numero = ltrim(trim($termo), '#');

        if (!ctype_digit($numero)) {
            return $query;
        }

        return $query->orWhereIn('grupo_id', PurchaseRequest::withoutGlobalScope('apenasFluxoAtivo')
            ->where('id', (int) $numero)->whereNotNull('grupo_id')->select('grupo_id'));
    }
}
