<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendenciaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('pendencias.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('pendencias.index'));

        $response->assertForbidden();
    }

    public function test_conferente_is_forbidden(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('pendencias.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_divergente_estoque_aprovado(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'estoque',
            'product_name' => 'Pendencia Real',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque',
            'product_name' => 'Ainda Aguardando',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'tipo_entrega' => 'estoque',
            'product_name' => 'Ja Conferido OK',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'tipo_entrega' => 'estoque',
            'product_name' => 'Ja Avancado',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'entrega_direta',
            'product_name' => 'Divergente Mas Dropship',
        ]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertSee('Pendencia Real');
        $response->assertDontSee('Ainda Aguardando');
        $response->assertDontSee('Ja Conferido OK');
        $response->assertDontSee('Ja Avancado');
        $response->assertDontSee('Divergente Mas Dropship');
    }

    public function test_index_shows_conferente_name_and_observacao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conferente = User::factory()->create(['role' => 'conferente', 'name' => 'Fulano Conferente']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'estoque',
            'conferente_id' => $conferente->id,
            'observacao_conferencia' => 'Chegou quebrado, faltam 2 unidades',
            'quantity' => 10,
            'quantidade_recebida' => 8,
        ]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertSee('Fulano Conferente');
        $response->assertSee('Chegou quebrado, faltam 2 unidades');
        $response->assertSee('10');
        $response->assertSee('8');
    }

    public function test_index_shows_empty_message_when_no_pendencias(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertSee('Nenhuma pendência no momento.');
    }

    private function pendenciaAprovadaEstoque(array $overrides = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'tipo_entrega' => 'estoque',
            'quantity' => 10,
            'quantidade_recebida' => 8,
        ], $overrides));
    }

    public function test_resolver_aceitar_sets_avancado_mesmo_assim(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertRedirect(route('pendencias.index'));
        $this->assertSame('avancado_mesmo_assim', $req->fresh()->status_conferencia);
    }

    public function test_resolver_aceitar_reinicia_relogio_de_entrada(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conferenciaAntiga = now()->subDays(10);
        $req = $this->pendenciaAprovadaEstoque(['conferencia_concluida_em' => $conferenciaAntiga]);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $conferenciaAtualizada = $req->fresh()->conferencia_concluida_em;
        $this->assertFalse($conferenciaAtualizada->equalTo($conferenciaAntiga));
        $this->assertTrue($conferenciaAtualizada->greaterThan($conferenciaAntiga));
        $this->assertTrue($conferenciaAtualizada->diffInSeconds(now()) < 5);
    }

    public function test_resolver_cancelar_nao_altera_conferencia_concluida_em(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conferenciaAntiga = now()->subDays(10);
        $req = $this->pendenciaAprovadaEstoque(['conferencia_concluida_em' => $conferenciaAntiga]);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao'    => 'cancelar',
            'observacao' => 'Fornecedor confirmou que não vai reenviar.',
        ]);

        $this->assertSame(
            $conferenciaAntiga->format('Y-m-d H:i:s'),
            $req->fresh()->conferencia_concluida_em->format('Y-m-d H:i:s')
        );
    }

    public function test_resolver_cancelar_sets_cancelado(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'cancelar',
            'observacao' => 'Fornecedor confirmou que não vai reenviar.',
        ]);

        $response->assertRedirect(route('pendencias.index'));
        $this->assertSame('cancelado', $req->fresh()->status_conferencia);
    }

    public function test_resolver_cancelar_requires_observacao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'cancelar',
        ]);

        $response->assertSessionHasErrors('observacao');
        $this->assertSame('divergente', $req->fresh()->status_conferencia);
    }

    public function test_resolver_aceitar_does_not_require_observacao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_resolver_appends_observacao_to_existing_admin_note(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque(['admin_note' => 'Aprovado com urgência.']);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'cancelar',
            'observacao' => 'Fornecedor não tem mais o produto.',
        ]);

        $notaFinal = $req->fresh()->admin_note;
        $this->assertStringContainsString('Aprovado com urgência.', $notaFinal);
        $this->assertStringContainsString('Fornecedor não tem mais o produto.', $notaFinal);
    }

    public function test_resolver_rejects_already_resolved_pendencia(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque(['status_conferencia' => 'cancelado']);

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertStatus(409);
    }

    public function test_resolver_rejects_non_estoque_tipo_entrega(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque(['tipo_entrega' => 'entrega_direta']);

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertStatus(409);
    }

    public function test_resolver_requires_admin(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($conferente)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_note_grown_past_500_by_pendencia_resolution_can_still_be_saved_on_admin_update(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque([
            'admin_note' => str_repeat('a', 300),
        ]);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao'    => 'cancelar',
            'observacao' => str_repeat('b', 400),
        ]);

        $notaFinal = $req->fresh()->admin_note;
        $this->assertGreaterThan(500, strlen($notaFinal));

        $response = $this->actingAs($admin)->patch(route('admin.requests.update', $req), [
            'status'     => 'aprovado',
            'admin_note' => $notaFinal,
            'supplier'   => 'Novo Fornecedor',
        ]);

        $response->assertSessionDoesntHaveErrors('admin_note');
        $this->assertSame('Novo Fornecedor', $req->fresh()->supplier);
    }
}
