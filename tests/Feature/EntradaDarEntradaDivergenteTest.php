<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Entrada pode dar entrada num item divergente (ex.: o admin liberou no grupo interno), desde que
 * escreva uma observação. O item vira "avançado mesmo assim" e sai das Pendências.
 */
class EntradaDarEntradaDivergenteTest extends TestCase
{
    use RefreshDatabase;

    private User $entrada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entrada = User::factory()->create(['role' => 'entrada']);
    }

    private function divergente(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'estoque',
            'quantity' => 10, 'quantidade_recebida' => 8, 'product_name' => 'Cabo Divergente',
            'observacao_conferencia' => 'Chegou 2 a menos', 'requester_name' => 'Maria',
        ], $attrs));
    }

    private function entrar(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->entrada)->patch(route('entrada.darEntrada', $item), array_merge([
            'vendedor_destino' => 'Maria', 'quantidade_entrada' => 8, 'obs_entrada' => 'Admin liberou no grupo interno',
        ], $extra));
    }

    public function test_entrada_da_entrada_no_item_divergente_com_observacao(): void
    {
        $item = $this->divergente();

        $this->entrar($item)->assertSessionHasNoErrors()->assertSessionHas('success');

        $item->refresh();
        $this->assertNotNull($item->entrada_concluida_em);
        $this->assertSame(8, $item->quantidade_entrada);
        $this->assertSame('Maria', $item->vendedor_destino);
        $this->assertSame('Admin liberou no grupo interno', $item->obs_entrada);
        $this->assertSame('avancado_mesmo_assim', $item->status_conferencia);
        $this->assertSame('Chegou 2 a menos', $item->observacao_conferencia); // a divergência continua registrada
    }

    public function test_observacao_e_obrigatoria_para_item_divergente(): void
    {
        $item = $this->divergente();

        $this->entrar($item, ['obs_entrada' => ''])->assertSessionHasErrors('obs_entrada');
        $this->entrar($item, ['obs_entrada' => '   '])->assertSessionHasErrors('obs_entrada');

        $item->refresh();
        $this->assertNull($item->entrada_concluida_em);
        $this->assertSame('divergente', $item->status_conferencia);
    }

    public function test_observacao_continua_opcional_para_item_conferido_ok(): void
    {
        $item = $this->divergente(['status_conferencia' => 'conferido_ok', 'observacao_conferencia' => null]);

        $this->entrar($item, ['obs_entrada' => ''])->assertSessionHasNoErrors();

        $this->assertNotNull($item->fresh()->entrada_concluida_em);
        $this->assertSame('conferido_ok', $item->fresh()->status_conferencia);
    }

    public function test_quantidade_continua_precisando_ser_a_recebida(): void
    {
        $item = $this->divergente();

        $this->entrar($item, ['quantidade_entrada' => 10])->assertSessionHasErrors('quantidade_entrada');

        $this->assertNull($item->fresh()->entrada_concluida_em);
    }

    public function test_observacao_tem_limite_de_tamanho(): void
    {
        $this->entrar($this->divergente(), ['obs_entrada' => str_repeat('a', 501)])->assertSessionHasErrors('obs_entrada');
    }

    public function test_item_cancelado_ou_pendente_continua_sem_poder_dar_entrada(): void
    {
        $cancelado = $this->divergente(['status_conferencia' => 'cancelado']);
        $naoAprovado = $this->divergente(['status' => 'pendente']);
        $semConferir = $this->divergente(['status_conferencia' => null]);

        foreach ([$cancelado, $naoAprovado, $semConferir] as $item) {
            $this->entrar($item)->assertSessionHas('aviso');
            $this->assertNull($item->fresh()->entrada_concluida_em);
        }
    }

    public function test_depois_da_entrada_sai_das_divergencias_e_vai_para_entrada_realizada(): void
    {
        $item = $this->divergente(['product_name' => 'Item Que Entrou']);
        $this->entrar($item);

        $this->actingAs($this->entrada);
        $this->assertStringNotContainsString('Item Que Entrou', $this->get(route('entrada.index', ['aba' => 'divergencias']))->getContent());
        $this->get(route('entrada.index', ['aba' => 'concluidas']))->assertSee('Item Que Entrou')->assertSee('Admin liberou no grupo interno');
    }

    public function test_depois_da_entrada_a_pendencia_deixa_de_existir_para_o_admin(): void
    {
        $item = $this->divergente(['product_name' => 'Item Pendente Resolvido']);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->assertStringContainsString('Item Pendente Resolvido', $this->actingAs($admin)->get(route('pendencias.index'))->getContent());

        $this->entrar($item);

        $this->actingAs($admin)->get(route('pendencias.index'))->assertDontSee('Item Pendente Resolvido');
        $this->actingAs($admin)->patch(route('pendencias.resolver', $item), ['decisao' => 'aceitar'])->assertSessionHas('aviso');
    }

    public function test_segundo_clique_nao_duplica_a_entrada(): void
    {
        $item = $this->divergente();
        $this->entrar($item);

        $this->entrar($item)->assertSessionHas('aviso');
    }

    public function test_aba_mostra_o_botao_e_o_formulario_com_observacao_obrigatoria(): void
    {
        $item = $this->divergente();

        $html = $this->actingAs($this->entrada)->get(route('entrada.index', ['aba' => 'divergencias']))->assertOk()->getContent();

        $this->assertStringContainsString('Dar Entrada', $html);
        $this->assertStringContainsString(route('entrada.darEntrada', $item), $html);
        $this->assertMatchesRegularExpression('/<textarea name="obs_entrada"[^>]*required/', $html);
    }

    public function test_so_quem_acessa_a_entrada_pode(): void
    {
        $item = $this->divergente();
        $dados = ['vendedor_destino' => 'X', 'quantidade_entrada' => 8, 'obs_entrada' => 'x'];

        $this->actingAs(User::factory()->create(['role' => 'conferente']))->patch(route('entrada.darEntrada', $item), $dados)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => null, 'is_admin' => false]))->patch(route('entrada.darEntrada', $item), $dados)->assertForbidden();

        $this->assertNull($item->fresh()->entrada_concluida_em);
    }
}
