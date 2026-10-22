<?php

namespace Tests\Unit;

use App\Services\DesdobramentoCheque;
use PHPUnit\Framework\TestCase;

/** Divide um pagamento em folhas de cheque de valores iguais, cada uma abaixo do limite (R$ 5.000,00). */
class DesdobramentoChequeTest extends TestCase
{
    private function valores(int $centavos): array
    {
        return (new DesdobramentoCheque(5000))->valores($centavos);
    }

    public function test_abaixo_do_limite_e_uma_folha_so(): void
    {
        $this->assertSame([499999], $this->valores(499999));
        $this->assertSame([100], $this->valores(100));
    }

    public function test_exatamente_5_mil_vira_duas_folhas_porque_tem_que_ficar_abaixo(): void
    {
        $this->assertSame([250000, 250000], $this->valores(500000));
    }

    public function test_divide_em_valores_iguais(): void
    {
        $this->assertSame([460000, 460000, 460000, 460000, 460000], $this->valores(2300000));
    }

    public function test_centavos_que_sobram_vao_nas_primeiras_folhas_e_a_soma_bate(): void
    {
        $this->assertSame([333334, 333333, 333333], $this->valores(1000000));
        $this->assertSame(1000000, array_sum($this->valores(1000000)));
    }

    public function test_nenhuma_folha_chega_ao_limite_nem_no_caso_de_borda(): void
    {
        // R$ 9.999,99 em 2 folhas daria uma de R$ 5.000,00: precisa de 3.
        $this->assertSame([333333, 333333, 333333], $this->valores(999999));
        $this->assertSame([499999, 499999], $this->valores(999998));

        foreach ([1, 499999, 500000, 500001, 999999, 1000000, 1234567, 10000000, 98765432] as $c) {
            $folhas = $this->valores($c);
            $this->assertSame($c, array_sum($folhas), "soma de {$c}");
            $this->assertLessThan(500000, max($folhas), "limite em {$c}");
            $this->assertLessThanOrEqual(1, max($folhas) - min($folhas), "iguais em {$c}");
        }
    }
}
