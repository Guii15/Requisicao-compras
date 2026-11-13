<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O anexo que o vendedor coloca na requisição (ex.: a folha do pedido) aparece no cartão do item
 * na Conferência e na Entrada, então quem confere e quem dá entrada precisa conseguir abrir.
 */
class AnexoNaConferenciaEntradaTest extends TestCase
{
    use RefreshDatabase;

    private function itemComAnexo(): PurchaseRequest
    {
        Storage::fake('local');
        Storage::disk('local')->put('anexos/folha.pdf', 'pdf');

        return PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'anexo_path' => 'anexos/folha.pdf',
            'anexo_nome' => 'folha.pdf',
        ]);
    }

    public function test_conferente_e_entrada_abrem_o_anexo_do_vendedor(): void
    {
        $item = $this->itemComAnexo();

        foreach (['conferente', 'entrada'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_admin' => false]))
                ->get(route('requests.anexo', $item))->assertOk();
        }
    }

    public function test_outro_vendedor_continua_sem_acesso(): void
    {
        $item = $this->itemComAnexo();

        $this->actingAs(User::factory()->create(['role' => null, 'is_admin' => false]))
            ->get(route('requests.anexo', $item))->assertForbidden();
    }
}
