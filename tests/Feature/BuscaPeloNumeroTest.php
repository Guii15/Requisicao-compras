<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Digitar o Nº da requisição (ex.: 4505 ou #4505) no campo de busca acha a requisição inteira. */
class BuscaPeloNumeroTest extends TestCase
{
    use RefreshDatabase;

    /** Cria uma requisição de 2 itens e uma outra qualquer; devolve [primeiro item, segundo item]. */
    private function requisicaoComDoisItens(array $campos = []): array
    {
        $grupo = (string) Str::uuid();
        $primeiro = PurchaseRequest::factory()->create($campos + ['grupo_id' => $grupo, 'product_name' => 'Teclado Gamer']);
        $segundo = PurchaseRequest::factory()->create($campos + ['grupo_id' => $grupo, 'product_name' => 'Mouse sem fio']);
        PurchaseRequest::factory()->create($campos + ['product_name' => 'Monitor grande']);

        return [$primeiro, $segundo];
    }

    private function idsDosGrupos($grupos): array
    {
        return $grupos->map(fn ($itens) => $itens->pluck('id')->sort()->values()->all())->values()->all();
    }

    public function test_admin_acha_requisicao_pelo_numero_no_campo_produto(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$primeiro, $segundo] = $this->requisicaoComDoisItens();

        foreach ([(string) $primeiro->id, '#' . $primeiro->id, ' ' . $primeiro->id . ' '] as $busca) {
            $grupos = $this->actingAs($admin)->get(route('admin.index', ['product_name' => $busca]))->original->getData()['requests'];

            $this->assertSame([[$primeiro->id, $segundo->id]], $this->idsDosGrupos($grupos), "busca: '{$busca}'");
        }
    }

    public function test_numero_de_um_item_do_meio_tambem_acha_a_requisicao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$primeiro, $segundo] = $this->requisicaoComDoisItens();

        $grupos = $this->actingAs($admin)->get(route('admin.index', ['product_name' => $segundo->id]))->original->getData()['requests'];

        $this->assertSame([[$primeiro->id, $segundo->id]], $this->idsDosGrupos($grupos));
    }

    public function test_busca_por_nome_continua_funcionando(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        PurchaseRequest::factory()->create(['product_name' => 'SSD 4505 GB']);
        PurchaseRequest::factory()->create(['product_name' => 'Monitor grande']);

        $grupos = $this->actingAs($admin)->get(route('admin.index', ['product_name' => 'ssd']))->original->getData()['requests'];

        $this->assertCount(1, $grupos);
        $this->assertSame('SSD 4505 GB', $grupos->first()->first()->product_name);
    }

    public function test_compras_feitas_acha_pelo_numero(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$primeiro, $segundo] = $this->requisicaoComDoisItens(['status' => 'aprovado', 'data_compra' => now(), 'preco_unitario' => 10]);

        $grupos = $this->actingAs($admin)->get(route('admin.compras.feitas', ['produto' => '#' . $primeiro->id]))->original->getData()['requests'];

        $this->assertSame([[$primeiro->id, $segundo->id]], $this->idsDosGrupos($grupos));
    }

    public function test_conferencia_acha_pelo_numero(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        [$primeiro, $segundo] = $this->requisicaoComDoisItens(['status' => 'aprovado', 'status_conferencia' => null]);

        $grupos = $this->actingAs($conferente)->get(route('conferencia.index', ['q' => $primeiro->id]))->original->getData()['requests'];

        $this->assertSame([[$primeiro->id, $segundo->id]], $this->idsDosGrupos($grupos));
    }

    public function test_entrada_acha_pelo_numero(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        [$primeiro, $segundo] = $this->requisicaoComDoisItens(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);

        $grupos = $this->actingAs($entrada)->get(route('entrada.index', ['q' => $primeiro->id]))->original->getData()['requests'];

        $this->assertSame([[$primeiro->id, $segundo->id]], $this->idsDosGrupos($grupos));
    }

    public function test_vendedor_acha_a_propria_requisicao_pelo_numero_mas_nao_a_de_outro(): void
    {
        $vendedor = User::factory()->create();
        [$primeiro, $segundo] = $this->requisicaoComDoisItens(['user_id' => $vendedor->id]);
        $deOutro = PurchaseRequest::factory()->create();

        $grupos = $this->actingAs($vendedor)->get(route('requests.index', ['product_name' => $primeiro->id]))->original->getData()['requests'];
        $this->assertSame([[$primeiro->id, $segundo->id]], $this->idsDosGrupos($grupos));

        $grupos = $this->actingAs($vendedor)->get(route('requests.index', ['product_name' => $deOutro->id]))->original->getData()['requests'];
        $this->assertCount(0, $grupos);
    }
}
