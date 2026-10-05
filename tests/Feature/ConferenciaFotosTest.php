<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A foto (e as extras) que a conferência tirou ao conferir aparece para a própria Conferência, na aba Conferidos.
 */
class ConferenciaFotosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function conferido(array $caminhos, array $attrs = []): PurchaseRequest
    {
        $item = PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'quantity' => 5, 'quantidade_recebida' => 5, 'product_name' => 'Cabo Conferido',
        ], $attrs));

        foreach ($caminhos as $c) {
            ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => $c, 'nome_original' => basename($c)]);
        }

        return $item;
    }

    private function conferidos(string $role = 'conferente'): string
    {
        return $this->actingAs(User::factory()->create(['role' => $role]))
            ->get(route('conferencia.index', ['aba' => 'conferidos']))->assertOk()->getContent();
    }

    public function test_conferidos_mostra_todas_as_fotos_do_item(): void
    {
        $this->conferido(['conferencia/principal.jpg', 'conferencia/barras1.jpg', 'conferencia/barras2.jpg']);

        $html = $this->conferidos();

        foreach (['principal', 'barras1', 'barras2'] as $nome) {
            $this->assertStringContainsString("/conferencia/{$nome}.jpg", $html);
        }
        $this->assertSame(2, substr_count($html, 'Fotos da conferência')); // desktop + celular
    }

    public function test_item_sem_foto_nao_mostra_o_bloco(): void
    {
        $this->conferido([]);

        $this->assertStringNotContainsString('Fotos da conferência', $this->conferidos());
    }

    public function test_divergente_cancelado_e_avancado_tambem_mostram_a_foto(): void
    {
        $this->conferido(['conferencia/div.jpg'], ['status_conferencia' => 'divergente', 'product_name' => 'Item Div']);
        $this->conferido(['conferencia/canc.jpg'], ['status_conferencia' => 'cancelado', 'product_name' => 'Item Canc']);
        $this->conferido(['conferencia/avanc.jpg'], ['status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Item Avanc']);

        $html = $this->conferidos();

        foreach (['div', 'canc', 'avanc'] as $nome) {
            $this->assertStringContainsString("/conferencia/{$nome}.jpg", $html);
        }
    }

    public function test_quem_e_da_entrada_tambem_ve_as_fotos_na_conferencia(): void
    {
        $this->conferido(['conferencia/principal.jpg']);

        $this->assertStringContainsString('/conferencia/principal.jpg', $this->conferidos('entrada'));
    }

    public function test_aba_aguardando_nao_tem_o_bloco_de_fotos(): void
    {
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Ainda Nao Conferido']);

        $html = $this->actingAs(User::factory()->create(['role' => 'conferente']))->get(route('conferencia.index'))->getContent();

        $this->assertStringNotContainsString('Fotos da conferência', $html);
    }

    public function test_as_fotos_vem_numa_consulta_so_sem_uma_por_item(): void
    {
        foreach (range(1, 6) as $i) {
            $this->conferido(["conferencia/f{$i}.jpg"], ['product_name' => "Item {$i}"]);
        }

        $this->actingAs(User::factory()->create(['role' => 'conferente']));
        DB::enableQueryLog();
        $this->get(route('conferencia.index', ['aba' => 'conferidos']))->assertOk();
        $consultas = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'conferencia_fotos'))->count();

        $this->assertSame(1, $consultas);
    }
}
