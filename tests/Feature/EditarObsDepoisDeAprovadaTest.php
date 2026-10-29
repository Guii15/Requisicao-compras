<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A obs do vendedor (justification) pode ser corrigida depois de aprovada: o admin muda na janela Atualizar
 * e o vendedor tem um "Editar obs" que só mexe nela (o resto da requisição continua travado).
 */
class EditarObsDepoisDeAprovadaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function atualizarComoAdmin(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), array_merge([
            'status' => 'aprovado', 'supplier' => 'Kabum',
        ], $extra));
    }

    // ---------- admin ----------

    public function test_admin_corrige_a_obs_do_vendedor_numa_requisicao_aprovada(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'aprovado', 'justification' => 'Filial 13']);

        $this->atualizarComoAdmin($item, ['justification' => 'Filial 31'])->assertSessionHasNoErrors();

        $this->assertSame('Filial 31', $item->fresh()->justification);
    }

    public function test_sem_o_campo_a_obs_do_vendedor_fica_como_estava(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'aprovado', 'justification' => 'Filial 13']);

        $this->atualizarComoAdmin($item)->assertSessionHasNoErrors();

        $this->assertSame('Filial 13', $item->fresh()->justification);
    }

    public function test_obs_do_vendedor_tem_o_mesmo_limite_do_pedido(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'aprovado']);

        $this->atualizarComoAdmin($item, ['justification' => str_repeat('a', 501)])->assertSessionHasErrors('justification');
    }

    public function test_janela_atualizar_tem_o_campo_da_obs_do_vendedor_preenchido(): void
    {
        PurchaseRequest::factory()->create(['status' => 'pendente', 'justification' => 'Filial 13']);

        $this->actingAs($this->admin())->get(route('admin.index'))
            ->assertOk()
            ->assertSee('name="justification"', false)
            ->assertSee('Obs do Vendedor');
    }

    // ---------- vendedor ----------

    public function test_vendedor_corrige_so_a_obs_da_requisicao_aprovada(): void
    {
        $vendedor = User::factory()->create();
        $item = PurchaseRequest::factory()->create([
            'user_id' => $vendedor->id, 'status' => 'aprovado', 'justification' => 'Filial 13', 'quantity' => 4,
        ]);

        $this->actingAs($vendedor)->patch(route('requests.obs', $item), [
            'justification' => 'Filial 31', 'quantity' => 99, 'status' => 'pendente',
        ])->assertRedirect(route('requests.index'));

        $item->refresh();
        $this->assertSame('Filial 31', $item->justification);
        $this->assertSame(4, (int) $item->quantity);
        $this->assertSame('aprovado', $item->status);
    }

    public function test_vendedor_nao_mexe_na_obs_de_requisicao_de_outro(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'aprovado', 'justification' => 'Filial 13']);

        $this->actingAs(User::factory()->create())
            ->patch(route('requests.obs', $item), ['justification' => 'Outra'])
            ->assertForbidden();

        $this->assertSame('Filial 13', $item->fresh()->justification);
    }

    public function test_obs_do_vendedor_continua_obrigatoria(): void
    {
        $vendedor = User::factory()->create();
        $item = PurchaseRequest::factory()->create(['user_id' => $vendedor->id, 'status' => 'aprovado']);

        $this->actingAs($vendedor)->patch(route('requests.obs', $item), ['justification' => ''])
            ->assertSessionHasErrors('justification');
    }

    public function test_lista_do_vendedor_mostra_editar_obs_na_aprovada(): void
    {
        $vendedor = User::factory()->create();
        $item = PurchaseRequest::factory()->create(['user_id' => $vendedor->id, 'status' => 'aprovado']);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertOk()
            ->assertSee(route('requests.obs', $item), false)
            ->assertSee('Editar obs');
    }
}
