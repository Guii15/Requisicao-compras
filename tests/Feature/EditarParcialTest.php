<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Item "restante" de um recebimento parcial (aguardando o resto chegar): a conferência pode
 * editar a quantidade que ainda falta, porque não se sabe quanto nem quando vai chegar.
 */
class EditarParcialTest extends TestCase
{
    use RefreshDatabase;

    private function restante(array $attrs = []): PurchaseRequest
    {
        $original = PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 50, 'quantidade_original' => 100,
        ]);

        return PurchaseRequest::factory()->create(array_merge([
            'grupo_id' => $original->grupo_id, 'status' => 'aprovado', 'status_conferencia' => null,
            'quantity' => 50, 'quantidade_original' => 100, 'restante_de_id' => $original->id, 'product_name' => 'Cabo HDMI',
        ], $attrs));
    }

    private function editar(User $user, PurchaseRequest $item, $quantidade)
    {
        return $this->actingAs($user)->patch(route('conferencia.editarParcial', $item), ['quantity' => $quantidade]);
    }

    public function test_conferente_edita_a_quantidade_que_falta(): void
    {
        $item = $this->restante();

        $this->editar(User::factory()->create(['role' => 'conferente']), $item, 30)
            ->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(30, $item->fresh()->quantity);
        $this->assertSame(100, $item->fresh()->quantidade_original);
    }

    public function test_quem_e_da_entrada_tambem_edita(): void
    {
        $item = $this->restante();

        $this->editar(User::factory()->create(['role' => 'entrada']), $item, 20)->assertSessionHasNoErrors();

        $this->assertSame(20, $item->fresh()->quantity);
    }

    public function test_vendedor_nao_edita(): void
    {
        $item = $this->restante();

        $this->editar(User::factory()->create(['role' => null]), $item, 10)->assertForbidden();

        $this->assertSame(50, $item->fresh()->quantity);
    }

    public function test_nao_aceita_zero_nem_mais_do_que_falta(): void
    {
        $item = $this->restante();
        $conferente = User::factory()->create(['role' => 'conferente']);

        $this->editar($conferente, $item, 0)->assertSessionHasErrors('quantity');
        $this->editar($conferente, $item, 51)->assertSessionHasErrors('quantity');
        $this->editar($conferente, $item, 'abc')->assertSessionHasErrors('quantity');

        $this->assertSame(50, $item->fresh()->quantity);
    }

    public function test_so_edita_item_parcial_que_ainda_nao_foi_conferido(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $comum = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'quantity' => 50]);
        $jaConferido = $this->restante(['status_conferencia' => 'conferido_ok']);

        $this->editar($conferente, $comum, 10)->assertSessionHas('aviso');
        $this->editar($conferente, $jaConferido, 10)->assertSessionHas('aviso');

        $this->assertSame(50, $comum->fresh()->quantity);
        $this->assertSame(50, $jaConferido->fresh()->quantity);
    }

    public function test_botao_editar_aparece_so_no_item_parcial_aguardando(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $parcial = $this->restante();
        $comum = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'quantity' => 7, 'product_name' => 'Item Comum']);

        $this->actingAs($conferente)->get(route('conferencia.index'))
            ->assertSee(route('conferencia.editarParcial', $parcial), false)
            ->assertDontSee(route('conferencia.editarParcial', $comum), false);
    }
}
