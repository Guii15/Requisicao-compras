<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PushNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quem preenche no Atualizar todos os dados da compra (fornecedor, data, preço unitário e condição)
 * e esquece o status em "Pendente" aprova a requisição sem querer: ela vira "aprovado" sozinha.
 */
class AprovaAoCompletarCompraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function dadosCompletos(array $extra = []): array
    {
        return array_merge([
            'status'             => 'pendente',
            'supplier'           => 'kabum',
            'preco_unitario'     => '10,00',
            'data_compra'        => '2026-11-06',
            'condicao_pagamento' => 'a_vista',
        ], $extra);
    }

    public function test_pendente_com_dados_completos_vira_aprovado(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin())
            ->patch(route('admin.requests.update', $item), $this->dadosCompletos())
            ->assertSessionDoesntHaveErrors();

        $item->refresh();
        $this->assertSame('aprovado', $item->status);
        $this->assertNotNull($item->approved_at);
    }

    public function test_aprovacao_automatica_avisa_conferencia_e_entrada(): void
    {
        $notifier = $this->mock(PushNotifier::class);
        $notifier->shouldReceive('aprovada')->once();

        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin())
            ->patch(route('admin.requests.update', $item), $this->dadosCompletos());
    }

    public function test_aprovacao_automatica_avisa_o_admin_na_mensagem(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin())
            ->patch(route('admin.requests.update', $item), $this->dadosCompletos())
            ->assertSessionHas('success', fn ($msg) => str_contains($msg, 'aprovada automaticamente'));
    }

    /** @dataProvider camposFaltando */
    public function test_faltando_algum_dado_continua_pendente(string $campo): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin())
            ->patch(route('admin.requests.update', $item), $this->dadosCompletos([$campo => '']))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('pendente', $item->refresh()->status);
    }

    public static function camposFaltando(): array
    {
        return [
            'sem fornecedor'      => ['supplier'],
            'sem preço unitário'  => ['preco_unitario'],
            'sem data da compra'  => ['data_compra'],
            'sem condição'        => ['condicao_pagamento'],
        ];
    }

    public function test_rejeitada_nao_vira_aprovada_mesmo_com_dados_completos(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente']);

        $this->actingAs($this->admin())
            ->patch(route('admin.requests.update', $item), $this->dadosCompletos(['status' => 'rejeitado']));

        $this->assertSame('rejeitado', $item->refresh()->status);
    }

    public function test_aprovada_voltada_para_pendente_de_proposito_fica_pendente(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'aprovado', 'approved_at' => now()]);

        $this->actingAs($this->admin())
            ->patch(route('admin.requests.update', $item), $this->dadosCompletos());

        $this->assertSame('pendente', $item->refresh()->status);
    }
}
