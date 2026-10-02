<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Soma o total de nomes escritos de formas diferentes numa linha só ("Yhan", "YHAN" e "yhan " viram "Yhan"),
 * para os rankings de "maiores gastos". As linhas de entrada já vêm somadas por texto exato do banco.
 */
class RankingPorNome
{
    /**
     * @param  Collection  $linhas  objetos com o nome em `$campo` e `total_gasto`
     * @param  callable|null  $normalizar  como comparar os nomes (por padrão ignora caixa, acento e espaços)
     * @return Collection<int, object>  objetos com `$campo` e `total_gasto`, do maior para o menor, no máximo `$limite`
     */
    public static function agrupar(Collection $linhas, string $campo, ?callable $normalizar = null, int $limite = 10): Collection
    {
        $normalizar ??= fn (?string $nome) => self::chave($nome);

        return $linhas
            ->groupBy(fn ($linha) => $normalizar((string) $linha->{$campo}))
            ->map(function (Collection $grupo) use ($campo) {
                // Mostra a grafia que mais gastou (no empate, a primeira em ordem alfabética).
                $principal = $grupo
                    ->sort(fn ($a, $b) => [(float) $b->total_gasto, (string) $a->{$campo}] <=> [(float) $a->total_gasto, (string) $b->{$campo}])
                    ->first();

                return (object) [
                    $campo => self::formatar((string) $principal->{$campo}),
                    'total_gasto' => round((float) $grupo->sum(fn ($l) => (float) $l->total_gasto), 2),
                ];
            })
            ->sortByDesc('total_gasto')
            ->take($limite)
            ->values();
    }

    /** Chave de comparação: "João  Pedro", "JOAO PEDRO" e " joao pedro " dão a mesma. */
    public static function chave(?string $nome): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', Str::ascii(mb_strtolower((string) $nome))));
    }

    /** Tira espaços sobrando; nome todo em maiúsculas ou minúsculas vira "Yhan". Grafia já bem escrita fica como está. */
    private static function formatar(string $nome): string
    {
        $nome = trim(preg_replace('/\s+/u', ' ', $nome));

        if ($nome === '') {
            return 'Não informado';
        }

        if ($nome === mb_strtoupper($nome) || $nome === mb_strtolower($nome)) {
            return mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');
        }

        return $nome;
    }
}
