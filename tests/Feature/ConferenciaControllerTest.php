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
        $response->assertSee('Divergente');
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
}
