<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FornecedorMesclaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Fornecedor $joyce;
    private Fornecedor $joyceInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Guilherme Admin']);
        $this->joyce = Fornecedor::create(['nome' => 'Joyce']);
        $this->joyceInfo = Fornecedor::create(['nome' => 'JOYCE INFORMÁTICA']);

        PurchaseRequest::factory()->count(2)->create(['fornecedor_id' => $this->joyce->id, 'supplier' => 'Joyce', 'supplier_original' => 'joyce ']);
        PurchaseRequest::factory()->create(['fornecedor_id' => $this->joyce->id, 'supplier' => 'Joyce', 'supplier_original' => null, 'tipo_registro' => 'compra_historica']);
        PurchaseRequest::factory()->count(3)->create(['fornecedor_id' => $this->joyceInfo->id, 'supplier' => 'JOYCE INFORMÁTICA', 'supplier_original' => 'Joyce Informatica Ltda']);
    }

    private function mesclar(array $dados)
    {
        return $this->actingAs($this->admin)
            ->from(route('admin.fornecedores.index'))
            ->post(route('admin.fornecedores.mesclar'), $dados);
    }

    public function test_so_admin_acessa(): void
    {
        $this->get(route('admin.fornecedores.index'))->assertRedirect(route('login'));

        $vendedor = User::factory()->create(['role' => null]);
        $this->actingAs($vendedor)->get(route('admin.fornecedores.index'))->assertForbidden();
        $this->actingAs($vendedor)->post(route('admin.fornecedores.mesclar'), [
            'origem_id' => $this->joyce->id, 'destino_id' => $this->joyceInfo->id, 'confirmar' => '1',
        ])->assertForbidden();

        $this->assertSame(2, Fornecedor::count());
    }

    public function test_lista_fornecedores_com_compras_e_grafias_originais(): void
    {
        $this->actingAs($this->admin)->get(route('admin.fornecedores.index'))
            ->assertOk()
            ->assertSee('JOYCE INFORMÁTICA')
            ->assertSee('Joyce Informatica Ltda')
            ->assertSeeInOrder(['Joyce', '3 compra']);
    }

    public function test_busca_filtra_pelo_nome_normalizado(): void
    {
        $this->actingAs($this->admin)->get(route('admin.fornecedores.index', ['q' => 'informatica']))
            ->assertSee('JOYCE INFORMÁTICA')
            ->assertDontSee('>Joyce<', false);
    }

    public function test_mesclar_move_as_compras_apaga_a_origem_e_registra_quem_fez(): void
    {
        $this->mesclar(['origem_id' => $this->joyce->id, 'destino_id' => $this->joyceInfo->id, 'confirmar' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertNull(Fornecedor::find($this->joyce->id));
        $this->assertSame(6, $this->joyceInfo->compras()->count());
        $this->assertSame(6, PurchaseRequest::withoutGlobalScopes()->where('supplier', 'JOYCE INFORMÁTICA')->count());
        // texto original nunca se perde: quem não tinha, guarda o nome antigo
        $this->assertSame(2, PurchaseRequest::withoutGlobalScopes()->where('supplier_original', 'joyce ')->count());
        $this->assertSame(1, PurchaseRequest::withoutGlobalScopes()->where('supplier_original', 'Joyce')->count());

        $log = DB::table('fornecedor_mesclagens')->first();
        $this->assertSame('Joyce', $log->origem_nome);
        $this->assertSame('JOYCE', $log->origem_normalizado);
        $this->assertSame($this->joyceInfo->id, $log->destino_id);
        $this->assertSame('JOYCE INFORMÁTICA', $log->destino_nome);
        $this->assertSame(3, (int) $log->compras_afetadas);
        $this->assertSame($this->admin->id, $log->user_id);
    }

    public function test_historico_de_mesclagens_aparece_na_tela(): void
    {
        $this->mesclar(['origem_id' => $this->joyce->id, 'destino_id' => $this->joyceInfo->id, 'confirmar' => '1']);

        $this->actingAs($this->admin)->get(route('admin.fornecedores.index'))
            ->assertSeeInOrder(['Mesclagens', 'Joyce', 'JOYCE INFORMÁTICA', 'Guilherme Admin']);
    }

    public function test_mesclar_exige_confirmacao(): void
    {
        $this->mesclar(['origem_id' => $this->joyce->id, 'destino_id' => $this->joyceInfo->id])
            ->assertSessionHasErrors('confirmar');

        $this->assertSame(2, Fornecedor::count());
        $this->assertSame(3, $this->joyce->compras()->count());
    }

    public function test_nao_mescla_com_ele_mesmo(): void
    {
        $this->mesclar(['origem_id' => $this->joyce->id, 'destino_id' => $this->joyce->id, 'confirmar' => '1'])
            ->assertSessionHasErrors('destino_id');

        $this->assertSame(2, Fornecedor::count());
    }

    public function test_aba_fornecedores_aparece_no_admin(): void
    {
        $this->actingAs($this->admin)->get(route('admin.index'))
            ->assertSee(route('admin.fornecedores.index'), false);
    }
}
