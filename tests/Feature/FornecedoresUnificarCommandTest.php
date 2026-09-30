<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
use App\Services\UnificadorFornecedores;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FornecedoresUnificarCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $csv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->csv = sys_get_temp_dir() . '/fornecedores-mapa-teste-' . uniqid() . '.csv';
    }

    protected function tearDown(): void
    {
        @unlink($this->csv);
        parent::tearDown();
    }

    private function compras(string $supplier, int $quantas, array $attrs = []): void
    {
        PurchaseRequest::factory()->count($quantas)->create(array_merge(['supplier' => $supplier], $attrs));
    }

    /** Cenário típico: 3 grafias do mesmo fornecedor + um "parecido" + um diferente. */
    private function cenario(): void
    {
        $this->compras('Joyce Informática', 3);
        $this->compras('JOYCE INFORMATICA LTDA', 1);
        $this->compras(' joyce informatica ', 1);
        $this->compras('Joyce', 2);
        $this->compras('Kabum', 4);
    }

    private function supplierDasCompras(): array
    {
        return DB::table('purchase_requests')->orderBy('id')->pluck('supplier')->all();
    }

    public function test_agrupa_identicos_e_escolhe_a_grafia_mais_usada(): void
    {
        $this->cenario();

        $grupos = app(UnificadorFornecedores::class)->grupos()->keyBy('normalizado');

        $this->assertSame('Joyce Informática', $grupos['JOYCE INFORMATICA']['nome_final']);
        $this->assertSame(5, $grupos['JOYCE INFORMATICA']['compras']);
        $this->assertCount(3, $grupos['JOYCE INFORMATICA']['variantes']);
        $this->assertSame(2, $grupos['JOYCE']['compras']);
        $this->assertSame(4, $grupos['KABUM']['compras']);
    }

    public function test_parecidos_viram_so_sugestao(): void
    {
        $this->cenario();
        $this->compras('Kabun', 1); // erro de digitação

        $sugestoes = app(UnificadorFornecedores::class)->sugestoes();
        $pares = $sugestoes->map(fn ($s) => [$s['a'], $s['b']])->all();

        $this->assertContains(['JOYCE', 'JOYCE INFORMATICA'], $pares);
        $this->assertContains(['KABUM', 'KABUN'], $pares);
        $this->assertNotContains(['JOYCE', 'KABUM'], $pares);
    }

    public function test_dry_run_mostra_o_mapeamento_exporta_csv_e_nao_grava_nada(): void
    {
        $this->cenario();
        $antes = $this->supplierDasCompras();

        $this->artisan('fornecedores:unificar', ['--dry-run' => true, '--exportar' => $this->csv])
            ->expectsOutputToContain('JOYCE INFORMATICA LTDA')
            ->expectsOutputToContain('Joyce Informática')
            ->expectsOutputToContain('Sugestões')
            ->expectsOutputToContain('Nada foi gravado')
            ->assertSuccessful();

        $this->assertSame(0, Fornecedor::count());
        $this->assertSame($antes, $this->supplierDasCompras());
        $this->assertFileExists($this->csv);
        $this->assertStringContainsString('nome_original;normalizado;fornecedor_final;compras;sugestao', file_get_contents($this->csv));
    }

    public function test_aplica_so_os_identicos_e_guarda_o_texto_original(): void
    {
        $this->cenario();

        $this->artisan('fornecedores:unificar')
            ->expectsConfirmation('Gravar esse mapeamento no banco?', 'yes')
            ->assertSuccessful();

        $this->assertSame(3, Fornecedor::count());
        $joyceInfo = Fornecedor::where('nome_normalizado', 'JOYCE INFORMATICA')->first();
        $this->assertSame('Joyce Informática', $joyceInfo->nome);
        $this->assertSame(5, $joyceInfo->compras()->count());
        $this->assertSame(5, PurchaseRequest::withoutGlobalScopes()->where('supplier', 'Joyce Informática')->count());
        $this->assertSame(1, PurchaseRequest::withoutGlobalScopes()->where('supplier_original', 'JOYCE INFORMATICA LTDA')->count());
        $this->assertSame(1, PurchaseRequest::withoutGlobalScopes()->where('supplier_original', ' joyce informatica ')->count());
        $this->assertSame(2, Fornecedor::where('nome_normalizado', 'JOYCE')->first()->compras()->count());
    }

    public function test_rodar_de_novo_nao_muda_nada(): void
    {
        $this->cenario();
        $this->artisan('fornecedores:unificar')->expectsConfirmation('Gravar esse mapeamento no banco?', 'yes');
        $depoisDaPrimeira = [Fornecedor::count(), $this->supplierDasCompras(), DB::table('purchase_requests')->pluck('supplier_original')->all()];

        $this->artisan('fornecedores:unificar')
            ->expectsConfirmation('Gravar esse mapeamento no banco?', 'yes')
            ->expectsOutputToContain('0 compra(s) alterada(s)')
            ->assertSuccessful();

        $this->assertSame($depoisDaPrimeira, [Fornecedor::count(), $this->supplierDasCompras(), DB::table('purchase_requests')->pluck('supplier_original')->all()]);
    }

    public function test_recusar_a_confirmacao_nao_grava(): void
    {
        $this->cenario();

        $this->artisan('fornecedores:unificar')->expectsConfirmation('Gravar esse mapeamento no banco?', 'no');

        $this->assertSame(0, Fornecedor::count());
    }

    public function test_mapa_revisado_junta_as_sugestoes_aprovadas(): void
    {
        $this->cenario();
        $this->artisan('fornecedores:unificar', ['--dry-run' => true, '--exportar' => $this->csv]);

        // Você edita o CSV: "Joyce" passa a ir para "Joyce Informática".
        $linhas = file($this->csv);
        $linhas = array_map(fn ($l) => str_starts_with(ltrim($l, "\u{FEFF}"), 'Joyce;') ? preg_replace('/^(\x{FEFF}?Joyce;JOYCE;)[^;]*/u', '$1Joyce Informática', $l) : $l, $linhas);
        file_put_contents($this->csv, implode('', $linhas));

        $this->artisan('fornecedores:unificar', ['--mapa' => $this->csv])
            ->expectsConfirmation('Gravar esse mapeamento no banco?', 'yes')
            ->assertSuccessful();

        $this->assertSame(2, Fornecedor::count());
        $this->assertSame(7, Fornecedor::where('nome_normalizado', 'JOYCE INFORMATICA')->first()->compras()->count());
        $this->assertSame(2, PurchaseRequest::withoutGlobalScopes()->where('supplier_original', 'Joyce')->where('supplier', 'Joyce Informática')->count());
    }

    public function test_inclui_historico_e_ignora_texto_vazio(): void
    {
        $this->compras('Joyce Informática', 1, ['tipo_registro' => 'compra_historica']);
        $this->compras('  --- ', 1);
        $this->compras('', 1);
        PurchaseRequest::factory()->create(['supplier' => null]);

        $this->artisan('fornecedores:unificar')->expectsConfirmation('Gravar esse mapeamento no banco?', 'yes');

        $this->assertSame(1, Fornecedor::count());
        $this->assertSame(1, Fornecedor::first()->compras()->count());
        $this->assertSame(1, PurchaseRequest::withoutGlobalScopes()->where('supplier', '  --- ')->whereNull('fornecedor_id')->count());
    }

    public function test_fornecedor_que_ja_existe_mantem_o_nome_e_recebe_as_compras(): void
    {
        Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);
        $this->compras('joyce informatica', 2);

        $this->artisan('fornecedores:unificar')->expectsConfirmation('Gravar esse mapeamento no banco?', 'yes');

        $this->assertSame(1, Fornecedor::count());
        $this->assertSame(2, PurchaseRequest::withoutGlobalScopes()->where('supplier', 'JOYCE INFORMÁTICA')->count());
    }
}
