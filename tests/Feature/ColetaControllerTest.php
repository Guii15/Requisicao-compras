<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColetaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('coleta.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('coleta.index'));

        $response->assertForbidden();
    }

    public function test_conferente_is_forbidden(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('coleta.index'));

        $response->assertForbidden();
    }

    public function test_entrada_is_forbidden(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('coleta.index'));

        $response->assertForbidden();
    }

    public function test_coleta_role_can_access_index(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);

        $response = $this->actingAs($coleta)->get(route('coleta.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('coleta.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_conferido_ok_or_avancado_without_coleta(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item OK Sem Coleta',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Item Avancado Sem Coleta',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente',
            'product_name' => 'Item Divergente Ainda Pendente',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => null,
            'product_name' => 'Item Aguardando Conferencia',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'data_coleta' => '2026-09-01', 'coletado_por' => 'Sergio',
            'product_name' => 'Item Ja Coletado',
        ]);

        $response = $this->actingAs($coleta)->get(route('coleta.index'));

        $response->assertSee('Item OK Sem Coleta');
        $response->assertSee('Item Avancado Sem Coleta');
        $response->assertDontSee('Item Divergente Ainda Pendente');
        $response->assertDontSee('Item Aguardando Conferencia');
        $response->assertDontSee('Item Ja Coletado');
    }

    public function test_index_independe_do_status_de_entrada(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => null,
            'product_name' => 'Item Sem Entrada Ainda',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Item Ja Com Entrada',
        ]);

        $response = $this->actingAs($coleta)->get(route('coleta.index'));

        $response->assertSee('Item Sem Entrada Ainda');
        $response->assertSee('Item Ja Com Entrada');
    }

    public function test_index_coletados_aba_lists_only_items_with_data_coleta(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'data_coleta' => '2026-09-01', 'coletado_por' => 'Sergio',
            'product_name' => 'Item Ja Coletado',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item Ainda Aguardando Coleta',
        ]);

        $response = $this->actingAs($coleta)->get(route('coleta.index', ['aba' => 'coletados']));

        $response->assertSee('Item Ja Coletado');
        $response->assertSee('Sergio');
        $response->assertDontSee('Item Ainda Aguardando Coleta');
    }

    public function test_search_filters_by_product_name(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Bateria G7 Plus']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Carregador Turbo']);

        $response = $this->actingAs($coleta)->get(route('coleta.index', ['q' => 'bateria']));

        $response->assertSee('Bateria G7 Plus');
        $response->assertDontSee('Carregador Turbo');
    }

    private function itemLiberadoParaColeta(array $overrides = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
        ], $overrides));
    }

    public function test_registrar_coleta_sets_coletado_por_e_data(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        $req = $this->itemLiberadoParaColeta();

        $response = $this->actingAs($coleta)->patch(route('coleta.registrar', $req), [
            'coletado_por' => 'Sergio',
            'data_coleta'  => '2026-09-20',
        ]);

        $response->assertRedirect(route('coleta.index'));
        $fresh = $req->fresh();
        $this->assertSame('Sergio', $fresh->coletado_por);
        $this->assertSame('2026-09-20', $fresh->data_coleta->format('Y-m-d'));
    }

    public function test_registrar_coleta_aceita_data_retroativa(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        $req = $this->itemLiberadoParaColeta();

        $response = $this->actingAs($coleta)->patch(route('coleta.registrar', $req), [
            'coletado_por' => 'Sergio',
            'data_coleta'  => now()->subDays(3)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('coleta.index'));
        $this->assertNotNull($req->fresh()->data_coleta);
    }

    public function test_registrar_coleta_requires_coletado_por(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        $req = $this->itemLiberadoParaColeta();

        $response = $this->actingAs($coleta)->patch(route('coleta.registrar', $req), [
            'data_coleta' => '2026-09-20',
        ]);

        $response->assertSessionHasErrors('coletado_por');
        $this->assertNull($req->fresh()->data_coleta);
    }

    public function test_registrar_coleta_requires_data_coleta(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        $req = $this->itemLiberadoParaColeta();

        $response = $this->actingAs($coleta)->patch(route('coleta.registrar', $req), [
            'coletado_por' => 'Sergio',
        ]);

        $response->assertSessionHasErrors('data_coleta');
    }

    public function test_registrar_coleta_em_item_ainda_nao_conferido_mostra_mensagem_especifica(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        $req = PurchaseRequest::factory()->create(['status' => 'pendente', 'status_conferencia' => null]);

        $response = $this->actingAs($coleta)->patch(route('coleta.registrar', $req), [
            'coletado_por' => 'Sergio',
            'data_coleta'  => '2026-09-20',
        ]);

        $response->assertRedirect(route('coleta.index'));
        $response->assertSessionHas('aviso', 'Este item ainda não foi aprovado/conferido — não é possível registrar a coleta ainda.');
    }

    public function test_registrar_coleta_rejects_already_registered_item(): void
    {
        $coleta = User::factory()->create(['role' => 'coleta']);
        $req = $this->itemLiberadoParaColeta(['data_coleta' => '2026-09-01', 'coletado_por' => 'Sergio']);

        $response = $this->actingAs($coleta)->patch(route('coleta.registrar', $req), [
            'coletado_por' => 'Outra Pessoa',
            'data_coleta'  => '2026-09-25',
        ]);

        $response->assertRedirect(route('coleta.index'));
        $response->assertSessionHas('aviso');
        $this->assertSame('Sergio', $req->fresh()->coletado_por);
    }

    public function test_registrar_coleta_requires_coleta_role(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = $this->itemLiberadoParaColeta();

        $response = $this->actingAs($conferente)->patch(route('coleta.registrar', $req), [
            'coletado_por' => 'Sergio',
            'data_coleta'  => '2026-09-20',
        ]);

        $response->assertForbidden();
    }
}
