<?php

namespace Tests\Feature;

use App\Console\Commands\ImportarHistoricoCompras;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportarHistoricoComprasCommandTest extends TestCase
{
    use RefreshDatabase;

    private function csvFixture(): string
    {
        return base_path('tests/Fixtures/historico_compras_amostra.csv');
    }

    public function test_dry_run_nao_grava_nada_no_banco(): void
    {
        $this->artisan('compras:importar-historico', ['--csv' => $this->csvFixture(), '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, PurchaseRequest::withoutGlobalScope('apenasFluxoAtivo')->count());
    }

    public function test_arquivo_inexistente_falha_com_exit_code_1(): void
    {
        $this->artisan('compras:importar-historico', ['--csv' => base_path('tests/Fixtures/nao-existe.csv')])
            ->assertExitCode(1);
    }

    public function test_importa_as_5_linhas_da_amostra_criando_usuario_historico(): void
    {
        $this->artisan('compras:importar-historico', ['--csv' => $this->csvFixture()])
            ->assertExitCode(0);

        $this->assertSame(5, PurchaseRequest::historico()->count());

        $usuario = User::where('email', ImportarHistoricoCompras::EMAIL_USUARIO_HISTORICO)->first();
        $this->assertNotNull($usuario);
        $this->assertFalse($usuario->is_admin);

        $normal = PurchaseRequest::historico()->where('origem_id', 'JAN._FEV._L8')->first();
        $this->assertSame('compra_historica', $normal->tipo_registro);
        $this->assertSame('aprovado', $normal->status);
        $this->assertSame(1840.22, (float) $normal->valor);
        $this->assertSame($usuario->id, $normal->user_id);

        $pati = PurchaseRequest::historico()->where('origem_id', 'Compra_Pati_L2')->first();
        $this->assertSame('cotacao_historica', $pati->tipo_registro);
        $this->assertSame('pendente', $pati->status);
    }

    public function test_linhas_com_mesmo_pedido_compartilham_grupo_id(): void
    {
        $this->artisan('compras:importar-historico', ['--csv' => $this->csvFixture()])
            ->assertExitCode(0);

        $l8 = PurchaseRequest::historico()->where('origem_id', 'JAN._FEV._L8')->first();
        $l9 = PurchaseRequest::historico()->where('origem_id', 'JAN._FEV._L9')->first();

        $this->assertSame($l8->grupo_id, $l9->grupo_id);
    }

    public function test_reimportar_nao_duplica_e_atualiza_registros_existentes(): void
    {
        $this->artisan('compras:importar-historico', ['--csv' => $this->csvFixture()])->assertExitCode(0);
        $this->artisan('compras:importar-historico', ['--csv' => $this->csvFixture()])->assertExitCode(0);

        $this->assertSame(5, PurchaseRequest::historico()->count());
        $this->assertSame(1, User::where('email', ImportarHistoricoCompras::EMAIL_USUARIO_HISTORICO)->count());
    }

    public function test_registros_historicos_nao_aparecem_em_consultas_padrao(): void
    {
        $this->artisan('compras:importar-historico', ['--csv' => $this->csvFixture()])->assertExitCode(0);

        $this->assertSame(0, PurchaseRequest::count());
    }
}
