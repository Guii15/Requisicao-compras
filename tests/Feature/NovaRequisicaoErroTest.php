<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovaRequisicaoErroTest extends TestCase
{
    use RefreshDatabase;

    private function dados(array $extra = []): array
    {
        return array_merge([
            'requester_name' => 'Vendedor', 'urgency' => 'alta', 'reason' => 'venda casada', 'tipo_entrega' => 'estoque',
            'justification' => 'obs',
            'products' => [
                ['product_name' => 'Mouse sem fio', 'product_code' => '', 'quantity' => 1],
                ['product_name' => 'Cabo USB', 'product_code' => 'X1', 'quantity' => 4],
            ],
        ], $extra);
    }

    public function test_erro_de_validacao_devolve_os_itens_digitados(): void
    {
        $u = User::factory()->create(['is_admin' => true]);

        $this->actingAs($u)->from(route('requests.create'))
            ->post(route('requests.store'), $this->dados(['justification' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('justification');

        $this->actingAs($u)->get(route('requests.create'))->assertOk();

        $resp = $this->actingAs($u)->followingRedirects()->from(route('requests.create'))
            ->post(route('requests.store'), $this->dados(['justification' => str_repeat('a', 501)]));

        $resp->assertSee('Mouse sem fio')->assertSee('Cabo USB')->assertSee('no máximo 500 caracteres');
        $resp->assertDontSee('must not be greater');
    }

    public function test_uma_requisicao_com_dois_itens_diz_requisicao_criada_e_conta_como_uma(): void
    {
        $u = User::factory()->create(['is_admin' => true]);

        $this->actingAs($u)->post(route('requests.store'), $this->dados())
            ->assertSessionHas('success', 'Requisição criada com sucesso! (2 itens)');

        $this->assertSame(2, PurchaseRequest::count());
        $this->assertSame(1, PurchaseRequest::numRequisicoes());
    }
}
