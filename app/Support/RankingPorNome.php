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
     * @param  bool  $juntarNomeIncompleto  nome curto que é o começo de UM único nome completo junta com ele
     *                                      ("YHAN" + "Yhan Rezende"). Se houver mais de um completo possível, não junta.
     * @return Collection<int, object>  objetos com `$campo` e `total_gasto`, do maior para o menor, no máximo `$limite`
     */
    public static function agrupar(Collection $linhas, string $campo, ?callable $normalizar = null, int $limite = 10, bool $juntarNomeIncompleto = false): Collection
    {
        $normalizar ??= fn (?string $nome) => self::chave($nome);

        $raiz = $juntarNomeIncompleto
            ? self::raizes($linhas->map(fn ($l) => $normalizar((string) $l->{$campo}))->unique()->values()->all())
            : [];

        return $linhas
            ->groupBy(function ($linha) use ($campo, $normalizar, $raiz) {
                $chave = $normalizar((string) $linha->{$campo});

                return $raiz[$chave] ?? $chave;
            })
            ->map(function (Collection $grupo) use ($campo, $normalizar) {
                // Mostra o nome mais completo; entre os de mesmo tamanho, a grafia que mais gastou (e, no empate, a primeira em ordem alfabética).
                $palavras = fn ($l) => count(array_filter(explode(' ', $normalizar((string) $l->{$campo}))));
                $principal = $grupo
                    ->sort(fn ($a, $b) => [$palavras($b), (float) $b->total_gasto, (string) $a->{$campo}] <=> [$palavras($a), (float) $a->total_gasto, (string) $b->{$campo}])
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

    /**
     * Para cada nome (já normalizado) que é só o começo, palavra por palavra, de outros nomes, diz a qual nome completo ele pertence.
     * Só junta quando existe exatamente UM nome completo possível (um que não seja o começo de outro).
     *
     * @param  array<int, string>  $chaves
     * @return array<string, string>  nome curto => nome completo
     */
    private static function raizes(array $chaves): array
    {
        $palavras = [];
        foreach ($chaves as $chave) {
            $palavras[$chave] = $chave === '' ? [] : explode(' ', $chave);
        }

        $ehComeco = fn (array $curto, array $longo) => count($curto) > 0
            && count($curto) < count($longo)
            && array_slice($longo, 0, count($curto)) === $curto;

        $raiz = [];

        foreach ($chaves as $chave) {
            $candidatos = array_filter($chaves, fn ($outro) => $ehComeco($palavras[$chave], $palavras[$outro]));
            $completos = array_filter($candidatos, fn ($c) => !array_filter($candidatos, fn ($outro) => $ehComeco($palavras[$c], $palavras[$outro])));

            if (count($completos) === 1) {
                $raiz[$chave] = array_values($completos)[0];
            }
        }

        return $raiz;
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
