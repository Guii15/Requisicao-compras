<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestHistoricoFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_criado_normalmente_recebe_tipo_registro_requisicao_por_padrao(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertSame('requisicao', $request->fresh()->tipo_registro);
    }

    public function test_registro_historico_grava_campos_de_rastreabilidade_e_dados_extra(): void
    {
        $request = PurchaseRequest::factory()->create([
            'tipo_registro' => 'compra_historica',
            'data_compra' => '2026-01-26',
            'origem_id' => 'JAN._FEV._L8',
            'aba_origem' => 'JAN.  FEV.',
            'mes_origem' => 'Jan-Fev',
            'dados_importacao' => ['pedido' => '202348', 'filial' => 'C3 Tech'],
        ]);

        $fresh = $request->fresh();

        $this->assertSame('compra_historica', $fresh->tipo_registro);
        $this->assertSame('2026-01-26', $fresh->data_compra->format('Y-m-d'));
        $this->assertSame('JAN._FEV._L8', $fresh->origem_id);
        $this->assertSame('JAN.  FEV.', $fresh->aba_origem);
        $this->assertSame('Jan-Fev', $fresh->mes_origem);
        $this->assertSame(['pedido' => '202348', 'filial' => 'C3 Tech'], $fresh->dados_importacao);
    }

    public function test_origem_id_e_unico(): void
    {
        PurchaseRequest::factory()->create(['origem_id' => 'DUPLICADO']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        PurchaseRequest::factory()->create(['origem_id' => 'DUPLICADO']);
    }
}
