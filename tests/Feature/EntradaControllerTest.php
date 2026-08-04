<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntradaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('entrada.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('entrada.index'));

        $response->assertForbidden();
    }

    public function test_conferente_is_forbidden(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('entrada.index'));

        $response->assertForbidden();
    }

    public function test_entrada_role_can_access_index(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('entrada.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_conferido_ok_or_avancado_without_entrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item OK Sem Entrada',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Item Avancado Sem Entrada',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente',
            'product_name' => 'Item Divergente Ainda Pendente',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'cancelado',
            'product_name' => 'Item Cancelado',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => null,
            'product_name' => 'Item Aguardando Conferencia',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Item Ja Com Entrada',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('Item OK Sem Entrada');
        $response->assertSee('Item Avancado Sem Entrada');
        $response->assertDontSee('Item Divergente Ainda Pendente');
        $response->assertDontSee('Item Cancelado');
        $response->assertDontSee('Item Aguardando Conferencia');
        $response->assertDontSee('Item Ja Com Entrada');
    }

    public function test_index_shows_quantities_and_avancado_warning(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'quantity' => 23,
            'quantidade_recebida' => 19,
            'product_name' => 'Produto Avisado',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('23 / 19');
        $response->assertSee('Avançado Mesmo Assim');
    }

    public function test_index_shows_empty_message_when_no_items(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('Nenhum item liberado aguardando entrada.');
    }

    private function itemLiberadoParaEntrada(array $overrides = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'quantity' => 10,
            'quantidade_recebida' => 10,
            'requester_name' => 'Vendedor Original',
        ], $overrides));
    }

    public function test_dar_entrada_sets_vendedor_quantidade_e_timestamp(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertRedirect(route('entrada.index'));
        $fresh = $req->fresh();
        $this->assertSame('Vendedor Original', $fresh->vendedor_destino);
        $this->assertSame(10, $fresh->quantidade_entrada);
        $this->assertNotNull($fresh->entrada_concluida_em);
    }

    public function test_dar_entrada_requires_vendedor_destino(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'quantidade_entrada' => 10,
        ]);

        $response->assertSessionHasErrors('vendedor_destino');
        $this->assertNull($req->fresh()->entrada_concluida_em);
    }

    public function test_dar_entrada_requires_quantidade_entrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
        ]);

        $response->assertSessionHasErrors('quantidade_entrada');
    }

    public function test_dar_entrada_rejects_already_concluded_item(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada(['entrada_concluida_em' => now()]);

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertStatus(409);
    }

    public function test_dar_entrada_rejects_item_not_conferido_ok_or_avancado(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada(['status_conferencia' => 'divergente']);

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertStatus(409);
    }

    public function test_dar_entrada_requires_entrada_role(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($conferente)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertForbidden();
    }
}
