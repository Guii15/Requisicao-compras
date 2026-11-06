<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\RmaEnvio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * "Enviar ao RMA": quem confere ou dá entrada manda um item com defeito para o setor RMA,
 * que preenche na aba "Enviados ao RMA" o que precisa para devolver ao fornecedor
 * (nº de série, protocolo, envio, rastreio, status e observação).
 */
class EnviarAoRmaTest extends TestCase
{
    use RefreshDatabase;

    private User $rma;
    private User $conferente;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->rma = User::factory()->create(['role' => 'rma', 'is_admin' => false]);
        $this->conferente = User::factory()->create(['role' => 'conferente', 'is_admin' => false, 'name' => 'Kelber']);
    }

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'product_name' => 'Fonte 500W',
            'supplier' => 'Joyce Informática',
            'quantity' => 10,
            'quantidade_recebida' => 10,
            'data_compra' => '2026-10-20',
            'preco_unitario' => 1234.56,
            'status_conferencia' => 'conferido_ok',
        ], $attrs));
    }

    private function enviar(User $quem, PurchaseRequest $item, array $dados = [])
    {
        return $this->actingAs($quem)->post(route('rma.enviar', $item), array_merge([
            'quantidade' => 2,
            'defeito' => 'Não liga',
        ], $dados));
    }

    // ---------- enviar ----------

    public function test_conferente_envia_item_conferido_ao_rma(): void
    {
        $item = $this->item();

        $this->enviar($this->conferente, $item, ['foto' => UploadedFile::fake()->image('defeito.jpg')])
            ->assertRedirect()
            ->assertSessionHas('success');

        $envio = RmaEnvio::sole();
        $this->assertSame($item->id, $envio->purchase_request_id);
        $this->assertSame(2, $envio->quantidade);
        $this->assertSame('Não liga', $envio->defeito);
        $this->assertSame('Aguardando envio', $envio->status);
        $this->assertSame($this->conferente->id, $envio->enviado_por_id);
        Storage::disk('public')->assertExists($envio->foto_path);
    }

    public function test_entrada_e_admin_tambem_enviam(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada', 'is_admin' => false]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->enviar($entrada, $this->item(['entrada_concluida_em' => now()]))->assertSessionHasNoErrors();
        $this->enviar($admin, $this->item())->assertSessionHasNoErrors();

        $this->assertSame(2, RmaEnvio::count());
    }

    public function test_vendedor_rma_e_financeiro_nao_enviam(): void
    {
        $item = $this->item();

        foreach ([null, 'rma', 'financeiro'] as $role) {
            $this->enviar(User::factory()->create(['role' => $role, 'is_admin' => false]), $item)->assertForbidden();
        }

        $this->assertSame(0, RmaEnvio::count());
    }

    public function test_defeito_e_quantidade_sao_obrigatorios(): void
    {
        $this->enviar($this->conferente, $this->item(), ['defeito' => '', 'quantidade' => ''])
            ->assertSessionHasErrors(['defeito', 'quantidade']);

        $this->assertSame(0, RmaEnvio::count());
    }

    public function test_nao_envia_mais_do_que_chegou_somando_envios_anteriores(): void
    {
        $item = $this->item(['quantity' => 10, 'quantidade_recebida' => 3]);

        $this->enviar($this->conferente, $item, ['quantidade' => 4])->assertSessionHasErrors('quantidade');
        $this->enviar($this->conferente, $item, ['quantidade' => 2])->assertSessionHasNoErrors();
        $this->enviar($this->conferente, $item, ['quantidade' => 2])->assertSessionHasErrors('quantidade');
        $this->enviar($this->conferente, $item, ['quantidade' => 1])->assertSessionHasNoErrors();

        $this->assertSame(3, (int) RmaEnvio::sum('quantidade'));
    }

    public function test_item_ainda_nao_conferido_nao_vai_ao_rma(): void
    {
        $this->enviar($this->conferente, $this->item(['status_conferencia' => null, 'quantidade_recebida' => null]))
            ->assertSessionHasErrors('quantidade');

        $this->assertSame(0, RmaEnvio::count());
    }

    // ---------- botões ----------

    public function test_botao_aparece_na_conferencia_em_item_conferido(): void
    {
        $item = $this->item();

        $this->actingAs($this->conferente)->get(route('conferencia.index', ['aba' => 'conferidos']))
            ->assertOk()
            ->assertSee('Enviar ao RMA')
            ->assertSee(route('rma.enviar', $item), false);
    }

    public function test_botao_aparece_na_entrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada', 'is_admin' => false]);
        $aguardando = $this->item(['product_name' => 'Aguardando entrada']);
        $concluida = $this->item(['product_name' => 'Ja deu entrada', 'entrada_concluida_em' => now(), 'quantidade_entrada' => 10]);

        $this->actingAs($entrada)->get(route('entrada.index'))
            ->assertOk()->assertSee(route('rma.enviar', $aguardando), false);
        $this->actingAs($entrada)->get(route('entrada.index', ['aba' => 'concluidas']))
            ->assertOk()->assertSee(route('rma.enviar', $concluida), false);
    }

    // ---------- aba do RMA ----------

    public function test_aba_enviados_lista_o_que_foi_mandado_sem_valores(): void
    {
        $this->enviar($this->conferente, $this->item(), ['defeito' => 'Cooler travado']);

        $this->actingAs($this->rma)->get(route('rma.envios'))
            ->assertOk()
            ->assertSee('Fonte 500W')
            ->assertSee('Joyce Informática')
            ->assertSee('Cooler travado')
            ->assertSee('Kelber')
            ->assertSee('Aguardando envio')
            ->assertDontSee('1.234,56')
            ->assertDontSee('1234.56');
    }

    public function test_aba_enviados_so_para_rma_e_admin(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('rma.envios'))->assertOk();

        foreach ([null, 'conferente', 'entrada', 'financeiro'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'is_admin' => false]))
                ->get(route('rma.envios'))->assertForbidden();
        }
    }

    public function test_filtra_abertos_e_finalizados(): void
    {
        $item = $this->item();
        RmaEnvio::create(['purchase_request_id' => $item->id, 'quantidade' => 1, 'defeito' => 'Defeito aberto', 'status' => 'Enviado ao fornecedor']);
        RmaEnvio::create(['purchase_request_id' => $item->id, 'quantidade' => 1, 'defeito' => 'Defeito fechado', 'status' => 'Crédito']);

        $this->actingAs($this->rma)->get(route('rma.envios'))
            ->assertSee('Defeito aberto')->assertDontSee('Defeito fechado');
        $this->actingAs($this->rma)->get(route('rma.envios', ['situacao' => 'finalizados']))
            ->assertSee('Defeito fechado')->assertDontSee('Defeito aberto');
        $this->actingAs($this->rma)->get(route('rma.envios', ['situacao' => 'todos']))
            ->assertSee('Defeito fechado')->assertSee('Defeito aberto');
    }

    public function test_rma_preenche_os_dados_do_envio_ao_fornecedor(): void
    {
        $this->enviar($this->conferente, $this->item());
        $envio = RmaEnvio::sole();

        $this->actingAs($this->rma)->patch(route('rma.envios.update', $envio), [
            'status' => 'Enviado ao fornecedor',
            'numero_serie' => 'SN123',
            'protocolo_fornecedor' => 'RMA-998',
            'data_envio' => '2026-11-04',
            'codigo_rastreio' => 'BR123456789',
            'observacao' => 'Mandado pelos Correios',
        ])->assertRedirect()->assertSessionHas('success');

        $envio->refresh();
        $this->assertSame('Enviado ao fornecedor', $envio->status);
        $this->assertSame('SN123', $envio->numero_serie);
        $this->assertSame('RMA-998', $envio->protocolo_fornecedor);
        $this->assertSame('2026-11-04', $envio->data_envio->format('Y-m-d'));
        $this->assertSame('BR123456789', $envio->codigo_rastreio);
        $this->assertSame('Mandado pelos Correios', $envio->observacao);
        $this->assertSame($this->rma->id, $envio->atualizado_por_id);
    }

    public function test_status_precisa_ser_da_lista(): void
    {
        $this->enviar($this->conferente, $this->item());

        $this->actingAs($this->rma)->patch(route('rma.envios.update', RmaEnvio::sole()), ['status' => 'Qualquer coisa'])
            ->assertSessionHasErrors('status');
    }

    public function test_conferente_nao_altera_o_envio(): void
    {
        $this->enviar($this->conferente, $this->item());

        $this->actingAs($this->conferente)->patch(route('rma.envios.update', RmaEnvio::sole()), ['status' => 'Crédito'])
            ->assertForbidden();

        $this->assertSame('Aguardando envio', RmaEnvio::sole()->status);
    }

    public function test_tela_de_compras_do_rma_tem_link_para_a_aba_enviados(): void
    {
        $this->actingAs($this->rma)->get(route('rma.index'))->assertOk()->assertSee(route('rma.envios'), false);
    }
}
