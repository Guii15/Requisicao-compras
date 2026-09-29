<?php

namespace App\Support;

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
}
