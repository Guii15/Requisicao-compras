<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * O perfil Entrada também cria e acompanha as PRÓPRIAS requisições (como um vendedor),
 * sem perder o que já faz (entrada, conferência, coleta) e sem abrir nada novo para os outros perfis.
 */
class EntradaRequisitaTest extends TestCase
{
    use RefreshDatabase;

    private function entrada(): User
    {
        return User::factory()->create(['role' => 'entrada', 'is_admin' => false]);
    }

    private function dados(array $extra = []): array
    {
        return array_merge([
            'requester_name' => 'Gustavo',
            'urgency'        => 'media',
            'reason'         => 'Reposição',
            'justification'  => 'Preciso para o estoque',
            'tipo_entrega'   => 'estoque',
            'products'       => [['product_name' => 'Cabo HDMI', 'quantity' => 2]],
        ], $extra);
    }

    public function test_entrada_acessa_lista_e_tela_de_nova_requisicao(): void
    {
        $entrada = $this->entrada();

        $this->actingAs($entrada)->get(route('requests.index'))->assertOk();
        $this->actingAs($entrada)->get(route('requests.create'))->assertOk();
    }

    public function test_entrada_cria_requisicao_pendente_com_o_proprio_user_id(): void
    {
        Mail::fake();
        $entrada = $this->entrada();

        $this->actingAs($entrada)
            ->post(route('requests.store'), $this->dados())
            ->assertRedirect(route('requests.index'));

        $this->assertDatabaseHas('purchase_requests', [
            'user_id'      => $entrada->id,
            'product_name' => 'Cabo HDMI',
            'status'       => 'pendente',
        ]);
    }

    public function test_entrada_ve_so_as_proprias_requisicoes(): void
    {
        $entrada = $this->entrada();
        $outro = User::factory()->create(['role' => null]);
        PurchaseRequest::factory()->create(['user_id' => $entrada->id, 'product_name' => 'ITEM-DO-GUSTAVO']);
        PurchaseRequest::factory()->create(['user_id' => $outro->id, 'product_name' => 'ITEM-DE-OUTRO']);

        $resposta = $this->actingAs($entrada)->get(route('requests.index'));

        $resposta->assertSee('ITEM-DO-GUSTAVO');
        $resposta->assertDontSee('ITEM-DE-OUTRO');
    }

    public function test_entrada_nao_edita_nem_exporta_requisicao_de_outro_usuario(): void
    {
        $entrada = $this->entrada();
        $outro = User::factory()->create(['role' => null]);
        $alheia = PurchaseRequest::factory()->create(['user_id' => $outro->id, 'status' => 'pendente']);

        $this->actingAs($entrada)->get(route('requests.edit', $alheia))->assertForbidden();
        $this->actingAs($entrada)->patch(route('requests.update', $alheia), ['product_name' => 'X'])->assertForbidden();
        $this->actingAs($entrada)->get(route('requests.export', $alheia))->assertForbidden();
    }

    public function test_entrada_edita_e_exporta_a_propria_requisicao(): void
    {
        $entrada = $this->entrada();
        $propria = PurchaseRequest::factory()->create(['user_id' => $entrada->id, 'status' => 'pendente']);

        $this->actingAs($entrada)->get(route('requests.edit', $propria))->assertOk();
        $this->actingAs($entrada)->get(route('requests.export', $propria))->assertOk();
    }

    public function test_entrada_continua_caindo_na_tela_de_entrada_no_dashboard(): void
    {
        $this->actingAs($this->entrada())
            ->get(route('dashboard'))
            ->assertRedirect(route('entrada.index'));
    }

    public function test_entrada_continua_acessando_entrada_e_conferencia(): void
    {
        $entrada = $this->entrada();

        $this->actingAs($entrada)->get(route('entrada.index'))->assertOk();
        $this->actingAs($entrada)->get(route('conferencia.index'))->assertOk();
    }

    public function test_entrada_continua_sem_acesso_ao_admin(): void
    {
        $this->actingAs($this->entrada())->get(route('admin.index'))->assertForbidden();
    }

    public function test_conferente_financeiro_e_rma_continuam_sem_requisitar(): void
    {
        foreach (['conferente', 'financeiro', 'rma'] as $perfil) {
            $user = User::factory()->create(['role' => $perfil, 'is_admin' => false]);

            $this->actingAs($user)->get(route('requests.index'))->assertForbidden();
            $this->actingAs($user)->get(route('requests.create'))->assertForbidden();
            $this->actingAs($user)->post(route('requests.store'), $this->dados())->assertForbidden();
        }
    }

    public function test_admin_continua_fora_da_lista_do_vendedor_mas_cria(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role' => null]);

        $this->actingAs($admin)->get(route('requests.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('requests.create'))->assertOk();
    }

    public function test_menu_do_entrada_mostra_minhas_requisicoes_e_dos_outros_nao_muda(): void
    {
        $nav = fn (User $u, string $rota) => (function ($html) {
            $ini = strpos($html, '<nav');
            return substr($html, $ini, strpos($html, '</nav>', $ini) - $ini);
        })($this->actingAs($u)->get(route($rota))->getContent());

        $this->assertStringContainsString('Minhas Requisições', $nav($this->entrada(), 'entrada.index'));
        $this->assertStringNotContainsString('Minhas Requisições', $nav(User::factory()->create(['role' => 'conferente']), 'conferencia.index'));
        $this->assertStringNotContainsString('Minhas Requisições', $nav(User::factory()->create(['is_admin' => true]), 'admin.index'));
    }

    public function test_login_do_entrada_volta_para_a_lista_de_requisicoes_se_foi_la_que_tentou_abrir(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada', 'is_admin' => false, 'password' => 'senha-segura-123']);

        $this->withSession(['url.intended' => url('/requisicoes')])
            ->post('/login/entrada', ['email' => $entrada->email, 'password' => 'senha-segura-123'])
            ->assertRedirect(url('/requisicoes'));
    }
}
