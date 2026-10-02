<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\RankingPorNome;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * "Maiores gastos": o mesmo nome escrito de formas diferentes (Yhan, YHAN, "Yhan ") aparece uma vez só, com o total somado.
 */
class RankingMaioresGastosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function gasto(string $vendedor, float $valor, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'requester_name' => $vendedor, 'valor' => $valor, 'supplier' => 'Kabum',
        ], $attrs));
    }

    private function rankingVendedores(): Collection
    {
        return $this->actingAs($this->admin)->get(route('admin.index'))->assertOk()->viewData('vendorSpending');
    }

    private function rankingFornecedores(): Collection
    {
        return $this->actingAs($this->admin)->get(route('admin.index'))->assertOk()->viewData('supplierSpending');
    }

    public function test_vendedor_escrito_de_formas_diferentes_vira_uma_linha_so(): void
    {
        $this->gasto('Yhan', 100);
        $this->gasto('YHAN', 200);
        $this->gasto('yhan ', 300);
        $this->gasto('  Yhan', 50);

        $ranking = $this->rankingVendedores();

        $this->assertCount(1, $ranking);
        $this->assertSame(650.0, (float) $ranking->first()->total_gasto);
    }

    public function test_acento_e_espacos_no_meio_tambem_juntam(): void
    {
        $this->gasto('João  Pedro', 100);
        $this->gasto('Joao Pedro', 100);
        $this->gasto('JOÃO PEDRO', 100);

        $ranking = $this->rankingVendedores();

        $this->assertCount(1, $ranking);
        $this->assertSame(300.0, (float) $ranking->first()->total_gasto);
    }

    public function test_nomes_diferentes_nao_sao_juntados(): void
    {
        $this->gasto('Yhan', 100);
        $this->gasto('Yhan Silva', 100);
        $this->gasto('Yago', 100);

        $this->assertCount(3, $this->rankingVendedores());
    }

    public function test_o_nome_mostrado_e_a_grafia_que_mais_gastou_e_bem_formatada(): void
    {
        $this->gasto('YHAN', 100);
        $this->gasto('yhan', 500);

        $this->assertSame('Yhan', $this->rankingVendedores()->first()->requester_name);
    }

    public function test_grafia_ja_bem_escrita_e_mantida(): void
    {
        $this->gasto('Maria da Silva', 500);
        $this->gasto('MARIA DA SILVA', 100);

        $this->assertSame('Maria da Silva', $this->rankingVendedores()->first()->requester_name);
    }

    public function test_ordena_pelo_total_somado_e_limita_a_dez(): void
    {
        foreach (range(1, 12) as $i) {
            $this->gasto('Vendedor ' . chr(64 + $i), $i * 10);
        }
        $this->gasto('vendedor a', 1000);   // soma com "Vendedor A" (10) e passa a ser o primeiro

        $ranking = $this->rankingVendedores();

        $this->assertCount(10, $ranking);
        $this->assertSame('Vendedor A', $ranking->first()->requester_name);
        $this->assertSame(1010.0, (float) $ranking->first()->total_gasto);
        $this->assertSame($ranking->pluck('total_gasto')->map(fn ($v) => (float) $v)->sortDesc()->values()->all(), $ranking->pluck('total_gasto')->map(fn ($v) => (float) $v)->values()->all());
    }

    public function test_so_conta_aprovado_com_valor(): void
    {
        $this->gasto('Yhan', 100);
        $this->gasto('YHAN', 999, ['status' => 'pendente']);
        $this->gasto('yhan', 999, ['valor' => null]);

        $this->assertSame(100.0, (float) $this->rankingVendedores()->first()->total_gasto);
    }

    public function test_fornecedor_com_grafias_diferentes_tambem_vira_uma_linha(): void
    {
        $this->gasto('Isaac', 100, ['supplier' => 'Joyce Informática']);
        $this->gasto('Ian', 200, ['supplier' => 'JOYCE INFORMATICA LTDA']);
        $this->gasto('Osmar', 300, ['supplier' => 'joyce informatica ltda.']);
        $this->gasto('Osmar', 50, ['supplier' => 'Kabum']);

        $ranking = $this->rankingFornecedores();

        $this->assertCount(2, $ranking);
        $this->assertSame(600.0, (float) $ranking->first()->total_gasto);
        $this->assertSame('Kabum', $ranking->last()->supplier);
    }

    public function test_fornecedores_de_nomes_diferentes_nao_sao_juntados(): void
    {
        $this->gasto('Isaac', 100, ['supplier' => 'Joyce']);
        $this->gasto('Isaac', 100, ['supplier' => 'Joyce Informática']);

        $this->assertCount(2, $this->rankingFornecedores());
    }

    public function test_fornecedor_vazio_continua_de_fora(): void
    {
        $this->gasto('Isaac', 100, ['supplier' => null]);
        $this->gasto('Isaac', 100, ['supplier' => '']);

        $this->assertCount(0, $this->rankingFornecedores());
    }

    public function test_painel_do_vendedor_tambem_junta_os_nomes(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);
        $this->gasto('Yhan', 100);
        $this->gasto('YHAN', 200);

        $ranking = $this->actingAs($vendedor)->get(route('requests.index'))->assertOk()->viewData('vendorSpending');

        $this->assertCount(1, $ranking);
        $this->assertSame(300.0, (float) $ranking->first()->total_gasto);
    }

    public function test_helper_devolve_objetos_com_o_nome_do_campo_e_o_total(): void
    {
        $linhas = collect([
            (object) ['requester_name' => 'Ana', 'total_gasto' => 10],
            (object) ['requester_name' => 'ANA', 'total_gasto' => 5],
        ]);

        $r = RankingPorNome::agrupar($linhas, 'requester_name');

        $this->assertCount(1, $r);
        $this->assertSame('Ana', $r->first()->requester_name);
        $this->assertEquals(15, $r->first()->total_gasto);
    }
}
