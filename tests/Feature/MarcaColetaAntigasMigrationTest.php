<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MarcaColetaAntigasMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function rodarMigration(): void
    {
        (require database_path('migrations/2026_09_30_160000_marca_coleta_de_requisicoes_antigas.php'))->up();
    }

    private function statusColeta(PurchaseRequest $item): string
    {
        return DB::table('purchase_requests')->where('id', $item->id)->value('status_coleta');
    }

    public function test_marca_como_coletado_o_que_ja_passou_da_coleta(): void
    {
        $conferido = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'status_coleta' => 'aguardando']);
        $divergente = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'status_coleta' => 'aguardando']);
        $comEntrada = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'entrada_concluida_em' => now(), 'status_coleta' => 'aguardando']);
        $historico = PurchaseRequest::factory()->create(['status' => 'aprovado', 'tipo_registro' => 'compra_historica', 'status_conferencia' => null, 'status_coleta' => 'aguardando']);

        $this->rodarMigration();

        $this->assertSame('coletado', $this->statusColeta($conferido));
        $this->assertSame('coletado', $this->statusColeta($divergente));
        $this->assertSame('coletado', $this->statusColeta($comEntrada));
        $this->assertSame('coletado', $this->statusColeta($historico));
    }

    public function test_mantem_aguardando_o_que_ainda_nao_foi_conferido(): void
    {
        $aprovadoSemConferencia = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'entrada_concluida_em' => null, 'status_coleta' => 'aguardando']);
        $pendente = PurchaseRequest::factory()->create(['status' => 'pendente', 'status_conferencia' => null, 'status_coleta' => 'aguardando']);

        $this->rodarMigration();

        $this->assertSame('aguardando', $this->statusColeta($aprovadoSemConferencia));
        $this->assertSame('aguardando', $this->statusColeta($pendente));
    }

    public function test_nao_mexe_em_coleta_ja_marcada_como_atraso(): void
    {
        $atrasado = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'status_coleta' => 'atraso']);

        $this->rodarMigration();

        $this->assertSame('atraso', $this->statusColeta($atrasado));
    }
}
