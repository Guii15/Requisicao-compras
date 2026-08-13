<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_index_groups_items_with_same_grupo_id(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $grupoId = (string) \Illuminate\Support\Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Item Um']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Item Dois']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));
        $grupos = $response->original->getData()['requests'];

        $this->assertCount(1, $grupos);
        $this->assertCount(2, $grupos->first());
    }

    public function test_index_aguardando_shows_item_with_entrada_ja_dada_from_same_group_without_dar_entrada_button(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $grupoId = (string) \Illuminate\Support\Str::uuid();
        PurchaseRequest::factory()->create([
            'grupo_id' => $grupoId, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item Aguardando Entrada',
        ]);
        PurchaseRequest::factory()->create([
            'grupo_id' => $grupoId, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(), 'vendedor_destino' => 'Vendedor X', 'quantidade_entrada' => 5,
            'product_name' => 'Item Ja Com Entrada No Mesmo Grupo',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));
        $html = $response->getContent();

        $response->assertSee('Item Aguardando Entrada');
        $response->assertSee('Item Ja Com Entrada No Mesmo Grupo');

        $posJaComEntrada = strpos($html, 'Item Ja Com Entrada No Mesmo Grupo');
        $posEntradaEm = strpos($html, 'Entrada em', $posJaComEntrada);
        $this->assertNotFalse($posEntradaEm);
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

    public function test_index_concluidas_aba_lists_only_items_with_entrada_registrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'vendedor_destino' => 'Vendedor Destino Final',
            'quantidade_entrada' => 7,
            'product_name' => 'Item Ja Com Entrada Registrada',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item Ainda Aguardando Entrada',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index', ['aba' => 'concluidas']));

        $response->assertSee('Item Ja Com Entrada Registrada');
        $response->assertSee('Vendedor Destino Final');
        $response->assertDontSee('Item Ainda Aguardando Entrada');
    }

    public function test_index_aguardando_aba_does_not_list_item_with_entrada_registrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Item Ja Com Entrada Registrada',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertDontSee('Item Ja Com Entrada Registrada');
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

    public function test_index_shows_photo_thumbnail_when_foto_exists(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
        ]);
        ConferenciaFoto::create([
            'purchase_request_id' => $req->id,
            'caminho_arquivo' => 'conferencia/teste-thumb.jpg',
            'nome_original' => 'foto.jpg',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('<img', false);
        $response->assertSee(Storage::url('conferencia/teste-thumb.jpg'), false);
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

    public function test_index_does_not_list_item_with_status_not_aprovado(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'rejeitado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item Rejeitado Apos Conferencia',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertDontSee('Item Rejeitado Apos Conferencia');
    }

    public function test_dar_entrada_rejects_item_with_status_not_aprovado(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada(['status' => 'rejeitado']);

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
