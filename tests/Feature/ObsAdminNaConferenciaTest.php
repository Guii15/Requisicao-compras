<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObsAdminNaConferenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_conferencia_ve_a_observacao_do_admin_no_item_aguardando(): void
    {
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'admin_note' => 'Conferir o número de série antes de liberar',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index'))
            ->assertOk()
            ->assertSee('>ADMIN</span>', false) // etiqueta da nota do admin (cartão do item e janela Conferir Item)
            ->assertSee('Conferir o número de série antes de liberar');
    }

    public function test_conferencia_ve_a_observacao_do_admin_nos_conferidos(): void
    {
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'admin_note' => 'Pedido urgente da filial 31',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index', ['aba' => 'conferidos']))
            ->assertSee('Pedido urgente da filial 31');
    }

    public function test_sem_observacao_do_admin_nao_mostra_o_bloco(): void
    {
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'admin_note' => null]);

        $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index'))
            ->assertDontSee('Obs (Admin)')
            ->assertDontSee('>ADMIN</span>', false);
    }
}
