<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHistoricoComprasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function criarHistorico(array $sobrescreve = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'tipo_registro' => 'compra_historica',
            'status' => 'aprovado',
            'aba_origem' => 'JAN.  FEV.',
            'mes_origem' => 'Jan-Fev',
            'origem_id' => 'JAN._FEV._L' . random_int(1, 999999),
            'data_compra' => '2026-01-26',
            'valor' => 100.0,
        ], $sobrescreve));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.historico-compras'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('admin.historico-compras'))->assertForbidden();
    }

    public function test_mostra_estado_vazio_sem_registros_importados(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertOk();
        $response->assertSee('Nenhum registro no histórico ainda', false);
    }

    public function test_mostra_registro_historico_e_total_geral(): void
    {
        $this->criarHistorico(['product_name' => 'Suporte P/ Gabinete']);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertOk();
        $response->assertSee('Suporte P/ Gabinete', false);
        $response->assertSee('R$ 100,00', false);
    }

    public function test_mostra_requisicoes_do_fluxo_ainda_pendentes_com_rotulo_pendente(): void
    {
        PurchaseRequest::factory()->create(['product_name' => 'Requisição Pendente', 'status' => 'pendente']);
        $this->criarHistorico(['product_name' => 'Item Histórico']);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Item Histórico', false);
        $response->assertSee('Requisição Pendente', false);
        $response->assertSee('Pendente', false);
    }

    public function test_mostra_requisicao_real_ja_finalizada_junto_com_o_historico_da_planilha(): void
    {
        PurchaseRequest::factory()->create([
            'product_name' => 'Requisição Finalizada',
            'status' => 'aprovado',
            'entrada_concluida_em' => now(),
        ]);
        $this->criarHistorico(['product_name' => 'Item Da Planilha']);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Requisição Finalizada', false);
        $response->assertSee('Item Da Planilha', false);
    }

    public function test_mostra_requisicao_real_rejeitada_como_finalizada(): void
    {
        PurchaseRequest::factory()->create([
            'product_name' => 'Requisição Rejeitada',
            'status' => 'rejeitado',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Requisição Rejeitada', false);
        $response->assertSee('Rejeitado', false);
    }

    public function test_mostra_requisicao_aprovada_mas_ainda_sem_entrada(): void
    {
        PurchaseRequest::factory()->create([
            'product_name' => 'Aguardando Entrada',
            'status' => 'aprovado',
            'entrada_concluida_em' => null,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Aguardando Entrada', false);
    }

    public function test_pesquisa_por_produto(): void
    {
        $this->criarHistorico(['product_name' => 'Filtro de Óleo']);
        $this->criarHistorico(['product_name' => 'SSD 240GB']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras', ['produto' => 'Filtro']));

        $response->assertSee('Filtro de Óleo', false);
        $response->assertDontSee('SSD 240GB', false);
    }

    public function test_pesquisa_por_vendedor(): void
    {
        $this->criarHistorico(['product_name' => 'Item Do Yhan', 'requester_name' => 'Yhan']);
        $this->criarHistorico(['product_name' => 'Item Do Gley', 'requester_name' => 'Gley']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras', ['vendedor' => 'Yhan']));

        $response->assertSee('Item Do Yhan', false);
        $response->assertDontSee('Item Do Gley', false);
    }

    public function test_filtra_por_mes_unificado_entre_planilha_e_fluxo_real(): void
    {
        $this->criarHistorico(['product_name' => 'Item Agosto Planilha', 'data_compra' => '2026-08-10']);
        $this->criarHistorico(['product_name' => 'Item Janeiro Planilha', 'data_compra' => '2026-01-10']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras', ['mes' => '2026-08']));

        $response->assertSee('Item Agosto Planilha', false);
        $response->assertDontSee('Item Janeiro Planilha', false);
    }

    public function test_filtra_por_aba_origem(): void
    {
        $this->criarHistorico(['aba_origem' => 'JAN.  FEV.', 'product_name' => 'Item Jan Fev']);
        $this->criarHistorico(['aba_origem' => 'AGOSTO', 'product_name' => 'Item Agosto']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras', ['aba_origem' => 'AGOSTO']));

        $response->assertSee('Item Agosto', false);
        $response->assertDontSee('Item Jan Fev', false);
    }

    public function test_mostra_dados_importacao_para_auditoria(): void
    {
        $this->criarHistorico([
            'product_name' => 'Item Com Dados Extra',
            'dados_importacao' => ['pedido' => '202348', 'filial' => 'C3 Tech'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Ver dados originais da planilha', false);
        $response->assertSee('202348', false);
    }

    public function test_usa_rotulos_legiveis_em_vez_de_chaves_cruas(): void
    {
        $this->criarHistorico([
            'product_name' => 'Item Com Rotulo',
            'dados_importacao' => ['data_entrada' => '2026-02-04 00:00:00', 'pedido' => '999'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Data de Entrada', false);
        $response->assertDontSee('data_entrada', false);
        $response->assertSee('Pedido', false);
    }

    public function test_indica_entrada_confirmada_quando_data_entrada_presente(): void
    {
        $this->criarHistorico([
            'product_name' => 'Item Com Entrada',
            'dados_importacao' => ['data_entrada' => '2026-02-18 00:00:00'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Entrada em 18/02/2026', false);
    }

    public function test_indica_falta_de_confirmacao_de_entrada_quando_ausente(): void
    {
        $this->criarHistorico([
            'product_name' => 'Item Sem Entrada',
            'dados_importacao' => ['pedido' => '123'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Sem confirmação de entrada/retirada na planilha', false);
    }

    public function test_requisicao_real_finalizada_mostra_data_de_entrada_do_fluxo_e_nao_mensagem_da_planilha(): void
    {
        PurchaseRequest::factory()->create([
            'product_name' => 'Requisicao Real Com Entrada',
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => \Carbon\Carbon::parse('2026-08-19 11:53:50', 'UTC'),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $entradaEsperada = \Carbon\Carbon::parse('2026-08-19 11:53:50', 'UTC')->timezone('America/Sao_Paulo')->format('d/m/Y');
        $response->assertSee('Entrada em ' . $entradaEsperada, false);
        $response->assertDontSee('Sem confirmação de entrada/retirada na planilha', false);
    }

    public function test_requisicao_real_conferida_mas_sem_entrada_mostra_aguardando_entrada(): void
    {
        PurchaseRequest::factory()->create([
            'product_name' => 'Requisicao Conferida Sem Entrada',
            'status' => 'rejeitado',
            'status_conferencia' => 'conferido_ok',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Aguardando entrada', false);
        $response->assertDontSee('Sem confirmação de entrada/retirada na planilha', false);
    }

    public function test_showroom_usa_data_de_retirada_quando_nao_ha_data_de_entrada(): void
    {
        $this->criarHistorico([
            'product_name' => 'Item Showroom',
            'aba_origem' => 'ShowRoom1',
            'dados_importacao' => ['data_retirada' => '2026-02-18'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Retirada em 18/02/2026', false);
    }

    public function test_cotacao_pati_nao_mostra_indicador_de_entrada(): void
    {
        $this->criarHistorico([
            'product_name' => 'Item Cotação Sem Entrada',
            'tipo_registro' => 'cotacao_historica',
            'status' => 'pendente',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertDontSee('Sem confirmação de entrada/retirada na planilha', false);
    }

    public function test_grupo_com_item_ainda_pendente_aparece_inteiro_no_historico(): void
    {
        PurchaseRequest::factory()->create([
            'grupo_id' => 'GRUPO-MISTO-1',
            'product_name' => 'Item Ja Conferido Com Entrada',
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
        ]);
        PurchaseRequest::factory()->create([
            'grupo_id' => 'GRUPO-MISTO-1',
            'product_name' => 'Item Ainda Pendente Do Mesmo Pedido',
            'status' => 'pendente',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Item Ja Conferido Com Entrada', false);
        $response->assertSee('Item Ainda Pendente Do Mesmo Pedido', false);
        $response->assertSee('Parcial', false);
    }

    public function test_ordena_por_ultima_atividade_e_nao_pela_data_de_criacao_original(): void
    {
        // Requisicao criada ha muito tempo mas aprovada AGORA precisa aparecer antes de
        // um item da planilha parado desde o import — o admin quer ver o que acabou de
        // acontecer, nao uma ordenacao pela data original da compra/criacao.
        $this->criarHistorico([
            'product_name' => 'Item Da Planilha Parado Desde O Import',
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        PurchaseRequest::factory()->create([
            'product_name' => 'Requisicao Antiga Aprovada Agora Mesmo',
            'status' => 'aprovado',
            'created_at' => now()->subDays(20),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSeeInOrder(['Requisicao Antiga Aprovada Agora Mesmo', 'Item Da Planilha Parado Desde O Import'], false);
    }

    public function test_desempata_por_data_da_compra_quando_ultima_atividade_e_igual(): void
    {
        // Lote inteiro de planilha importado de uma vez tem o MESMO updated_at (horario
        // do import) — sem desempate, a ordem entre eles seria arbitraria em vez de
        // cronologica pela data real da compra.
        $mesmoInstante = now()->subDays(10);
        $this->criarHistorico([
            'product_name' => 'Item Comprado Depois',
            'data_compra' => '2026-08-20',
            'updated_at' => $mesmoInstante,
        ]);
        $this->criarHistorico([
            'product_name' => 'Item Comprado Antes',
            'data_compra' => '2026-08-01',
            'updated_at' => $mesmoInstante,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSeeInOrder(['Item Comprado Depois', 'Item Comprado Antes'], false);
    }

    public function test_cotacao_sem_data_compra_nao_aparece_como_mes_1970_no_filtro(): void
    {
        $this->criarHistorico([
            'product_name' => 'Cotacao Sem Data',
            'tipo_registro' => 'cotacao_historica',
            'status' => 'pendente',
            'data_compra' => null,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertDontSee('1970', false);
    }

    public function test_agrupa_itens_do_mesmo_pedido(): void
    {
        $this->criarHistorico(['grupo_id' => 'JAN.  FEV._PEDIDO_202348', 'product_name' => 'Item A do Pedido']);
        $this->criarHistorico(['grupo_id' => 'JAN.  FEV._PEDIDO_202348', 'product_name' => 'Item B do Pedido']);

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertOk();
        $response->assertSee('2 itens', false);
        $response->assertSee('Item A do Pedido', false);
        $response->assertSee('Item B do Pedido', false);
    }
}
