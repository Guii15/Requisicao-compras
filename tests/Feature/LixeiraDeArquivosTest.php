<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Support\LixeiraDeArquivos;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Arquivo anexado que é removido ou trocado não some de vez: vai para uma lixeira e fica 30 dias.
 */
class LixeiraDeArquivosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Carbon::setTestNow('2026-10-02 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_descartar_move_o_arquivo_para_a_lixeira_do_dia_sem_perder_o_conteudo(): void
    {
        Storage::disk('local')->put('pedidos-compra/p.pdf', 'conteudo importante');

        $this->assertTrue(LixeiraDeArquivos::descartar('local', 'pedidos-compra/p.pdf'));

        Storage::disk('local')->assertMissing('pedidos-compra/p.pdf');
        Storage::disk('local')->assertExists('lixeira/2026-10-02/pedidos-compra__p.pdf');
        $this->assertSame('conteudo importante', Storage::disk('local')->get('lixeira/2026-10-02/pedidos-compra__p.pdf'));
    }

    public function test_descartar_arquivo_inexistente_ou_caminho_vazio_nao_quebra(): void
    {
        $this->assertFalse(LixeiraDeArquivos::descartar('local', 'pedidos-compra/nao-existe.pdf'));
        $this->assertFalse(LixeiraDeArquivos::descartar('local', null));
        $this->assertFalse(LixeiraDeArquivos::descartar('local', ''));
    }

    public function test_dois_descartes_com_o_mesmo_nome_no_mesmo_dia_nao_se_sobrescrevem(): void
    {
        Storage::disk('local')->put('pedidos-compra/p.pdf', 'primeiro');
        LixeiraDeArquivos::descartar('local', 'pedidos-compra/p.pdf');
        Storage::disk('local')->put('pedidos-compra/p.pdf', 'segundo');
        LixeiraDeArquivos::descartar('local', 'pedidos-compra/p.pdf');

        $this->assertCount(2, Storage::disk('local')->files('lixeira/2026-10-02'));
    }

    public function test_apaga_de_vez_so_o_que_esta_na_lixeira_ha_mais_de_30_dias(): void
    {
        Storage::disk('local')->put('lixeira/2026-08-30/velho.pdf', 'x');   // 33 dias
        Storage::disk('local')->put('lixeira/2026-09-02/limite.pdf', 'x');  // 30 dias: ainda fica
        Storage::disk('local')->put('lixeira/2026-09-25/recente.pdf', 'x');
        Storage::disk('local')->put('pedidos-compra/ativo.pdf', 'x');       // fora da lixeira: nunca é tocado

        $apagadas = LixeiraDeArquivos::limparAntigos('local');

        $this->assertSame(1, $apagadas);
        Storage::disk('local')->assertMissing('lixeira/2026-08-30/velho.pdf');
        Storage::disk('local')->assertExists('lixeira/2026-09-02/limite.pdf');
        Storage::disk('local')->assertExists('lixeira/2026-09-25/recente.pdf');
        Storage::disk('local')->assertExists('pedidos-compra/ativo.pdf');
    }

    public function test_descartar_tambem_limpa_a_lixeira_antiga(): void
    {
        Storage::disk('local')->put('lixeira/2026-08-01/velho.pdf', 'x');
        Storage::disk('local')->put('pedidos-compra/p.pdf', 'y');

        LixeiraDeArquivos::descartar('local', 'pedidos-compra/p.pdf');

        Storage::disk('local')->assertMissing('lixeira/2026-08-01/velho.pdf');
        Storage::disk('local')->assertExists('lixeira/2026-10-02/pedidos-compra__p.pdf');
    }

    public function test_pastas_estranhas_na_lixeira_nao_sao_apagadas(): void
    {
        Storage::disk('local')->put('lixeira/anotacao/leia-me.txt', 'x');

        LixeiraDeArquivos::limparAntigos('local');

        Storage::disk('local')->assertExists('lixeira/anotacao/leia-me.txt');
    }

    public function test_trocar_o_pedido_de_compra_manda_o_antigo_para_a_lixeira(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $item = PurchaseRequest::factory()->aprovado()->create();
        $dados = ['data_compra' => '2026-09-20', 'preco_unitario' => '10,00', 'supplier' => 'Kabum', 'condicao_pagamento' => 'a_vista'];

        $this->actingAs($admin)->patch(route('admin.compras.update', $item), $dados + ['pedido_compra' => UploadedFile::fake()->create('antigo.pdf', 10, 'application/pdf')]);
        $antigo = $item->refresh()->pedido_compra_path;

        $this->actingAs($admin)->patch(route('admin.compras.update', $item), $dados + ['pedido_compra' => UploadedFile::fake()->create('novo.pdf', 10, 'application/pdf')]);

        Storage::disk('local')->assertMissing($antigo);
        Storage::disk('local')->assertExists('lixeira/2026-10-02/' . str_replace('/', '__', $antigo));
    }

    public function test_remover_anexo_manda_o_arquivo_para_a_lixeira(): void
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);
        $item = PurchaseRequest::factory()->create(['user_id' => $vendedor->id, 'status' => 'pendente', 'anexo_path' => 'anexos-requisicao/o.pdf', 'anexo_nome' => 'o.pdf']);
        Storage::disk('local')->put('anexos-requisicao/o.pdf', 'orcamento');

        $this->actingAs($vendedor)->delete(route('requests.anexo.remover', $item));

        Storage::disk('local')->assertMissing('anexos-requisicao/o.pdf');
        $this->assertSame('orcamento', Storage::disk('local')->get('lixeira/2026-10-02/anexos-requisicao__o.pdf'));
    }
}
