<?php

namespace Tests\Unit;

use App\Models\Fornecedor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FornecedorNormalizarTest extends TestCase
{
    public static function casos(): array
    {
        return [
            'caixa e espaços'              => [' joyce ', 'JOYCE'],
            'maiúsculas'                   => ['JOYCE', 'JOYCE'],
            'acento'                       => ['Joyce Informática', 'JOYCE INFORMATICA'],
            'sufixo LTDA'                  => ['JOYCE INFORMATICA LTDA', 'JOYCE INFORMATICA'],
            'sufixo com ponto'             => ['Joyce Informática Ltda.', 'JOYCE INFORMATICA'],
            'S/A'                          => ['Kabum  Comércio   S/A', 'KABUM COMERCIO'],
            'S.A.'                         => ['Kabum Comercio S.A.', 'KABUM COMERCIO'],
            'SA junto'                     => ['Kabum Comercio SA', 'KABUM COMERCIO'],
            'vários sufixos'               => ['Auto Peças Sul ltda. - ME', 'AUTO PECAS SUL'],
            'EIRELI e EPP'                 => ['Joyce Informatica Eireli EPP', 'JOYCE INFORMATICA'],
            'pontuação no meio'            => ['G.P.J. Distribuidora', 'G P J DISTRIBUIDORA'],
            'hífen e barra'                => ['Auto-Peças/Sul', 'AUTO PECAS SUL'],
            'ME no meio fica'              => ['Casa ME Gusta Ltda', 'CASA ME GUSTA'],
            'S A no começo fica'           => ['S.A. Distribuidora', 'S A DISTRIBUIDORA'],
            'só o sufixo não some'         => ['EIRELI', 'EIRELI'],
            'cedilha e til'                => ['Açaí São João', 'ACAI SAO JOAO'],
            'só pontuação vira vazio'      => ['  ---  ', ''],
            'vazio'                        => ['', ''],
            'nulo'                         => [null, ''],
        ];
    }

    #[DataProvider('casos')]
    public function test_normaliza(?string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, Fornecedor::normalizar($entrada));
    }

    public function test_variacoes_do_mesmo_fornecedor_viram_a_mesma_chave(): void
    {
        $variacoes = ['Joyce Informática', 'JOYCE INFORMATICA LTDA', ' joyce  informatica ', 'Joyce Informatica Ltda. ME'];

        $chaves = array_unique(array_map([Fornecedor::class, 'normalizar'], $variacoes));

        $this->assertSame(['JOYCE INFORMATICA'], array_values($chaves));
    }
}
