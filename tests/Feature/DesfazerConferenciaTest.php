<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Conferência feita errado: quem tem a permissão "Pode desfazer conferência" (marcada pelo super admin
 * em Usuários) desfaz, e o item volta para Aguardando para ser conferido de novo.
 */
class DesfazerConferenciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function quemDesfaz(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'conferente', 'pode_desfazer_conferencia' => true], $attrs));
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_admin' => true, 'email' => config('admin.super_admin_email')]);
    }

    private function conferido(array $attrs = []): PurchaseRequest
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'quantity' => 10, 'status_conferencia' => 'divergente',
            'quantidade_recebida' => 8, 'observacao_conferencia' => 'Faltaram 2', 'conferente_id' => $conferente->id,
        ], $attrs));
    }

    public function test_desfaz_e_item_volta_para_aguardando_conferencia(): void
    {
        $item = $this->conferido();
        Storage::disk('public')->put('conferencia/errada.jpg', 'x');
        ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => 'conferencia/errada.jpg', 'nome_original' => 'errada.jpg']);

        $this->actingAs($this->quemDesfaz())
            ->patch(route('conferencia.desfazer', $item))
            ->assertRedirect(route('conferencia.index'))
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertNull($item->status_conferencia);
        $this->assertNull($item->quantidade_recebida);
        $this->assertNull($item->observacao_conferencia);
        $this->assertNull($item->conferente_id);

        // As fotos erradas saem do item e vão para a lixeira (30 dias), para não se misturarem com as novas.
        $this->assertSame(0, $item->fotosConferencia()->count());
        Storage::disk('public')->assertMissing('conferencia/errada.jpg');
        $this->assertNotEmpty(Storage::disk('public')->allFiles('lixeira'));

        // E aparece de novo na fila da Conferência.
        $this->actingAs($this->quemDesfaz())->get(route('conferencia.index'))->assertSee($item->product_name);
    }

    public function test_sem_a_permissao_nao_desfaz(): void
    {
        $item = $this->conferido();

        foreach ([User::factory()->create(['role' => 'conferente']), User::factory()->create(['is_admin' => true])] as $usuario) {
            $this->actingAs($usuario)->patch(route('conferencia.desfazer', $item))->assertForbidden();
        }

        $this->assertSame('divergente', $item->refresh()->status_conferencia);
    }

    public function test_super_admin_sempre_pode_desfazer(): void
    {
        $item = $this->conferido(['status_conferencia' => 'conferido_ok']);

        $this->actingAs($this->superAdmin())->patch(route('conferencia.desfazer', $item))->assertSessionHas('success');

        $this->assertNull($item->refresh()->status_conferencia);
    }

    public function test_item_que_ja_teve_entrada_nao_e_desfeito(): void
    {
        $item = $this->conferido(['status_conferencia' => 'conferido_ok', 'quantidade_entrada' => 8, 'entrada_concluida_em' => now()]);

        $this->actingAs($this->quemDesfaz())->patch(route('conferencia.desfazer', $item))->assertSessionHas('aviso');

        $this->assertSame('conferido_ok', $item->refresh()->status_conferencia);
    }

    public function test_aguardar_restante_desfeito_apaga_o_restante_e_volta_a_quantidade(): void
    {
        $item = $this->conferido([
            'status_conferencia' => 'conferido_ok', 'quantity' => 6, 'quantidade_original' => 10, 'quantidade_recebida' => 6,
            'observacao_conferencia' => 'Recebimento parcial: chegaram 6 de 10; aguardando o restante.',
        ]);
        $restante = PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'quantity' => 4, 'quantidade_original' => 10, 'restante_de_id' => $item->id, 'grupo_id' => $item->grupo_id,
        ]);

        $this->actingAs($this->quemDesfaz())->patch(route('conferencia.desfazer', $item))->assertSessionHas('success');

        $item->refresh();
        $this->assertNull($item->status_conferencia);
        $this->assertSame(10, (int) $item->quantity);
        $this->assertNull($item->quantidade_original);
        $this->assertNull(PurchaseRequest::find($restante->id));
    }

    public function test_restante_que_ja_foi_conferido_impede_desfazer_o_original(): void
    {
        $item = $this->conferido(['status_conferencia' => 'conferido_ok', 'quantity' => 6, 'quantidade_original' => 10, 'quantidade_recebida' => 6]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'quantity' => 4, 'quantidade_original' => 10, 'restante_de_id' => $item->id, 'status_conferencia' => 'conferido_ok',
        ]);

        $this->actingAs($this->quemDesfaz())->patch(route('conferencia.desfazer', $item))->assertSessionHas('aviso');

        $this->assertSame('conferido_ok', $item->refresh()->status_conferencia);
        $this->assertSame(6, (int) $item->quantity);
    }

    public function test_desfazer_um_restante_mantem_a_quantidade_original_do_pedido(): void
    {
        $original = $this->conferido(['status_conferencia' => 'conferido_ok', 'quantity' => 6, 'quantidade_original' => 10]);
        $restante = $this->conferido(['status_conferencia' => 'conferido_ok', 'quantity' => 4, 'quantidade_original' => 10, 'quantidade_recebida' => 4, 'restante_de_id' => $original->id]);

        $this->actingAs($this->quemDesfaz())->patch(route('conferencia.desfazer', $restante))->assertSessionHas('success');

        $restante->refresh();
        $this->assertNull($restante->status_conferencia);
        $this->assertSame(10, (int) $restante->quantidade_original);
    }

    public function test_botao_aparece_so_para_quem_pode(): void
    {
        $item = $this->conferido();
        $url = route('conferencia.index', ['aba' => 'conferidos']);

        $this->actingAs($this->quemDesfaz())->get($url)->assertSee(route('conferencia.desfazer', $item), false)->assertSee('Desfazer conferência');
        $this->actingAs(User::factory()->create(['role' => 'conferente']))->get($url)->assertDontSee(route('conferencia.desfazer', $item), false);
    }

    public function test_botao_some_quando_o_item_ja_teve_entrada(): void
    {
        $item = $this->conferido(['status_conferencia' => 'conferido_ok', 'entrada_concluida_em' => now()]);

        $this->actingAs($this->quemDesfaz())->get(route('conferencia.index', ['aba' => 'conferidos']))
            ->assertDontSee(route('conferencia.desfazer', $item), false);
    }

    public function test_super_admin_marca_a_permissao_no_perfil_do_usuario(): void
    {
        $kelber = User::factory()->create(['role' => 'conferente']);
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->patch(route('admin.users.updateRole', $kelber), ['perfil' => 'conferente', 'pode_desfazer_conferencia' => '1'])
            ->assertSessionHas('success');
        $this->assertTrue($kelber->refresh()->pode_desfazer_conferencia);

        // Sem a caixa marcada, a permissão sai.
        $this->actingAs($super)->patch(route('admin.users.updateRole', $kelber), ['perfil' => 'conferente']);
        $this->assertFalse($kelber->refresh()->pode_desfazer_conferencia);
    }

    public function test_tela_de_usuarios_mostra_a_caixa_e_quem_tem_a_permissao(): void
    {
        User::factory()->create(['name' => 'Kelber', 'role' => 'conferente', 'pode_desfazer_conferencia' => true]);

        $this->actingAs($this->superAdmin())->get(route('admin.users.index'))
            ->assertSee('name="pode_desfazer_conferencia"', false)
            ->assertSee('Desfaz conferência');
    }
}
