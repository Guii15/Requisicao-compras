<?php

namespace Tests\Unit;

use App\Support\LarguraBarra;
use PHPUnit\Framework\TestCase;

class LarguraBarraTest extends TestCase
{
    public function test_o_maior_valor_ocupa_a_barra_toda(): void
    {
        $this->assertSame(100, LarguraBarra::percentual(500.0, 500.0));
    }

    public function test_valor_intermediario_e_proporcional(): void
    {
        $this->assertSame(50, LarguraBarra::percentual(250.0, 500.0));
        $this->assertSame(26, LarguraBarra::percentual(225621.70, 882860.60)); // 25,56% arredonda para 26
    }

    public function test_valor_pequeno_perto_de_um_gigante_continua_visivel(): void
    {
        // 225 mil contra 41 milhões daria 0,5%: a barra sumiria
        $this->assertSame(3, LarguraBarra::percentual(225621.70, 41282860.60));
    }

    public function test_minimo_pode_ser_trocado(): void
    {
        $this->assertSame(5, LarguraBarra::percentual(1.0, 1000000.0, 5));
    }

    public function test_valor_zero_ou_negativo_nao_desenha_barra(): void
    {
        $this->assertSame(0, LarguraBarra::percentual(0.0, 500.0));
        $this->assertSame(0, LarguraBarra::percentual(-10.0, 500.0));
    }

    public function test_maximo_zero_ou_nulo_nao_quebra(): void
    {
        $this->assertSame(0, LarguraBarra::percentual(0.0, 0.0));
        $this->assertSame(0, LarguraBarra::percentual(10.0, 0.0));
    }

    public function test_nunca_passa_de_cem(): void
    {
        $this->assertSame(100, LarguraBarra::percentual(900.0, 500.0));
    }

    public function test_aceita_numero_em_texto_como_vem_do_banco(): void
    {
        $this->assertSame(50, LarguraBarra::percentual('250.00', '500.00'));
    }
}
