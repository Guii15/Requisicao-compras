<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataDaCompraNaEntradaTest extends TestCase
{
    use RefreshDatabase;

    public function test_entrada_mostra_a_data_da_compra_na_lista_e_na_janela(): void
    {
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 2, 'quantidade_recebida' => 2,
            'data_compra' => '2026-09-17',
        ]);

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))
            ->assertOk()->assertSee('Data da compra')->assertSee('17/09/2026')->getContent();

        $this->assertStringContainsString('Compra em:', $html);
    }
}
