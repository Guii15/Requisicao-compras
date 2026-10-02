<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O comprador corrige a quantidade realmente comprada em "Dados da compra" (ex.: pediram 50, comprou 100).
 * A Conferência lê essa mesma quantidade, então passa a ver o número certo.
 */
class QuantidadeDadosCompraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function dados(array $extra = []): array
    {
        return array_merge([
            'data_compra' => '2026-09-20', 'preco_unitario' => '10,00', 'supplier' => 'Kabum',
        ], $extra);
    }

    private function salvar(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->admin)->patch(route('admin.compras.update', $item), $this->dados($extra));
    }

    public function test_admin_corrige_a_quantidade_comprada(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        $this->salvar($item, ['quantity' => '100'])->assertSessionHasNoErrors();

        $this->assertSame(100, $item->fresh()->quantity);
    }

    public function test_conferencia_passa_a_ver_a_quantidade_corrigida(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => null, 'product_name' => 'Cabo Teste']);
        $this->salvar($item, ['quantity' => '100']);

        $html = $this->actingAs(User::factory()->create(['role' => 'conferente']))->get(route('conferencia.index'))->getContent();

        $this->assertStringContainsString('Cabo Teste', $html);
        $this->assertStringContainsString('value="100" min="0"', $html);   // quantidade recebida já vem com 100
        $this->assertStringNotContainsString('value="50" min="0"', $html);
    }

    public function test_sem_o_campo_a_quantidade_nao_muda(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        $this->salvar($item)->assertSessionHasNoErrors();

        $this->assertSame(50, $item->fresh()->quantity);
    }

    public function test_quantidade_precisa_ser_inteira_e_maior_que_zero(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        foreach (['0', '-3', 'abc', '2,5', '1000001'] as $ruim) {
            $this->salvar($item, ['quantity' => $ruim])->assertSessionHasErrors('quantity');
        }

        $this->assertSame(50, $item->fresh()->quantity);
    }

    public function test_item_ja_conferido_nao_aceita_mudar_a_quantidade(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 50]);

        $this->salvar($item, ['quantity' => '100'])->assertSessionHasErrors('quantity');

        $this->assertSame(50, $item->fresh()->quantity);
        $this->assertNull($item->fresh()->data_compra); // nada foi salvo
    }

    public function test_item_ja_conferido_aceita_salvar_com_a_mesma_quantidade(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 50]);

        $this->salvar($item, ['quantity' => '50'])->assertSessionHasNoErrors();

        $this->assertSame('2026-09-20', $item->fresh()->data_compra->format('Y-m-d'));
    }

    public function test_item_de_recebimento_parcial_nao_aceita_mudar_a_quantidade(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 20, 'quantidade_original' => 100, 'restante_de_id' => PurchaseRequest::factory()->aprovado()->create()->id]);

        $this->salvar($item, ['quantity' => '30'])->assertSessionHasErrors('quantity');

        $this->assertSame(20, $item->fresh()->quantity);
    }

    public function test_formulario_mostra_o_campo_com_a_quantidade_atual(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50]);

        $this->actingAs($this->admin)->get(route('admin.compras.edit', $item))
            ->assertOk()
            ->assertSee('name="quantity"', false)
            ->assertSee('value="50"', false);
    }

    public function test_formulario_de_item_conferido_mostra_o_campo_travado(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['quantity' => 50, 'status_conferencia' => 'conferido_ok', 'quantidade_recebida' => 50]);

        $this->actingAs($this->admin)->get(route('admin.compras.edit', $item))
            ->assertSee('name="quantity"', false)
            ->assertSee('readonly', false)
            ->assertSee('já foi conferido');
    }
}
