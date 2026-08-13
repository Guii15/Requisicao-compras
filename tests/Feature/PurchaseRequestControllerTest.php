<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurchaseRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'requester_name' => 'Vendedor Teste',
            'supplier' => 'Fornecedor Teste',
            'urgency' => 'media',
            'reason' => 'Reposição',
            'justification' => 'Filial 31',
            'tipo_entrega' => 'estoque',
            'products' => [
                ['product_name' => 'Produto A', 'quantity' => 2],
                ['product_name' => 'Produto B', 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_store_persists_tipo_entrega_on_every_product_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(
            route('requests.store'),
            $this->validStorePayload(['tipo_entrega' => 'entrega_direta'])
        );

        $this->assertDatabaseCount('purchase_requests', 2);
        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto A', 'tipo_entrega' => 'entrega_direta']);
        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto B', 'tipo_entrega' => 'entrega_direta']);
    }

    public function test_store_defaults_to_estoque_when_selected_in_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('requests.store'), $this->validStorePayload());

        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto A', 'tipo_entrega' => 'estoque']);
    }

    public function test_store_rejects_invalid_tipo_entrega(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('requests.store'),
            $this->validStorePayload(['tipo_entrega' => 'algo_invalido'])
        );

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_store_rejects_missing_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $payload = $this->validStorePayload();
        unset($payload['tipo_entrega']);

        $response = $this->actingAs($user)->post(route('requests.store'), $payload);

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_store_assigns_same_grupo_id_to_all_products_in_submission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('requests.store'), $this->validStorePayload());

        $registros = PurchaseRequest::whereIn('product_name', ['Produto A', 'Produto B'])->get();
        $this->assertCount(2, $registros);
        $this->assertNotNull($registros[0]->grupo_id);
        $this->assertSame($registros[0]->grupo_id, $registros[1]->grupo_id);
    }

    public function test_store_assigns_different_grupo_id_to_separate_submissions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('requests.store'), $this->validStorePayload([
            'products' => [['product_name' => 'Produto A', 'quantity' => 1]],
        ]));
        $this->actingAs($user)->post(route('requests.store'), $this->validStorePayload([
            'products' => [['product_name' => 'Produto C', 'quantity' => 1]],
        ]));

        $a = PurchaseRequest::where('product_name', 'Produto A')->firstOrFail();
        $c = PurchaseRequest::where('product_name', 'Produto C')->firstOrFail();
        $this->assertNotSame($a->grupo_id, $c->grupo_id);
    }

    public function test_update_changes_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'tipo_entrega' => 'estoque',
        ]);

        $this->actingAs($user)->patch(route('requests.update', $purchaseRequest), [
            'requester_name' => $purchaseRequest->requester_name,
            'supplier' => $purchaseRequest->supplier,
            'urgency' => $purchaseRequest->urgency,
            'reason' => $purchaseRequest->reason,
            'justification' => $purchaseRequest->justification,
            'tipo_entrega' => 'entrega_direta',
            'product_name' => $purchaseRequest->product_name,
            'product_code' => $purchaseRequest->product_code,
            'quantity' => $purchaseRequest->quantity,
        ]);

        $this->assertSame('entrega_direta', $purchaseRequest->fresh()->tipo_entrega);
    }

    public function test_update_rejects_invalid_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($user)->patch(route('requests.update', $purchaseRequest), [
            'requester_name' => $purchaseRequest->requester_name,
            'supplier' => $purchaseRequest->supplier,
            'urgency' => $purchaseRequest->urgency,
            'reason' => $purchaseRequest->reason,
            'justification' => $purchaseRequest->justification,
            'tipo_entrega' => 'algo_invalido',
            'product_name' => $purchaseRequest->product_name,
            'product_code' => $purchaseRequest->product_code,
            'quantity' => $purchaseRequest->quantity,
        ]);

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertSame('estoque', $purchaseRequest->fresh()->tipo_entrega);
    }

    public function test_index_shows_aguardando_conferencia_for_aprovado_without_status_conferencia(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => null,
            'product_name' => 'Produto Aguardando',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Aguardando conferência');
    }

    public function test_index_shows_conferido_ok_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto OK',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido ✓ OK');
        $response->assertDontSee('Aguardando conferência');
    }

    public function test_index_shows_divergente_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'product_name' => 'Produto Divergente',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido — Divergente');
    }

    public function test_index_shows_avancado_mesmo_assim_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Produto Avancado',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido — Avançado Mesmo Assim');
    }

    public function test_index_pendente_shows_no_conferencia_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'status_conferencia' => null,
            'product_name' => 'Produto Pendente',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Aguardando conferência');
        $response->assertDontSee('Conferido ✓ OK');
    }

    public function test_index_rejeitado_without_conferencia_shows_no_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejeitado',
            'status_conferencia' => null,
            'product_name' => 'Produto Rejeitado',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Aguardando conferência');
        $response->assertDontSee('Conferido ✓ OK');
    }

    public function test_index_rejeitado_with_status_conferencia_still_shows_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejeitado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Rejeitado Conferido',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido ✓ OK');
    }

    public function test_index_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'Conferido ✓ OK'));
    }

    public function test_index_badge_wrapped_in_div_when_product_code_and_url_empty(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => null,
            'product_name' => 'Produto Sem Codigo Url',
            'product_code' => null,
            'product_url' => null,
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        // Verificar que a etiqueta "Aguardando conferência" existe
        $response->assertSee('Aguardando conferência');
        // Verificar que ela está dentro de uma <div> envolvendo a etiqueta
        $this->assertStringContainsString('<div>', $html);
        $this->assertStringContainsString('</div>', $html);
        // Confirmar que há uma <div> dentro do <td> do produto (antes de fechar a tag)
        $this->assertMatchesRegularExpression('/<td[^>]*>(?:.*?)<div>.*?Aguardando conferência.*?<\/div>.*?<\/td>/s', $html);
    }

    public function test_index_shows_cancelado_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Bateria G7 Plus',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Cancelado<', false);
        $response->assertDontSee('Aguardando conferência');
    }

    public function test_index_cancelado_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '>Cancelado<'));
    }

    public function test_index_shows_entrada_realizada_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Bateria G8 Plus',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Entrada Realizada<', false);
        $response->assertDontSee('>Conferido ✓ OK<', false);
    }

    public function test_index_entrada_realizada_takes_priority_over_cancelado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'entrada_concluida_em' => now(),
            'product_name' => 'Caso Extremo Cancelado Com Entrada',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Entrada Realizada<', false);
        $response->assertDontSee('>Cancelado<', false);
    }

    public function test_index_entrada_realizada_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Produto Entrada Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '>Entrada Realizada<'));
    }

    public function test_index_without_entrada_still_shows_conferido_ok(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => null,
            'product_name' => 'Produto Sem Entrada Ainda',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Conferido ✓ OK<', false);
        $response->assertDontSee('>Entrada Realizada<', false);
    }

    public function test_index_shows_ver_foto_link_when_foto_exists(): void
    {
        $user = User::factory()->create();
        $req = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Com Foto',
        ]);
        ConferenciaFoto::create([
            'purchase_request_id' => $req->id,
            'caminho_arquivo' => 'conferencia/produto-com-foto.jpg',
            'nome_original' => 'foto.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Ver foto');
        $response->assertSee(Storage::url('conferencia/produto-com-foto.jpg'), false);
    }

    public function test_index_does_not_show_ver_foto_link_when_no_foto(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => null,
            'product_name' => 'Produto Sem Foto',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Ver foto');
    }

    public function test_index_foto_modal_shows_correct_image_for_each_request(): void
    {
        $user = User::factory()->create();
        $reqA = PurchaseRequest::factory()->create([
            'user_id' => $user->id, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
        ]);
        $reqB = PurchaseRequest::factory()->create([
            'user_id' => $user->id, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
        ]);
        ConferenciaFoto::create(['purchase_request_id' => $reqA->id, 'caminho_arquivo' => 'conferencia/foto-a.jpg', 'nome_original' => 'a.jpg']);
        ConferenciaFoto::create(['purchase_request_id' => $reqB->id, 'caminho_arquivo' => 'conferencia/foto-b.jpg', 'nome_original' => 'b.jpg']);

        $response = $this->actingAs($user)->get(route('requests.index'));
        $html = $response->getContent();

        $posA = strpos($html, "id=\"foto-{$reqA->id}\"");
        $posB = strpos($html, "id=\"foto-{$reqB->id}\"");
        $this->assertNotFalse($posA);
        $this->assertNotFalse($posB);

        [$startA, $endA] = $posA < $posB ? [$posA, $posB] : [$posA, strlen($html)];
        $modalA = substr($html, $startA, $endA - $startA);

        $this->assertStringContainsString('foto-a.jpg', $modalA);
        $this->assertStringNotContainsString('foto-b.jpg', $modalA);
    }

    public function test_index_does_not_n_plus_one_query_fotos_conferencia(): void
    {
        $criarComFoto = function (User $user, int $quantidade) {
            foreach (range(1, $quantidade) as $i) {
                $req = PurchaseRequest::factory()->create([
                    'user_id' => $user->id, 'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
                ]);
                ConferenciaFoto::create([
                    'purchase_request_id' => $req->id,
                    'caminho_arquivo' => "conferencia/foto-{$i}.jpg",
                    'nome_original' => "foto-{$i}.jpg",
                ]);
            }
        };

        $userUm = User::factory()->create();
        $criarComFoto($userUm, 1);
        DB::enableQueryLog();
        $this->actingAs($userUm)->get(route('requests.index'));
        $queryCountUm = count(DB::getQueryLog());
        DB::disableQueryLog();
        DB::flushQueryLog();

        $userCinco = User::factory()->create();
        $criarComFoto($userCinco, 5);
        DB::enableQueryLog();
        $this->actingAs($userCinco)->get(route('requests.index'));
        $queryCountCinco = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame($queryCountUm, $queryCountCinco, 'A quantidade de queries não deveria crescer com o número de linhas (foto por linha = N+1).');
    }
}
