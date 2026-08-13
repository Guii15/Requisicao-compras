<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRequestsNovasHistoricoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_novas_shows_pendente_item(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Item Pendente']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Item Pendente');
    }

    public function test_novas_shows_aprovado_aguardando_entrada(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'entrada_concluida_em' => null, 'product_name' => 'Item Aprovado Sem Entrada',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Item Aprovado Sem Entrada');
    }

    public function test_novas_does_not_show_rejeitado(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'rejeitado', 'product_name' => 'Item Rejeitado']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertDontSee('Item Rejeitado');
    }

    public function test_novas_does_not_show_aprovado_com_entrada_concluida(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'entrada_concluida_em' => now(), 'product_name' => 'Item Finalizado',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertDontSee('Item Finalizado');
    }

    public function test_historico_shows_rejeitado(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'rejeitado', 'product_name' => 'Item Rejeitado Historico']);

        $response = $this->actingAs($admin)->get(route('admin.index', ['aba' => 'historico']));

        $response->assertSee('Item Rejeitado Historico');
    }

    public function test_historico_shows_aprovado_com_entrada_concluida(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'entrada_concluida_em' => now(), 'product_name' => 'Item Concluido Historico',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', ['aba' => 'historico']));

        $response->assertSee('Item Concluido Historico');
    }

    public function test_historico_does_not_show_pendente(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Item Ainda Pendente']);

        $response = $this->actingAs($admin)->get(route('admin.index', ['aba' => 'historico']));

        $response->assertDontSee('Item Ainda Pendente');
    }

    public function test_historico_does_not_show_aprovado_sem_entrada(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'entrada_concluida_em' => null, 'product_name' => 'Item Aprovado Aguardando Entrada',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.index', ['aba' => 'historico']));

        $response->assertDontSee('Item Aprovado Aguardando Entrada');
    }

    public function test_group_with_at_least_one_unfinalized_item_shows_in_novas(): void
    {
        $admin = $this->admin();
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'rejeitado', 'product_name' => 'Item Ja Rejeitado No Grupo']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'pendente', 'product_name' => 'Item Ainda Pendente No Grupo']);

        $response = $this->actingAs($admin)->get(route('admin.index'));

        $response->assertSee('Item Ja Rejeitado No Grupo');
        $response->assertSee('Item Ainda Pendente No Grupo');
    }

    public function test_group_fully_finalized_shows_only_in_historico(): void
    {
        $admin = $this->admin();
        $grupoId = (string) Str::uuid();
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'rejeitado', 'product_name' => 'Item A Finalizado']);
        PurchaseRequest::factory()->create(['grupo_id' => $grupoId, 'status' => 'aprovado', 'entrada_concluida_em' => now(), 'product_name' => 'Item B Finalizado']);

        $novas = $this->actingAs($admin)->get(route('admin.index'));
        $historico = $this->actingAs($admin)->get(route('admin.index', ['aba' => 'historico']));

        $novas->assertDontSee('Item A Finalizado');
        $novas->assertDontSee('Item B Finalizado');
        $historico->assertSee('Item A Finalizado');
        $historico->assertSee('Item B Finalizado');
    }

    public function test_filters_still_work_within_aba(): void
    {
        $admin = $this->admin();
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Filtro Alvo']);
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Outro Produto']);

        $response = $this->actingAs($admin)->get(route('admin.index', ['product_name' => 'Filtro Alvo']));

        $response->assertSee('Filtro Alvo');
        $response->assertDontSee('Outro Produto');
    }
}
