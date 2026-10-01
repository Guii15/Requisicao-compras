<?php

namespace App\Support;

class Dinheiro
{
    /** Converte "1.250,50" em "1250.50"; valor já com ponto decimal passa direto. */
    public static function decimal(?string $valor): string
    {
        $valor = trim(str_replace(['R$', ' '], '', (string) $valor));

        if (str_contains($valor, ',')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        }

        return $valor;
    }

    /** Valor curto para eixos de gráfico: 50000 -> "R$ 50 mil", 1500000 -> "R$ 1,5 mi". */
    public static function compacto(float|int $valor): string
    {
        $valor = (float) $valor;

        if ($valor >= 1_000_000) {
            return 'R$ ' . rtrim(rtrim(number_format($valor / 1_000_000, 1, ',', '.'), '0'), ',') . ' mi';
        }

        if ($valor >= 1_000) {
            return 'R$ ' . rtrim(rtrim(number_format($valor / 1_000, 1, ',', '.'), '0'), ',') . ' mil';
        }

        return 'R$ ' . number_format($valor, 0, ',', '.');
    }

    /** 1250.5 -> "R$ 1.250,50" */
    public static function brl(float|int|string|null $valor): string
    {
        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }
}
