<?php

namespace App\Services;

use App\Models\Fornecedor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Junta grafias diferentes do mesmo fornecedor ("Joyce", "JOYCE INFORMATICA LTDA"...).
 * Só agrupa sozinho o que fica IDÊNTICO depois de Fornecedor::normalizar; o que é
 * apenas parecido vira sugestão para uma pessoa decidir.
 */
class UnificadorFornecedores
{
    /** Texto que a pessoa digitou: o original guardado, ou o supplier se ainda não foi unificado. */
    private const TEXTO_ORIGINAL = 'COALESCE(supplier_original, supplier)';

    /**
     * Cada grafia distinta digitada, com quantas compras usam ela.
     *
     * @return Collection<int, array{original: string, normalizado: string, compras: int}>
     */
    public function variantes(): Collection
    {
        return DB::table('purchase_requests')
            ->selectRaw(self::TEXTO_ORIGINAL . ' as original, COUNT(*) as compras')
            ->whereRaw(self::TEXTO_ORIGINAL . ' IS NOT NULL')
            ->groupByRaw(self::TEXTO_ORIGINAL)
            ->get()
            ->map(fn ($linha) => [
                'original' => $linha->original,
                'normalizado' => Fornecedor::normalizar($linha->original),
                'compras' => (int) $linha->compras,
            ])
            ->filter(fn ($v) => $v['normalizado'] !== '')
            ->values();
    }

    /**
     * Grafias agrupadas pelo nome normalizado. O nome final é o do fornecedor que já
     * existe com essa chave; senão, a grafia mais usada (empate: a mais longa).
     *
     * @return Collection<int, array{normalizado: string, nome_final: string, compras: int, variantes: array}>
     */
    public function grupos(): Collection
    {
        $existentes = Fornecedor::pluck('nome', 'nome_normalizado');

        return $this->variantes()
            ->groupBy('normalizado')
            ->map(function (Collection $variantes, string $normalizado) use ($existentes) {
                $maisUsada = $variantes
                    ->sort(fn ($a, $b) => [$b['compras'], mb_strlen(trim($b['original'])), trim($a['original'])]
                        <=> [$a['compras'], mb_strlen(trim($a['original'])), trim($b['original'])])
                    ->first();

                return [
                    'normalizado' => $normalizado,
                    'nome_final' => $existentes[$normalizado] ?? trim(preg_replace('/\s+/u', ' ', $maisUsada['original'])),
                    'compras' => $variantes->sum('compras'),
                    'variantes' => $variantes->sortByDesc('compras')->values()->all(),
                ];
            })
            ->sortBy('normalizado')
            ->values();
    }

    /**
     * Pares de grupos parecidos que NÃO são juntados sozinhos.
     *
     * @return Collection<int, array{a: string, b: string, motivo: string}>
     */
    public function sugestoes(): Collection
    {
        $chaves = $this->grupos()->pluck('normalizado')->all();
        $pares = collect();

        foreach ($chaves as $i => $a) {
            foreach (array_slice($chaves, $i + 1) as $b) {
                $motivo = $this->motivoParecido($a, $b);
                if ($motivo !== null) {
                    $pares->push(['a' => $a, 'b' => $b, 'motivo' => $motivo]);
                }
            }
        }

        return $pares;
    }

    public function motivoParecido(string $a, string $b): ?string
    {
        [$curto, $longo] = mb_strlen($a) <= mb_strlen($b) ? [$a, $b] : [$b, $a];

        if (mb_strlen($curto) >= 3 && str_contains(" {$longo} ", " {$curto} ")) {
            return "\"{$curto}\" está dentro de \"{$longo}\"";
        }

        if (mb_strlen($curto) >= 5 && levenshtein($a, $b) <= 2) {
            return 'diferença de ' . levenshtein($a, $b) . ' letra(s)';
        }

        return null;
    }

    /**
     * Mapa padrão: cada grafia vai para o nome final do seu grupo (só idênticos).
     *
     * @return array<string, string> grafia original => nome final
     */
    public function mapaPadrao(): array
    {
        $mapa = [];
        foreach ($this->grupos() as $grupo) {
            foreach ($grupo['variantes'] as $variante) {
                $mapa[$variante['original']] = $grupo['nome_final'];
            }
        }

        return $mapa;
    }

    /**
     * Grava o mapa. Idempotente: compras que já estão certas não são tocadas.
     *
     * @param array<string, string> $mapa grafia original => nome final
     * @return array{fornecedores_criados: int, compras_alteradas: int}
     */
    public function aplicar(array $mapa): array
    {
        return DB::transaction(function () use ($mapa) {
            $criados = 0;
            $alteradas = 0;

            foreach ($mapa as $original => $nomeFinal) {
                $nomeFinal = trim(preg_replace('/\s+/u', ' ', (string) $nomeFinal));
                $chave = Fornecedor::normalizar($nomeFinal);
                if ($chave === '') {
                    continue;
                }

                $fornecedor = Fornecedor::firstOrCreate(['nome_normalizado' => $chave], ['nome' => $nomeFinal]);
                $criados += $fornecedor->wasRecentlyCreated ? 1 : 0;

                $alteradas += DB::table('purchase_requests')
                    ->whereRaw(self::TEXTO_ORIGINAL . ' = ?', [$original])
                    ->where(fn ($q) => $q->whereNull('fornecedor_id')
                        ->orWhere('fornecedor_id', '!=', $fornecedor->id)
                        ->orWhere('supplier', '!=', $fornecedor->nome)
                        ->orWhereNull('supplier_original'))
                    ->update([
                        'supplier_original' => DB::raw(self::TEXTO_ORIGINAL),
                        'supplier' => $fornecedor->nome,
                        'fornecedor_id' => $fornecedor->id,
                    ]);
            }

            return ['fornecedores_criados' => $criados, 'compras_alteradas' => $alteradas];
        });
    }
}
