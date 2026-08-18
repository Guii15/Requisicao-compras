<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A tela de Requisições do Admin so' mostra pendentes (itens que ainda precisam de
 * acao). Requisições finalizadas (aprovadas com entrada, ou rejeitadas) saem daqui
 * e passam a viver em admin.historico-compras (ver AdminHistoricoComprasTest).
 */
class AdminRequestsNovasHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_shows_pendente_item(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Item Pendente']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Item Pendente');
    }

    public function test_shows_aprovado_aguardando_entrada(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'entrada_concluida_em' => null, 'product_name' => 'Item Aprovado Sem Entrada',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Item Aprovado Sem Entrada');
    }

    public function test_does_not_show_rejeitado(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'rejeitado', 'product_name' => 'Item Rejeitado']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertDontSee('Item Rejeitado');
    }

    public function test_does_not_show_aprovado_com_entrada_concluida(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'entrada_concluida_em' => now(), 'product_name' => 'Item Finalizado',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertDontSee('Item Finalizado');
    }

    public function test_group_with_at_least_one_unfinalized_item_shows(): void
    {
        $admin = $this->admin();
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'rejeitado', 'product_name' => 'Item Ja Rejeitado No Grupo']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'pendente', 'product_name' => 'Item Ainda Pendente No Grupo']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Item Ja Rejeitado No Grupo');
        $response->assertSee('Item Ainda Pendente No Grupo');
    }

    public function test_group_fully_finalized_is_hidden(): void
    {
        $admin = $this->admin();
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'rejeitado', 'product_name' => 'Item A Finalizado']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'aprovado', 'entrada_concluida_em' => now(), 'product_name' => 'Item B Finalizado']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertDontSee('Item A Finalizado');
        $response->assertDontSee('Item B Finalizado');
    }

    public function test_filters_still_work(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Filtro Alvo']);
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Outro Produto']);

        $response = $this->actingAs($admin)->get(route('admin.index', ['product_name' => 'Filtro Alvo']));

        $response->assertSee('Filtro Alvo');
        $response->assertDontSee('Outro Produto');
    }
}
