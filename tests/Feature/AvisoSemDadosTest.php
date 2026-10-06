<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Compras Feitas: a partir de 02/10/2026, toda requisição aprovada aparece ali na hora, mesmo sem data e preço da compra.
 * As aprovadas antes dessa data só aparecem quando já têm os dados (as sem dados ficam em Compras, com um aviso).
 */
class AvisoSemDadosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Carbon::setTestNow('2026-10-05 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function aprovada(string $nome, ?string $aprovadaEm, array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->aprovado()->create(array_merge([
            'product_name' => $nome, 'approved_at' => $aprovadaEm, 'data_compra' => null, 'preco_unitario' => null,
        ], $attrs));
    }

    private function feitas(array $query = []): string
    {
        return $this->actingAs($this->admin)->get(route('admin.compras.feitas', $query))->assertOk()->getContent();
    }

    // ---------- aparece na hora ----------

    public function test_aprovada_hoje_sem_dados_ja_aparece_em_compras_feitas(): void
    {
        $this->aprovada('Item Aprovado Hoje', '2026-10-05 09:00:00');

        $html = $this->feitas();

        $this->assertStringContainsString('Item Aprovado Hoje', $html);
        $this->assertStringContainsString('Registrar compra', $html);
    }

    public function test_aprovar_no_admin_faz_aparecer_na_hora(): void
    {
        $item = PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Item Recem Aprovado']);
        $this->assertStringNotContainsString('Item Recem Aprovado', $this->feitas());

        $this->actingAs($this->admin)->patch(route('admin.requests.update', $item), ['status' => 'aprovado', 'empresa_compradora' => 'Binário'])->assertSessionHasNoErrors();

        $this->assertStringContainsString('Item Recem Aprovado', $this->feitas());
    }

    public function test_aprovada_a_partir_da_virada_do_dia_2_entra_e_antes_dela_nao(): void
    {
        $this->aprovada('Entra Primeiro Instante', '2026-10-02 03:00:00');   // 00:00 em São Paulo
        $this->aprovada('Fica De Fora', '2026-10-02 02:59:59');              // 23:59:59 de 01/10 em São Paulo

        $html = $this->feitas();

        $this->assertStringContainsString('Entra Primeiro Instante', $html);
        $this->assertStringNotContainsString('Fica De Fora', $html);
    }

    // ---------- o que já era assim continua ----------

    public function test_aprovada_antes_do_corte_com_dados_continua_aparecendo(): void
    {
        $this->aprovada('Antiga Com Dados', '2026-09-20 10:00:00', ['data_compra' => '2026-09-21', 'preco_unitario' => 10]);

        $this->assertStringContainsString('Antiga Com Dados', $this->feitas());
    }

    public function test_aprovada_antiga_sem_dados_ou_sem_data_de_aprovacao_nao_aparece(): void
    {
        $this->aprovada('Antiga Sem Dados', '2026-09-20 10:00:00');
        $this->aprovada('Sem Data De Aprovacao', null);

        $html = $this->feitas();

        $this->assertStringNotContainsString('Antiga Sem Dados', $html);
        $this->assertStringNotContainsString('Sem Data De Aprovacao', $html);
    }

    public function test_pendente_e_rejeitada_nao_aparecem(): void
    {
        PurchaseRequest::factory()->create(['status' => 'pendente', 'product_name' => 'Item Pendente', 'approved_at' => null]);
        PurchaseRequest::factory()->create(['status' => 'rejeitado', 'product_name' => 'Item Rejeitado', 'approved_at' => null]);

        $html = $this->feitas();

        $this->assertStringNotContainsString('Item Pendente', $html);
        $this->assertStringNotContainsString('Item Rejeitado', $html);
    }

    // ---------- como aparece ----------

    public function test_item_sem_dados_tem_botao_registrar_e_o_completo_tem_editar(): void
    {
        $this->aprovada('Sem Dados Hoje', '2026-10-05 09:00:00');
        $this->aprovada('Completo Hoje', '2026-10-05 09:00:00', ['data_compra' => '2026-10-05', 'preco_unitario' => 10]);

        $html = $this->feitas();

        $this->assertSame(2, substr_count($html, 'Registrar compra')); // desktop + celular do item sem dados
        $this->assertStringContainsString('Falta registrar', $html);
    }

    public function test_grupo_so_com_itens_sem_dados_mostra_o_selo_sem_dados_e_nao_parcial(): void
    {
        $this->aprovada('Sem Dados Hoje', '2026-10-05 09:00:00');

        $html = $this->feitas();

        $this->assertStringContainsString('Sem dados', $html);
        $this->assertStringNotContainsString('>Parcial<', $html);
    }

    // ---------- aviso das antigas ----------

    public function test_aviso_conta_so_as_antigas_sem_dados_que_nao_aparecem_na_lista(): void
    {
        $this->aprovada('Antiga 1', '2026-09-20 10:00:00');
        $this->aprovada('Antiga 2', null);
        $this->aprovada('Nova Sem Dados', '2026-10-05 09:00:00');

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))
            ->assertSee('2 compras aprovadas antes de 02/10/2026 ainda não têm')
            ->assertSee(route('admin.compras.index', ['situacao' => 'sem_dados']), false);
    }

    public function test_aviso_no_singular(): void
    {
        $this->aprovada('Antiga 1', '2026-09-20 10:00:00');

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))->assertSee('1 compra aprovada antes de 02/10/2026 ainda não tem');
    }

    public function test_sem_antigas_pendentes_nao_mostra_o_aviso(): void
    {
        $this->aprovada('Nova Sem Dados', '2026-10-05 09:00:00');
        $this->aprovada('Antiga Completa', '2026-09-20 10:00:00', ['data_compra' => '2026-09-21', 'preco_unitario' => 10]);

        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))->assertDontSee('ainda não tem')->assertDontSee('ainda não têm');
    }

    public function test_o_atalho_do_aviso_lista_so_as_que_faltam_dados(): void
    {
        $this->aprovada('Item Sem Dados', '2026-09-20 10:00:00');
        $this->aprovada('Item Completo', '2026-09-20 10:00:00', ['data_compra' => '2026-09-21', 'preco_unitario' => 10]);

        $this->actingAs($this->admin)->get(route('admin.compras.index', ['situacao' => 'sem_dados']))
            ->assertSee('Item Sem Dados')->assertSee('Registrar compra')->assertDontSee('Item Completo');
    }
}
