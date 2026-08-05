<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricasControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('metricas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_plain_vendedor_can_access(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_conferente_can_access(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_entrada_can_access(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_admin_can_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_tempo_medio_conferencia_calculado_corretamente(): void
    {
        $user = User::factory()->create();
        $base = now()->subDays(5);

        PurchaseRequest::factory()->create([
            'aprovado_em' => $base,
            'conferencia_concluida_em' => $base->copy()->addHours(2),
        ]);
        PurchaseRequest::factory()->create([
            'aprovado_em' => $base,
            'conferencia_concluida_em' => $base->copy()->addHours(4),
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('3h');
    }

    public function test_mostra_sem_dados_suficientes_quando_nao_ha_itens(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('Sem dados suficientes');
    }

    public function test_item_aguardando_conferencia_ha_mais_de_24h_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'aprovado_em' => now()->subHours(30),
            'product_name' => 'Produto Estourado Conferencia',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('Produto Estourado Conferencia');
        $response->assertSee('Aguardando conferência');
    }

    public function test_item_aguardando_entrada_ha_mais_de_24h_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'conferencia_concluida_em' => now()->subHours(30),
            'entrada_concluida_em' => null,
            'product_name' => 'Produto Estourado Entrada',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('Produto Estourado Entrada');
        $response->assertSee('Aguardando entrada');
    }

    public function test_item_aprovado_ha_menos_de_24h_nao_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'aprovado_em' => now()->subHours(2),
            'product_name' => 'Produto Recente Nao Estourado',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertDontSee('Produto Recente Nao Estourado');
    }

    public function test_item_divergente_nao_aparece_estourado_mesmo_apos_24h(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'aprovado_em' => now()->subHours(48),
            'conferencia_concluida_em' => now()->subHours(40),
            'product_name' => 'Produto Divergente Antigo',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertDontSee('Produto Divergente Antigo');
    }

    public function test_duracao_negativa_por_reaprovacao_nao_distorce_media(): void
    {
        $user = User::factory()->create();
        $base = now()->subDays(5);

        // Item normal: aprovado -> conferido 2h depois.
        PurchaseRequest::factory()->create([
            'aprovado_em' => $base,
            'conferencia_concluida_em' => $base->copy()->addHours(2),
        ]);

        // Item "reaprovado": conferencia_concluida_em ficou registrada antes,
        // mas o admin reaprovou depois (aprovado_em > conferencia_concluida_em),
        // gerando duração negativa que não deve entrar na média.
        PurchaseRequest::factory()->create([
            'aprovado_em' => $base->copy()->addHours(10),
            'conferencia_concluida_em' => $base,
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('2h');
        $this->assertDoesNotMatchRegularExpression('/-\d+(\.\d+)?h</', $response->getContent());
    }

    public function test_item_rejeitado_apos_aprovado_nao_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'rejeitado',
            'status_conferencia' => null,
            'aprovado_em' => now()->subHours(48),
            'product_name' => 'Produto Aprovado E Depois Rejeitado',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertDontSee('Produto Aprovado E Depois Rejeitado');
    }
}
