<?php

namespace App\Services;

/**
 * Divide um pagamento em folhas de cheque de valores iguais, cada uma ABAIXO do limite.
 * Toda a conta é feita em centavos, para a soma das folhas bater exatamente com o pagamento.
 */
class DesdobramentoCheque
{
    private int $limiteCentavos;

    public function __construct(?float $limite = null)
    {
        $this->limiteCentavos = (int) round(($limite ?? (float) config('financeiro.limite_cheque', 5000)) * 100);
    }

    public static function centavos(float|string $valor): int
    {
        return (int) round((float) $valor * 100);
    }

    public function limite(): float
    {
        return $this->limiteCentavos / 100;
    }

    /**
     * Valor de cada folha, em centavos. Os centavos que não dividem certo vão um em cada nas primeiras folhas.
     * O número de folhas é o menor que deixa todas abaixo do limite: R$ 5.000,00 vira 2 folhas, R$ 9.999,99 vira 3.
     *
     * @return list<int>
     */
    public function valores(int $centavos): array
    {
        if ($centavos <= 0) {
            return [];
        }

        $maximo = $this->limiteCentavos - 1;
        $folhas = intdiv($centavos + $maximo - 1, $maximo);
        $base = intdiv($centavos, $folhas);
        $resto = $centavos % $folhas;

        $valores = [];
        for ($i = 0; $i < $folhas; $i++) {
            $valores[] = $base + ($i < $resto ? 1 : 0);
        }

        return $valores;
    }
}
