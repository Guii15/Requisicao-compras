<?php

namespace App\Support;

use App\Models\PurchaseRequest;
use Illuminate\Support\Collection;

/**
 * Empresa compradora é texto livre, mas "Binario", "binário" e "BINÁRIO" são a mesma empresa.
 * Ao salvar, a grafia digitada é trocada pela já usada em outros itens (a mais frequente;
 * no empate, a com acento). Empresa nova fica como foi digitada.
 */
class EmpresaCompradora
{
    /** Remove espaços das pontas e repetidos no meio. Vazio vira null. */
    public static function limpar(?string $texto): ?string
    {
        $limpo = trim((string) preg_replace('/\s+/u', ' ', (string) $texto));

        return $limpo === '' ? null : $limpo;
    }

    /** Mesma empresa = mesma chave (sem acento, sem maiúscula, sem pontuação). */
    public static function chave(?string $nome): string
    {
        return RankingPorNome::chave($nome);
    }

    /**
     * Grafia que deve ser gravada para o que o admin digitou.
     * O próprio item fica fora da comparação, para dar para corrigir a grafia de uma empresa só dele.
     */
    public static function canonica(?string $digitado, ?int $ignorarItemId = null): ?string
    {
        $digitado = self::limpar($digitado);
        if ($digitado === null) {
            return null;
        }

        $chave = self::chave($digitado);
        $existentes = self::contagens($ignorarItemId)
            ->filter(fn (int $qtd, string $grafia) => self::chave($grafia) === $chave);

        return $existentes->isEmpty() ? $digitado : self::vencedora($existentes);
    }

    /** Uma sugestão por empresa, na grafia vencedora, em ordem alfabética. */
    public static function sugestoes(): Collection
    {
        return self::contagens()
            ->groupBy(fn (int $qtd, string $grafia) => self::chave($grafia), preserveKeys: true)
            ->map(fn (Collection $grupo) => self::vencedora($grupo))
            ->sort(fn (string $a, string $b) => strcmp(self::chave($a), self::chave($b)))
            ->values();
    }

    /** @return Collection<string, int> grafia => quantos itens usam */
    private static function contagens(?int $ignorarItemId = null): Collection
    {
        return PurchaseRequest::query()
            ->whereNotNull('empresa_compradora')
            ->where('empresa_compradora', '!=', '')
            ->when($ignorarItemId, fn ($q) => $q->where('id', '!=', $ignorarItemId))
            ->selectRaw('empresa_compradora, count(*) as qtd')
            ->groupBy('empresa_compradora')
            ->pluck('qtd', 'empresa_compradora')
            ->map(fn ($qtd) => (int) $qtd);
    }

    /** Mais usada; no empate, a com mais caracteres acentuados; depois, ordem alfabética (estável). */
    private static function vencedora(Collection $grafias): string
    {
        $acentos = fn (string $g) => mb_strlen($g) - strlen(preg_replace('/[^\x00-\x7F]/u', '', $g));

        return $grafias->keys()
            ->sort(function (string $a, string $b) use ($grafias, $acentos) {
                return [$grafias[$b], $acentos($b), $a] <=> [$grafias[$a], $acentos($a), $b];
            })
            ->first();
    }
}
