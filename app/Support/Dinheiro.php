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

    /** 1250.5 -> "R$ 1.250,50" */
    public static function brl(float|int|string|null $valor): string
    {
        return 'R$ ' . number_format((float) $valor, 2, ',', '.');
    }
}
