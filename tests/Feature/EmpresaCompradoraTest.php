<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Empresa compradora: qual empresa nossa (Binário, Mamuth, Ninja...) fez a compra.
 * Texto livre, preenchido pelo admin; obrigatório só ao aprovar. O RMA usa isso na garantia.
 */
class EmpresaCompradoraTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_salva_a_empresa_compradora_ao_aprovar_e_tira_espacos_das_pontas(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'             => 'aprovado',
            'supplier'           => 'kabum',
            'empresa_compradora' => '  Mamuth  ',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Mamuth', $item->refresh()->empresa_compradora);
    }

    public function test_nao_aprova_sem_empresa_compradora(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'   => 'aprovado',
            'supplier' => 'kabum',
        ])->assertSessionHasErrors('empresa_compradora');

        $item->refresh();
        $this->assertSame('pendente', $item->status);
        $this->assertNull($item->empresa_compradora);
    }

    public function test_empresa_so_com_espacos_conta_como_vazia_ao_aprovar(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'             => 'aprovado',
            'empresa_compradora' => '   ',
        ])->assertSessionHasErrors('empresa_compradora');

        $this->assertSame('pendente', $item->refresh()->status);
    }

    public function test_pendente_e_rejeitado_nao_exigem_empresa(): void
    {
        $pendente = PurchaseRequest::factory()->create();
        $rejeitado = PurchaseRequest::factory()->create();
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.requests.update', $pendente), ['status' => 'pendente'])
            ->assertSessionDoesntHaveErrors();
        $this->actingAs($admin)->patch(route('admin.requests.update', $rejeitado), ['status' => 'rejeitado'])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('rejeitado', $rejeitado->refresh()->status);
    }

    public function test_empresa_com_mais_de_255_caracteres_e_recusada(): void
    {
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'             => 'aprovado',
            'empresa_compradora' => str_repeat('a', 256),
        ])->assertSessionHasErrors('empresa_compradora');
    }

    public function test_item_ja_aprovado_nao_perde_a_empresa_ao_ser_reeditado_com_ela(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Ninja']);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'             => 'aprovado',
            'empresa_compradora' => 'Ninja',
            'admin_note'         => 'ok',
        ])->assertSessionDoesntHaveErrors();

        $this->assertSame('Ninja', $item->refresh()->empresa_compradora);
    }

    public function test_cartao_do_item_mostra_a_empresa_para_o_vendedor_e_para_o_admin(): void
    {
        $vendedor = User::factory()->create();
        PurchaseRequest::factory()->aprovado()->create([
            'user_id'            => $vendedor->id,
            'empresa_compradora' => 'Mamuth',
        ]);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertOk()
            ->assertSee('Empresa compradora')
            ->assertSee('Mamuth');
        $this->actingAs($this->admin())->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Mamuth');
    }

    public function test_cartao_sem_empresa_nao_inventa_valor(): void
    {
        $vendedor = User::factory()->create();
        PurchaseRequest::factory()->create(['user_id' => $vendedor->id]);

        $this->actingAs($vendedor)->get(route('requests.index'))
            ->assertOk()
            ->assertDontSee('Empresa compradora:');
    }

    private function aprovarComEmpresa(PurchaseRequest $item, string $empresa): void
    {
        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'             => 'aprovado',
            'empresa_compradora' => $empresa,
        ])->assertSessionDoesntHaveErrors();
    }

    public function test_usa_a_grafia_ja_existente_ignorando_acento_maiuscula_e_espacos(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binário']);

        foreach (['binario', 'BINÁRIO', '  Binario  ', 'bInÁrIo'] as $digitado) {
            $item = PurchaseRequest::factory()->create();
            $this->aprovarComEmpresa($item, $digitado);
            $this->assertSame('Binário', $item->refresh()->empresa_compradora, $digitado);
        }
    }

    public function test_com_variacoes_ja_gravadas_vence_a_grafia_mais_usada(): void
    {
        PurchaseRequest::factory()->aprovado()->count(2)->create(['empresa_compradora' => 'Binario']);
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binário']);

        $item = PurchaseRequest::factory()->create();
        $this->aprovarComEmpresa($item, 'BINARIO');

        $this->assertSame('Binario', $item->refresh()->empresa_compradora);
    }

    public function test_no_empate_vence_a_grafia_com_acento(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binario']);
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binário']);

        $item = PurchaseRequest::factory()->create();
        $this->aprovarComEmpresa($item, 'binario');

        $this->assertSame('Binário', $item->refresh()->empresa_compradora);
    }

    public function test_empresa_nova_fica_como_digitada_com_espacos_internos_colapsados(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Mamuth']);

        $item = PurchaseRequest::factory()->create();
        $this->aprovarComEmpresa($item, '  Ninja   Tech ');

        $this->assertSame('Ninja Tech', $item->refresh()->empresa_compradora);
    }

    public function test_empresas_diferentes_nao_se_misturam(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Mamuth']);

        $item = PurchaseRequest::factory()->create();
        $this->aprovarComEmpresa($item, 'Mamuth Tech');

        $this->assertSame('Mamuth Tech', $item->refresh()->empresa_compradora);
    }

    public function test_o_proprio_item_nao_conta_contra_si_e_a_grafia_pode_ser_corrigida(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binario']);

        $this->aprovarComEmpresa($item, 'Binário');

        $this->assertSame('Binário', $item->refresh()->empresa_compradora);
    }

    public function test_sugestoes_do_modal_saem_sem_duplicata_e_com_a_grafia_mais_usada(): void
    {
        PurchaseRequest::factory()->aprovado()->count(2)->create(['empresa_compradora' => 'Binário']);
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binario']);
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'binário']);
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Ninja']);

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()->getContent();

        preg_match('/<datalist id="empresa-options">(.*?)<\/datalist>/s', $html, $m);
        preg_match_all('/<option value="([^"]*)">/', $m[1] ?? '', $opcoes);

        $this->assertSame(['Binário', 'Ninja'], $opcoes[1]);
    }

    public function test_modal_do_admin_tem_o_campo_e_sugere_as_empresas_ja_usadas(): void
    {
        PurchaseRequest::factory()->aprovado()->create(['empresa_compradora' => 'Binário']);
        PurchaseRequest::factory()->create();

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="empresa_compradora"', $html);
        $this->assertStringContainsString('id="empresa-options"', $html);
        $this->assertStringContainsString('<option value="Binário">', $html);
    }
}
