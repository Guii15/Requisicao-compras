<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestConferenciaFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tipo_entrega_defaults_to_estoque(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertSame('estoque', $request->fresh()->tipo_entrega);
    }

    public function test_status_conferencia_defaults_to_null(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertNull($request->fresh()->status_conferencia);
    }

    public function test_conferencia_fields_are_mass_assignable_and_persist(): void
    {
        $conferente = User::factory()->create();

        $request = PurchaseRequest::factory()->create([
            'tipo_entrega' => 'entrega_direta',
            'status_conferencia' => 'divergente',
            'quantidade_recebida' => 3,
            'observacao_conferencia' => 'Caixa amassada',
            'conferente_id' => $conferente->id,
        ]);

        $request = $request->fresh();
        $this->assertSame('entrega_direta', $request->tipo_entrega);
        $this->assertSame('divergente', $request->status_conferencia);
        $this->assertSame(3, $request->quantidade_recebida);
        $this->assertSame('Caixa amassada', $request->observacao_conferencia);
        $this->assertTrue($request->conferente->is($conferente));
    }
}
