<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestHistoricoScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_consultas_padrao_nao_trazem_registros_historicos(): void
    {
        PurchaseRequest::factory()->create();
        PurchaseRequest::factory()->create(['tipo_registro' => 'compra_historica']);
        PurchaseRequest::factory()->create(['tipo_registro' => 'cotacao_historica']);

        $this->assertSame(1, PurchaseRequest::count());
    }

    public function test_scope_historico_traz_so_os_registros_importados(): void
    {
        PurchaseRequest::factory()->create();
        PurchaseRequest::factory()->create(['tipo_registro' => 'compra_historica']);
        PurchaseRequest::factory()->create(['tipo_registro' => 'cotacao_historica']);

        $this->assertSame(2, PurchaseRequest::historico()->count());
    }
}
