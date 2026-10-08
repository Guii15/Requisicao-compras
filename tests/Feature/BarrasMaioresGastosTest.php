<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Um valor gigante (ex.: item de teste de R$ 40 milhões) não pode fazer as outras barras de
 * "Maiores gastos" sumirem: toda barra com gasto tem pelo menos 3% de largura.
 */
class BarrasMaioresGastosTest extends TestCase
{
    use RefreshDatabase;

    private function compra(string $vendedor, string $fornecedor, float $valor): void
    {
        PurchaseRequest::factory()->aprovado()->create([
            'requester_name' => $vendedor,
            'supplier'       => $fornecedor,
            'valor'          => $valor,
        ]);
    }

    /** Larguras (em %) das barras verdes/azuis dos rankings, na ordem em que aparecem. */
    private function larguras(string $html): array
    {
        preg_match_all('/<div style="height:100%; width:(\d+)%; background:#(?:05018D|059669|2563eb); border-radius:2px;">/', $html, $m);

        return array_map('intval', $m[1]);
    }

    public function test_vendedor_ve_as_barras_pequenas_com_o_minimo_visivel(): void
    {
        $this->compra('Gigante', 'Forn A', 40000000);
        $this->compra('Pequeno', 'Forn B', 225000);

        $html = $this->actingAs(User::factory()->create())->get(route('requests.index'))->assertOk()->getContent();
        $larguras = $this->larguras($html);

        $this->assertContains(100, $larguras);
        $this->assertNotContains(0, $larguras);
        $this->assertNotContains(1, $larguras);
        $this->assertGreaterThanOrEqual(2, count(array_filter($larguras, fn ($l) => $l === 3)), 'vendedor e fornecedor pequenos com 3%');
    }

    public function test_admin_tambem_usa_o_minimo_visivel_nos_rankings(): void
    {
        $this->compra('Gigante', 'Forn A', 40000000);
        $this->compra('Pequeno', 'Forn B', 225000);
        PurchaseRequest::factory()->create(); // pendente: faz o painel do admin listar o grupo

        $html = $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.index'))->assertOk()->getContent();
        $larguras = $this->larguras($html);

        $this->assertNotContains(0, $larguras);
        $this->assertNotContains(1, $larguras);
        // uma marcação só para PC e celular: o carrossel de gráficos do celular saiu, então cada ranking aparece uma vez
        $this->assertGreaterThanOrEqual(2, count(array_filter($larguras, fn ($l) => $l === 3)), 'vendedor e fornecedor pequenos com 3%');
        $this->assertStringNotContainsString('adm-charts-carousel', $html);
    }

    public function test_sem_gasto_nao_desenha_barra_nenhuma(): void
    {
        $html = $this->actingAs(User::factory()->create())->get(route('requests.index'))->assertOk()->getContent();

        $this->assertSame([], $this->larguras($html));
    }
}
