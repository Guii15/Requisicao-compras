<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPlanilhaOriginalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    /**
     * A planilha e' colocada direto no disco pelo Guilherme (fora do app,
     * via terminal do servidor) — nao existe upload pela tela. Aqui simula
     * isso escrevendo o arquivo de amostra direto no disco fake.
     */
    private function colocarPlanilhaNoDisco(): void
    {
        Storage::disk('local')->put(
            'planilhas-historico/planilha-atual.xlsx',
            file_get_contents(base_path('tests/Fixtures/planilha_original_amostra.xlsx'))
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.historico-compras.planilha.download'))->assertRedirect(route('login'));
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get(route('admin.historico-compras.planilha.download'))->assertForbidden();
    }

    public function test_download_retorna_404_quando_nenhuma_planilha_foi_colocada(): void
    {
        Storage::fake('local');

        $this->actingAs($this->admin())
            ->get(route('admin.historico-compras.planilha.download'))
            ->assertNotFound();
    }

    public function test_download_completo_retorna_o_arquivo(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras.planilha.download'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_download_por_aba_retorna_404_se_a_aba_nao_existe(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $this->actingAs($this->admin())
            ->get(route('admin.historico-compras.planilha.download-aba', 'Dezembro'))
            ->assertNotFound();
    }

    public function test_download_por_aba_retorna_um_arquivo_so_com_aquela_aba(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras.planilha.download-aba', 'Fevereiro'));

        $response->assertOk();

        $caminhoTemp = tempnam(sys_get_temp_dir(), 'teste_aba_') . '.xlsx';
        file_put_contents($caminhoTemp, $response->streamedContent());

        $planilhaBaixada = \PhpOffice\PhpSpreadsheet\IOFactory::load($caminhoTemp);
        $this->assertSame(['Fevereiro'], $planilhaBaixada->getSheetNames());
        $this->assertSame('Mouse Gamer', $planilhaBaixada->getActiveSheet()->getCell('A2')->getValue());

        unlink($caminhoTemp);
    }

    public function test_nao_existe_mais_rota_de_upload_pela_tela(): void
    {
        // A planilha e' colocada direto no servidor por fora do app, nao pela interface.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.historico-compras.planilha.upload'));
    }

    public function test_historico_compras_mostra_mensagem_quando_nenhuma_planilha_foi_colocada(): void
    {
        Storage::fake('local');

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertSee('Nenhuma planilha original enviada ainda', false);
    }

    public function test_historico_compras_mostra_data_e_select_de_abas(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertOk();
        $response->assertDontSee('Nenhuma planilha original enviada ainda', false);
        $response->assertSee('Janeiro', false);
        $response->assertSee('Fevereiro', false);
    }

    public function test_selecionar_um_mes_mostra_a_tabela_com_os_dados_daquela_aba(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras', ['planilha_aba' => 'Fevereiro']));

        $response->assertOk();
        $response->assertSee('Mouse Gamer', false);
        $response->assertDontSee('Cabo HDMI', false); // isso e' da aba Janeiro, nao devia aparecer
    }

    public function test_nao_mostra_tabela_quando_nenhum_mes_foi_selecionado(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertDontSee('Mouse Gamer', false);
        $response->assertDontSee('Cabo HDMI', false);
    }

    public function test_ignora_aba_invalida_na_url_sem_quebrar(): void
    {
        Storage::fake('local');
        $this->colocarPlanilhaNoDisco();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.historico-compras', ['planilha_aba' => 'AbaQueNaoExiste']));

        $response->assertOk();
    }

    public function test_a_tela_de_historico_de_compras_existente_continua_funcionando_normalmente(): void
    {
        // Garantia de que a secao nova nao quebrou nada da tela que ja existia.
        Storage::fake('local');

        $response = $this->actingAs($this->admin())->get(route('admin.historico-compras'));

        $response->assertOk();
        $response->assertSee('Histórico de Compras', false);
    }
}
