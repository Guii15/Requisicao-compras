<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConferenciaFotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_foto_can_be_created_and_belongs_to_purchase_request(): void
    {
        $request = PurchaseRequest::factory()->create();

        $foto = ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/abc123.jpg',
            'nome_original' => 'foto.jpg',
        ]);

        $this->assertTrue($foto->purchaseRequest->is($request));
    }

    public function test_purchase_request_has_many_fotos_conferencia(): void
    {
        $request = PurchaseRequest::factory()->create();
        ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/foto1.jpg',
            'nome_original' => 'foto1.jpg',
        ]);
        ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/foto2.jpg',
            'nome_original' => 'foto2.jpg',
        ]);

        $this->assertCount(2, $request->fresh()->fotosConferencia);
    }

    public function test_deleting_purchase_request_cascades_to_fotos(): void
    {
        $request = PurchaseRequest::factory()->create();
        ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/foto1.jpg',
            'nome_original' => 'foto1.jpg',
        ]);

        $request->delete();

        $this->assertDatabaseCount('conferencia_fotos', 0);
    }
}
