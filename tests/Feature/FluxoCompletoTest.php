<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Percorre o fluxo inteiro pelas rotas reais:
 * vendedor cria -> admin aprova -> coleta -> conferencia -> entrada.
 */
class FluxoCompletoTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;
    private User $admin;
    private User $conferente;
    private User $entrada;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $this->vendedor = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->conferente = User::factory()->create(['role' => 'conferente', 'name' => 'Carlos Conferente']);
        $this->entrada = User::factory()->create(['role' => 'entrada']);
    }

    private function vendedorCriaRequisicao(): array
    {
        $this->actingAs($this->vendedor)->post(route('requests.store'), [
            'requester_name' => 'Vendedor Fluxo',
            'supplier' => 'Fornecedor Fluxo',
            'urgency' => 'media',
            'reason' => 'Reposição',
            'justification' => 'Filial 31',
            'tipo_entrega' => 'estoque',
            'products' => [
                ['product_name' => 'Produto Alfa', 'quantity' => 2],
                ['product_name' => 'Produto Beta', 'quantity' => 3],
            ],
        ])->assertSessionHasNoErrors();

        return [
            PurchaseRequest::where('product_name', 'Produto Alfa')->firstOrFail(),
            PurchaseRequest::where('product_name', 'Produto Beta')->firstOrFail(),
        ];
    }

    private function adminMudaStatus(PurchaseRequest $item, string $status): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.requests.update', $item), ['status' => $status, 'empresa_compradora' => 'Binário'])
            ->assertSessionHasNoErrors();
    }

    private function coleta(string $resultado)
    {
        return $this->actingAs($this->conferente)
            ->get(route('conferencia.index', ['aba' => 'coleta', 'resultado' => $resultado]));
    }

    public function test_requisicao_nova_nasce_pendente_e_aguardando_coleta_mas_nao_aparece_na_coleta(): void
    {
        [$alfa, $beta] = $this->vendedorCriaRequisicao();

        $this->assertSame('pendente', $alfa->status);
        $this->assertSame('aguardando', $alfa->status_coleta);

        $this->coleta('aguardando')
            ->assertOk()
            ->assertDontSee('Produto Alfa')
            ->assertDontSee('Produto Beta');
    }

    public function test_aprovar_no_admin_faz_o_item_aparecer_na_coleta_como_aguardando(): void
    {
        [$alfa, $beta] = $this->vendedorCriaRequisicao();

        $this->adminMudaStatus($alfa, 'aprovado');

        $this->coleta('aguardando')
            ->assertSee('Produto Alfa')
            ->assertDontSee('Produto Beta');
        $this->coleta('coletado')->assertDontSee('Produto Alfa');
        $this->coleta('atraso')->assertDontSee('Produto Alfa');
    }

    public function test_rejeitar_ou_voltar_para_pendente_tira_o_item_da_coleta(): void
    {
        [$alfa] = $this->vendedorCriaRequisicao();
        $this->adminMudaStatus($alfa, 'aprovado');
        $this->coleta('aguardando')->assertSee('Produto Alfa');

        $this->adminMudaStatus($alfa, 'rejeitado');

        $this->coleta('aguardando')->assertDontSee('Produto Alfa');
    }

    public function test_coletar_move_o_item_de_aba_e_marcar_atraso_vai_para_aba_atraso(): void
    {
        [$alfa, $beta] = $this->vendedorCriaRequisicao();
        $this->adminMudaStatus($alfa, 'aprovado');
        $this->adminMudaStatus($beta, 'aprovado');

        $this->actingAs($this->conferente)->patch(route('conferencia.coleta', $alfa), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T09:00',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->conferente)->patch(route('conferencia.coleta', $beta), [
            'status_coleta' => 'atraso',
            'data_coleta' => '2026-09-30T09:05',
        ])->assertSessionHasNoErrors();

        $this->coleta('aguardando')->assertDontSee('Produto Alfa')->assertDontSee('Produto Beta');
        $this->coleta('coletado')->assertSee('Produto Alfa')->assertDontSee('Produto Beta');
        $this->coleta('atraso')->assertSee('Produto Beta')->assertDontSee('Produto Alfa');

        $this->assertSame('Carlos Conferente', $alfa->fresh()->coletado_por);
        $this->assertNull($beta->fresh()->coletado_por);
    }

    public function test_coleta_e_conferencia_sao_independentes(): void
    {
        [$alfa] = $this->vendedorCriaRequisicao();
        $this->adminMudaStatus($alfa, 'aprovado');

        $this->actingAs($this->conferente)->patch(route('conferencia.coleta', $alfa), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T09:00',
        ]);

        $this->actingAs($this->conferente)
            ->get(route('conferencia.index'))
            ->assertSee('Produto Alfa');
        $this->assertNull($alfa->fresh()->status_conferencia);
    }

    public function test_fluxo_completo_ate_a_entrada(): void
    {
        [$alfa] = $this->vendedorCriaRequisicao();

        $this->adminMudaStatus($alfa, 'aprovado');

        $this->actingAs($this->conferente)->patch(route('conferencia.coleta', $alfa), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T09:00',
        ]);

        $this->actingAs($this->conferente)->patch(route('conferencia.conferir', $alfa), [
            'quantidade_recebida' => 2,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ])->assertSessionHasNoErrors();

        $this->assertSame('conferido_ok', $alfa->fresh()->status_conferencia);

        $this->actingAs($this->entrada)
            ->get(route('entrada.index'))
            ->assertSee('Produto Alfa');

        $this->actingAs($this->entrada)->patch(route('entrada.darEntrada', $alfa), [
            'vendedor_destino' => 'Vendedor Fluxo',
            'quantidade_entrada' => 2,
        ])->assertSessionHasNoErrors();

        $final = $alfa->fresh();
        $this->assertNotNull($final->entrada_concluida_em);
        $this->assertSame('coletado', $final->status_coleta);
        $this->assertSame('Carlos Conferente', $final->coletado_por);

        $this->actingAs($this->entrada)
            ->get(route('entrada.index', ['aba' => 'concluidas']))
            ->assertSee('Produto Alfa');
    }

    public function test_entrada_nao_consegue_dar_entrada_em_item_nao_conferido(): void
    {
        [$alfa] = $this->vendedorCriaRequisicao();
        $this->adminMudaStatus($alfa, 'aprovado');

        $this->actingAs($this->entrada)->patch(route('entrada.darEntrada', $alfa), [
            'vendedor_destino' => 'Vendedor Fluxo',
            'quantidade_entrada' => 2,
        ])->assertSessionHas('aviso');

        $this->assertNull($alfa->fresh()->entrada_concluida_em);
    }
}
