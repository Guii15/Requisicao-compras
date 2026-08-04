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
}
