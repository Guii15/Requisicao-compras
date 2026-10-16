<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** O vendedor acrescenta itens numa requisição pendente pela própria tela de Editar. */
class AcrescentarItemNaEdicaoTest extends TestCase
{
    use RefreshDatabase;

    private function payload(PurchaseRequest $pr, array $extra = []): array
    {
        return array_merge([
            'requester_name' => $pr->requester_name,
            'supplier'       => $pr->supplier,
            'urgency'        => $pr->urgency,
            'reason'         => $pr->reason,
            'justification'  => $pr->justification,
            'tipo_entrega'   => 'estoque',
            'product_name'   => $pr->product_name,
            'product_code'   => $pr->product_code,
            'quantity'       => $pr->quantity,
        ], $extra);
    }

    private function requisicaoPendente(User $user, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'user_id'        => $user->id,
            'status'         => 'pendente',
            'tipo_entrega'   => 'estoque',
            'requester_name' => 'Yhan',
            'urgency'        => 'alta',
            'reason'         => 'Reposição',
            'justification'  => 'Filial 31',
            'supplier'       => 'Fornecedor X',
        ], $attrs));
    }

    public function test_tela_de_editar_mostra_a_opcao_de_acrescentar_item(): void
    {
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);

        $this->withoutVite();

        $this->actingAs($user)->get(route('requests.edit', $pr))
            ->assertOk()
            ->assertSee('Adicionar item');
    }

    public function test_item_acrescentado_entra_na_mesma_requisicao_com_os_dados_do_pedido(): void
    {
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);

        $this->actingAs($user)->patch(route('requests.update', $pr), $this->payload($pr, [
            'novos_itens' => [
                ['product_name' => 'Cabo HDMI 2m', 'product_code' => 'HD2', 'quantity' => 3],
            ],
        ]))->assertRedirect(route('requests.index'));

        $novo = PurchaseRequest::where('product_name', 'Cabo HDMI 2m')->first();
        $this->assertNotNull($novo);
        $this->assertSame($pr->grupo_id, $novo->grupo_id);
        $this->assertSame($user->id, $novo->user_id);
        $this->assertSame('pendente', $novo->status);
        $this->assertSame('HD2', $novo->product_code);
        $this->assertSame(3, (int) $novo->quantity);
        $this->assertSame('Yhan', $novo->requester_name);
        $this->assertSame('alta', $novo->urgency);
        $this->assertSame('Reposição', $novo->reason);
        $this->assertSame('Filial 31', $novo->justification);
        $this->assertSame('estoque', $novo->tipo_entrega);
        $this->assertSame('Fornecedor X', $novo->supplier);
        $this->assertSame('requisicao', $novo->tipo_registro);
        $this->assertSame(2, PurchaseRequest::where('grupo_id', $pr->grupo_id)->count());
    }

    public function test_acrescenta_varios_itens_e_ignora_linha_vazia(): void
    {
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);

        $this->actingAs($user)->patch(route('requests.update', $pr), $this->payload($pr, [
            'novos_itens' => [
                ['product_name' => 'Item 1', 'quantity' => 1],
                ['product_name' => '', 'quantity' => ''],
                ['product_name' => 'Item 2', 'quantity' => 5],
            ],
        ]));

        $this->assertSame(3, PurchaseRequest::where('grupo_id', $pr->grupo_id)->count());
    }

    public function test_salvar_sem_item_novo_nao_cria_nada(): void
    {
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);

        $this->actingAs($user)->patch(route('requests.update', $pr), $this->payload($pr));

        $this->assertSame(1, PurchaseRequest::where('grupo_id', $pr->grupo_id)->count());
    }

    public function test_item_novo_com_nome_mas_sem_quantidade_da_erro_e_nao_salva(): void
    {
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);

        $this->actingAs($user)->patch(route('requests.update', $pr), $this->payload($pr, [
            'novos_itens' => [['product_name' => 'Sem quantidade', 'quantity' => '']],
        ]))->assertSessionHasErrors('novos_itens.0.quantity');

        $this->assertSame(1, PurchaseRequest::where('grupo_id', $pr->grupo_id)->count());
    }

    public function test_item_novo_pode_ter_anexo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);

        $this->actingAs($user)->patch(route('requests.update', $pr), $this->payload($pr, [
            'novos_itens' => [[
                'product_name' => 'Com anexo',
                'quantity' => 1,
                'anexo' => UploadedFile::fake()->create('orcamento.pdf', 100, 'application/pdf'),
            ]],
        ]));

        $novo = PurchaseRequest::where('product_name', 'Com anexo')->first();
        $this->assertSame('orcamento.pdf', $novo->anexo_nome);
        Storage::disk('local')->assertExists($novo->anexo_path);
    }

    public function test_nao_acrescenta_se_algum_item_da_requisicao_ja_foi_aprovado(): void
    {
        $user = User::factory()->create();
        $pr = $this->requisicaoPendente($user);
        $this->requisicaoPendente($user, ['grupo_id' => $pr->grupo_id, 'status' => 'aprovado']);

        $this->actingAs($user)->patch(route('requests.update', $pr), $this->payload($pr, [
            'novos_itens' => [['product_name' => 'Tarde demais', 'quantity' => 1]],
        ]))->assertSessionHasErrors('novos_itens');

        $this->assertNull(PurchaseRequest::where('product_name', 'Tarde demais')->first());
    }

    public function test_outro_vendedor_nao_acrescenta_item(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $pr = $this->requisicaoPendente($dono);

        $this->actingAs($outro)->patch(route('requests.update', $pr), $this->payload($pr, [
            'novos_itens' => [['product_name' => 'Intruso', 'quantity' => 1]],
        ]))->assertForbidden();

        $this->assertNull(PurchaseRequest::where('product_name', 'Intruso')->first());
    }
}
