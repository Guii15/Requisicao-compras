<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObsAdminVisivelTest extends TestCase
{
    use RefreshDatabase;

    private function itemComObsAdmin(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 2, 'quantidade_recebida' => 2,
            'admin_note' => 'Comprar so da loja X, urgente',
        ], $attrs));
    }

    public function test_entrada_ve_a_observacao_do_admin(): void
    {
        $this->itemComObsAdmin();

        $this->actingAs(User::factory()->create(['role' => 'entrada']))
            ->get(route('entrada.index'))
            ->assertSee('Obs (Admin)')
            ->assertSee('Comprar so da loja X, urgente');
    }

    public function test_vendedor_ve_a_observacao_do_admin_direto_na_lista(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemComObsAdmin(['user_id' => $vendedor->id]);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertSee('Obs (Admin)')
            ->assertSee('Comprar so da loja X, urgente');
    }

    public function test_sem_observacao_do_admin_nao_mostra_o_bloco(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemComObsAdmin(['user_id' => $vendedor->id, 'admin_note' => null]);

        $this->actingAs($vendedor)->get(route('requests.index'))->assertDontSee('Obs (Admin)');
        $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))->assertDontSee('Obs (Admin)');
    }
}
