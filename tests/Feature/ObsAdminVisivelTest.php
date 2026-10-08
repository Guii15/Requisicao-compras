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
            ->assertSee('>ADMIN</span>', false) // etiqueta da nota do admin no cartão do item
            ->assertSee('Comprar so da loja X, urgente');
    }

    public function test_vendedor_ve_a_observacao_do_admin_direto_na_lista(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemComObsAdmin(['user_id' => $vendedor->id]);

        // a nota do admin vem no cartão do item com a etiqueta ADMIN (uma marcação só para PC e celular)
        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertSee('>ADMIN</span>', false)
            ->assertSee('Comprar so da loja X, urgente');
    }

    public function test_sem_observacao_do_admin_nao_mostra_o_bloco(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemComObsAdmin(['user_id' => $vendedor->id, 'admin_note' => null]);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertDontSee('Obs (Admin)')
            ->assertDontSee('>ADMIN</span>', false);
        $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))
            ->assertDontSee('Obs (Admin)')
            ->assertDontSee('>ADMIN</span>', false);
    }

    public function test_quadro_dar_entrada_mostra_a_observacao_do_admin(): void
    {
        $this->itemComObsAdmin();

        $html = $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index'))->getContent();

        // cartão do item + janela "Dar Entrada" (uma marcação só para PC e celular)
        $this->assertSame(2, substr_count($html, 'Comprar so da loja X, urgente'));
        $this->assertSame(2, substr_count($html, '>ADMIN</span>'));
        $this->assertStringNotContainsString('modal-entrada-m-', $html); // a janela do celular saiu
    }

    public function test_vendedor_nao_tem_mais_o_ver_obs_nem_o_modal_do_compras(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->itemComObsAdmin(['user_id' => $vendedor->id]);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertDontSee('Ver obs.')
            ->assertDontSee('Observação do Compras');
    }
}
