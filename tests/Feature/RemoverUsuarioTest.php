<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Remover" um usuário tira o acesso dele, mas NÃO apaga as requisições que ele fez
 * (antes o cascade do banco apagava todo o histórico do funcionário).
 */
class RemoverUsuarioTest extends TestCase
{
    use RefreshDatabase;

    private User $super;

    protected function setUp(): void
    {
        parent::setUp();
        $this->super = User::factory()->create(['is_admin' => true, 'email' => 'superadmin.teste@example.com']);
    }

    private function remover($user)
    {
        return $this->actingAs($this->super)->from(route('admin.users.index'))
            ->delete(route('admin.users.destroy', $user));
    }

    public function test_remover_mantem_as_requisicoes_do_usuario(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        PurchaseRequest::factory()->count(2)->create(['user_id' => $vendedor->id, 'requester_name' => 'Ex Funcionario']);

        $this->remover($vendedor)->assertRedirect(route('admin.users.index'))->assertSessionHas('success');

        $this->assertNull(User::find($vendedor->id));
        $this->assertSame(2, PurchaseRequest::withoutGlobalScopes()->where('user_id', $vendedor->id)->count());
    }

    public function test_usuario_removido_nao_consegue_entrar(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->remover($vendedor);
        auth()->guard('web')->logout();

        $this->post(route('login.perfil.store', 'vendedor'), ['email' => $vendedor->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_requisicoes_de_usuario_removido_continuam_aparecendo_para_o_admin(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        PurchaseRequest::factory()->create(['user_id' => $vendedor->id, 'status' => 'pendente', 'product_name' => 'Item Do Ex Funcionario']);
        $this->remover($vendedor);

        $this->actingAs($this->super)->get(route('admin.index'))->assertOk()->assertSee('Item Do Ex Funcionario');
    }

    public function test_remover_apaga_os_avisos_push_do_usuario(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        PushSubscription::create(['user_id' => $vendedor->id, 'endpoint' => 'https://push.exemplo/x', 'public_key' => 'k', 'auth_token' => 'a']);

        $this->remover($vendedor);

        $this->assertSame(0, PushSubscription::where('user_id', $vendedor->id)->count());
    }

    public function test_segundo_clique_em_remover_nao_da_404(): void
    {
        $vendedor = User::factory()->create(['role' => null]);
        $this->remover($vendedor);

        $this->remover($vendedor->id)->assertRedirect(route('admin.users.index'))->assertSessionHas('success');
    }

    public function test_nao_remove_a_si_mesmo(): void
    {
        $this->remover($this->super)->assertSessionHas('error');

        $this->assertNotNull(User::find($this->super->id));
    }

    public function test_so_o_super_admin_remove(): void
    {
        $outroAdmin = User::factory()->create(['is_admin' => true]);
        $vendedor = User::factory()->create(['role' => null]);

        $this->actingAs($outroAdmin)->delete(route('admin.users.destroy', $vendedor))->assertForbidden();

        $this->assertNotNull(User::find($vendedor->id));
    }
}
