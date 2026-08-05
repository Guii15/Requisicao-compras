<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConferenciaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('conferencia.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('conferencia.index'));

        $response->assertForbidden();
    }

    public function test_conferente_can_access_index(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_entrada_role_can_access_index(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_entrada_role_cannot_conferir(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $response = $this->actingAs($entrada)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 1,
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertForbidden();
    }

    public function test_entrada_role_does_not_see_conferir_button(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Item Visivel Para Entrada']);

        $response = $this->actingAs($entrada)->get(route('conferencia.index'));

        $response->assertSee('Item Visivel Para Entrada');
        $response->assertDontSee('Conferir Item');
        $response->assertSee('Aguardando conferência');
    }

    public function test_index_lists_only_approved_requests_without_status_conferencia(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $pending = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Pendente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Ja Conferido']);
        PurchaseRequest::factory()->create(['status' => 'pendente', 'status_conferencia' => null, 'product_name' => 'Produto Nao Aprovado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('Produto Pendente');
        $response->assertDontSee('Produto Ja Conferido');
        $response->assertDontSee('Produto Nao Aprovado');
    }

    public function test_conferir_with_resultado_ok_persists_conferido_ok_and_photo(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
            'quantity' => 10,
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 10,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertRedirect();
        $purchaseRequest = $purchaseRequest->fresh();
        $this->assertSame('conferido_ok', $purchaseRequest->status_conferencia);
        $this->assertSame(10, $purchaseRequest->quantidade_recebida);
        $this->assertSame($conferente->id, $purchaseRequest->conferente_id);
        $this->assertSame('aprovado', $purchaseRequest->status);
        $this->assertCount(1, $purchaseRequest->fotosConferencia);
        Storage::disk('public')->assertExists($purchaseRequest->fotosConferencia->first()->caminho_arquivo);
    }

    public function test_conferir_divergente_com_tipo_entrega_estoque_persists_divergente(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'Faltaram itens na caixa.',
            'acao' => 'salvar',
        ]);

        $this->assertSame('divergente', $purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_divergente_com_tipo_entrega_entrega_direta_e_avancar_mesmo_assim(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
        ]);

        $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'Embalagem avariada, seguindo mesmo assim.',
            'acao' => 'avancar_mesmo_assim',
        ]);

        $this->assertSame('avancado_mesmo_assim', $purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_rejects_avancar_mesmo_assim_when_tipo_entrega_is_estoque(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'Tentativa de burlar a trava.',
            'acao' => 'avancar_mesmo_assim',
        ]);

        $response->assertForbidden();
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_rejects_avancar_mesmo_assim_when_resultado_is_ok(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'avancar_mesmo_assim',
        ]);

        $response->assertForbidden();
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_requires_observacao_when_divergente(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'acao' => 'salvar',
        ]);

        $response->assertSessionHasErrors('observacao_conferencia');
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_requires_foto(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertSessionHasErrors('foto');
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_rejects_already_conferred_request(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertStatus(409);
        $this->assertSame('conferido_ok', $purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_rejects_non_aprovado_request(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'pendente',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertStatus(409);
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_index_default_still_shows_only_aguardando(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Aguardando']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Conferido']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('Produto Aguardando');
        $response->assertDontSee('Produto Conferido');
    }

    public function test_index_aba_conferidos_shows_all_three_conferred_statuses(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Aguardando']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Produto Avancado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertDontSee('Produto Aguardando');
        $response->assertSee('Produto OK');
        $response->assertSee('Produto Divergente');
        $response->assertSee('Produto Avancado');
    }

    public function test_index_conferidos_resultado_ok_shows_only_conferido_ok(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Produto Avancado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos', 'resultado' => 'ok']));

        $response->assertSee('Produto OK');
        $response->assertDontSee('Produto Divergente');
        $response->assertDontSee('Produto Avancado');
    }

    public function test_index_conferidos_resultado_divergente_shows_divergente_and_avancado(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Produto Avancado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos', 'resultado' => 'divergente']));

        $response->assertDontSee('Produto OK');
        $response->assertSee('Produto Divergente');
        $response->assertSee('Produto Avancado');
    }

    public function test_index_conferidos_ignores_unknown_resultado_value(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos', 'resultado' => 'lixo']));

        $response->assertSee('Produto OK');
        $response->assertSee('Produto Divergente');
    }

    public function test_index_ignores_unknown_aba_value_and_falls_back_to_aguardando(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Aguardando']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'lixo']));

        $response->assertSee('Produto Aguardando');
    }

    public function test_index_conferidos_shows_correct_badge_for_each_status(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Produto Avancado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('>OK<', false);
        $response->assertSee('>Divergente<', false);
        $response->assertSee('Avançado Mesmo Assim');
    }

    public function test_index_conferidos_has_no_conferir_button(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertDontSee('Conferir', false);
    }

    public function test_foto_input_has_capture_attribute(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('capture="environment"', false);
    }

    public function test_mobile_cards_block_present_with_correct_toggle_classes(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Mobile']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('conf-desktop-table', false);
        $response->assertSee('conf-mobile-cards', false);
        $response->assertSee('Produto Mobile');
        $response->assertSee('@media (max-width: 768px)', false);
    }

    public function test_mobile_card_shows_tipo_entrega_badge_and_data_grid(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
            'requester_name' => 'Vendedor Mobile Teste',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $html = $response->getContent();
        $mobileSection = substr($html, strpos($html, 'conf-mobile-cards'));

        $this->assertStringContainsString('Venda Casada', $mobileSection);
        $this->assertStringContainsString('Vendedor Mobile Teste', $mobileSection);
    }

    public function test_mobile_cards_show_result_badge_on_conferidos_tab(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Conferido Mobile']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $html = $response->getContent();
        $mobileSection = substr($html, strpos($html, 'conf-mobile-cards'));

        $this->assertStringContainsString('Produto Conferido Mobile', $mobileSection);
        $this->assertStringContainsString('>OK<', $mobileSection);
    }

    public function test_mobile_modal_has_unique_ids_not_colliding_with_desktop_modal(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('modal-conferir-m-' . $req->id, false);
        $response->assertSee('form-conferir-m-' . $req->id, false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'id="modal-conferir-' . $req->id . '"'));
        $this->assertSame(1, substr_count($html, 'id="modal-conferir-m-' . $req->id . '"'));
    }

    public function test_mobile_modal_not_rendered_on_conferidos_tab(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertDontSee('modal-conferir-m-' . $req->id, false);
    }

    public function test_mobile_modal_avancar_button_only_for_entrega_direta(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $dropship = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
        $estoque = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('<button type="submit" id="btn-avancar-m-' . $dropship->id . '"', false);
        $response->assertDontSee('id="btn-avancar-m-' . $estoque->id . '"', false);
    }

    public function test_search_filters_by_product_name(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Bateria G7 Plus']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Carregador Turbo']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['q' => 'bateria']));

        $response->assertSee('Bateria G7 Plus');
        $response->assertDontSee('Carregador Turbo');
    }

    public function test_search_filters_by_requester_name(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto A', 'requester_name' => 'Guilherme Souza']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto B', 'requester_name' => 'Maria Silva']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['q' => 'guilherme']));

        $response->assertSee('Produto A');
        $response->assertDontSee('Produto B');
    }

    public function test_search_filters_by_supplier(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto A', 'supplier' => 'Bomvink']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto B', 'supplier' => 'GPJ']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['q' => 'bomvink']));

        $response->assertSee('Produto A');
        $response->assertDontSee('Produto B');
    }

    public function test_search_combined_with_aba_conferidos_and_resultado(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Bateria OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Bateria Divergente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos', 'resultado' => 'ok', 'q' => 'bateria']));

        $response->assertSee('Bateria OK');
        $response->assertDontSee('Bateria Divergente');
    }

    public function test_index_conferidos_shows_conferente_name(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente', 'name' => 'Fulano Conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'conferente_id' => $conferente->id,
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('Fulano Conferente');
    }

    public function test_index_aguardando_does_not_show_conferido_por_column(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertDontSee('Conferido por');
    }

    public function test_divergencia_warning_and_auto_select_script_present_for_aguardando_item(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'quantity' => 100]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('id="aviso-divergencia-' . $req->id . '"', false);
        $response->assertSee('id="aviso-divergencia-m-' . $req->id . '"', false);
        $response->assertSee('function verificaDivergencia' . $req->id . '(valor)', false);
        $response->assertSee('function verificaDivergenciaMobile' . $req->id . '(valor)', false);
        $response->assertSee('pedido: 100', false);
    }

    public function test_index_conferidos_shows_cancelado_badge_not_avancado(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado Conferencia',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('>Cancelado<', false);
        $response->assertDontSee('Avançado Mesmo Assim');
    }

    public function test_index_conferidos_cancelado_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado Dois Layouts',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '>Cancelado<'));
    }

    public function test_index_conferidos_still_shows_avancado_mesmo_assim_correctly(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Produto Ainda Avancado',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('Avançado Mesmo Assim');
        $response->assertDontSee('>Cancelado<', false);
    }

    public function test_index_conferidos_resultado_divergente_includes_cancelado(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado No Subfiltro',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos', 'resultado' => 'divergente']));

        $response->assertSee('Produto Cancelado No Subfiltro');
    }
}
