# Atribuir Perfil de Usuário Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar um seletor de perfil (Vendedor/Conferente/Entrada/Admin) na tela de Usuários do Admin, tanto na criação de usuário quanto na edição de um usuário já existente, e corrigir a coluna "Perfil" da tabela pra mostrar os 4 estados corretamente.

**Architecture:** Um único campo de formulário `perfil` (string: `vendedor`/`conferente`/`entrada`/`admin`) é traduzido no controller pros campos já existentes do model `User` (`is_admin` bool + `role` string nullable). Nenhuma migration nova — os campos já existem desde os sub-projetos anteriores.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`php artisan test`), SQLite (dev local).

## Global Constraints

- Mapeamento perfil → campos (usado em `storeUser()` e `updateRole()`):
  `admin` → `is_admin=true, role=null`; `conferente` → `is_admin=false,
  role='conferente'`; `entrada` → `is_admin=false, role='entrada'`;
  `vendedor` → `is_admin=false, role=null`.
- `updateRole()` bloqueia o próprio admin de mudar seu próprio perfil
  (`$user->id === auth()->id()` → `back()->with('error', ...)`, sem alterar
  nada), mesmo padrão de `destroyUser()`.
- O botão "Perfil" na tabela só aparece quando `$u->id !== auth()->id()`,
  mesma condição já usada no botão "Remover" em
  `resources/views/admin/users.blade.php`.
- Toda a área `/admin/*` já está atrás de `AdminMiddleware` (grupo de rota
  em `routes/web.php`) — nenhuma mudança de middleware nesta fatia.

---

### Task 1: Backend — mapeamento de perfil e ação de editar

**Files:**
- Modify: `app/Http/Controllers/AdminController.php` (método `storeUser()`,
  novo método `updateRole()`)
- Modify: `routes/web.php` (nova rota `admin.users.updateRole`)
- Test: `tests/Feature/AdminUserManagementTest.php` (novo arquivo — não
  existe nenhum teste de gestão de usuários do Admin hoje)

**Interfaces:**
- Produces: rota nomeada `admin.users.updateRole` (`PATCH
  /admin/usuarios/{user}/perfil`), aceitando campo `perfil` no body.
  `AdminController::storeUser()` passa a exigir `perfil` em vez de
  `is_admin` no body do POST.

- [ ] **Step 1: Escrever os testes de `storeUser()` com o campo `perfil`**

Crie `tests/Feature/AdminUserManagementTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validStoreUserPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Usuário Teste',
            'email' => 'usuario.teste@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'perfil' => 'vendedor',
        ], $overrides);
    }

    public function test_store_user_with_perfil_admin_sets_is_admin_true_and_role_null(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'novo.admin@example.com',
            'perfil' => 'admin',
        ]));

        $criado = User::where('email', 'novo.admin@example.com')->first();
        $this->assertNotNull($criado);
        $this->assertTrue($criado->is_admin);
        $this->assertNull($criado->role);
    }

    public function test_store_user_with_perfil_conferente_sets_role_conferente(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'novo.conferente@example.com',
            'perfil' => 'conferente',
        ]));

        $criado = User::where('email', 'novo.conferente@example.com')->first();
        $this->assertFalse($criado->is_admin);
        $this->assertSame('conferente', $criado->role);
    }

    public function test_store_user_with_perfil_entrada_sets_role_entrada(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'nova.entrada@example.com',
            'perfil' => 'entrada',
        ]));

        $criado = User::where('email', 'nova.entrada@example.com')->first();
        $this->assertFalse($criado->is_admin);
        $this->assertSame('entrada', $criado->role);
    }

    public function test_store_user_with_perfil_vendedor_sets_is_admin_false_and_role_null(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'novo.vendedor@example.com',
            'perfil' => 'vendedor',
        ]));

        $criado = User::where('email', 'novo.vendedor@example.com')->first();
        $this->assertFalse($criado->is_admin);
        $this->assertNull($criado->role);
    }

    public function test_store_user_rejects_missing_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $payload = $this->validStoreUserPayload();
        unset($payload['perfil']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $payload);

        $response->assertSessionHasErrors('perfil');
    }

    public function test_store_user_rejects_invalid_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $this->validStoreUserPayload([
            'email' => 'invalido@example.com',
            'perfil' => 'gerente',
        ]));

        $response->assertSessionHasErrors('perfil');
        $this->assertNull(User::where('email', 'invalido@example.com')->first());
    }
}
```

- [ ] **Step 2: Rodar os testes de `storeUser()` e confirmar que falham**

Run: `php artisan test --filter=AdminUserManagementTest`
Expected: FAIL — hoje `storeUser()` não valida nem usa `perfil`, então os
usuários criados terão `is_admin=false` e `role=null` sempre, quebrando os
testes de `admin` e `conferente`/`entrada`; e o teste de `perfil` ausente
falha porque a rota aceita a requisição normalmente (sem essa regra de
validação).

- [ ] **Step 3: Atualizar `storeUser()` em `app/Http/Controllers/AdminController.php`**

Localize o método `storeUser()` atual (recebe `name`, `email`, `password`,
`is_admin`) e substitua por:

```php
    public function storeUser(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'perfil'                => 'required|in:vendedor,conferente,entrada,admin',
        ], [
            'name.required'         => 'O nome é obrigatório.',
            'email.required'        => 'O e-mail é obrigatório.',
            'email.unique'          => 'Já existe um usuário com este e-mail.',
            'password.required'     => 'A senha é obrigatória.',
            'password.min'          => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed'    => 'As senhas não coincidem.',
            'perfil.required'       => 'Selecione um perfil.',
            'perfil.in'             => 'Perfil inválido.',
        ]);

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
            'is_admin' => $request->perfil === 'admin',
            'role'     => in_array($request->perfil, ['conferente', 'entrada'], true) ? $request->perfil : null,
        ]);

        return back()->with('success', 'Usuário criado com sucesso!');
    }
```

- [ ] **Step 4: Rodar os testes de `storeUser()` de novo e confirmar que passam**

Run: `php artisan test --filter=AdminUserManagementTest`
Expected: os 6 testes já escritos passam (os de `updateRole()` ainda não
existem).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AdminController.php tests/Feature/AdminUserManagementTest.php
git commit -m "feat: adiciona campo perfil na criacao de usuario do admin"
```

- [ ] **Step 6: Escrever os testes de `updateRole()`**

Adicione ao final de `tests/Feature/AdminUserManagementTest.php`, antes do
`}` de fechamento da classe:

```php

    public function test_update_role_changes_existing_user_to_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'admin',
        ]);

        $response->assertRedirect();
        $fresh = $alvo->fresh();
        $this->assertTrue($fresh->is_admin);
        $this->assertNull($fresh->role);
    }

    public function test_update_role_changes_existing_user_to_conferente(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'conferente',
        ]);

        $fresh = $alvo->fresh();
        $this->assertFalse($fresh->is_admin);
        $this->assertSame('conferente', $fresh->role);
    }

    public function test_update_role_changes_existing_user_to_entrada(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'entrada',
        ]);

        $fresh = $alvo->fresh();
        $this->assertFalse($fresh->is_admin);
        $this->assertSame('entrada', $fresh->role);
    }

    public function test_update_role_changes_existing_user_back_to_vendedor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => 'conferente']);

        $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'vendedor',
        ]);

        $fresh = $alvo->fresh();
        $this->assertFalse($fresh->is_admin);
        $this->assertNull($fresh->role);
    }

    public function test_update_role_rejects_invalid_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'gerente',
        ]);

        $response->assertSessionHasErrors('perfil');
        $this->assertNull($alvo->fresh()->role);
    }

    public function test_update_role_blocks_admin_from_changing_own_profile(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role' => null]);

        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $admin), [
            'perfil' => 'conferente',
        ]);

        $response->assertRedirect();
        $fresh = $admin->fresh();
        $this->assertTrue($fresh->is_admin);
        $this->assertNull($fresh->role);
    }

    public function test_update_role_requires_admin_access(): void
    {
        $vendedor = User::factory()->create(['is_admin' => false, 'role' => null]);
        $alvo = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($vendedor)->patch(route('admin.users.updateRole', $alvo), [
            'perfil' => 'conferente',
        ]);

        $response->assertForbidden();
        $this->assertNull($alvo->fresh()->role);
    }
```

- [ ] **Step 7: Rodar os testes de `updateRole()` e confirmar que falham**

Run: `php artisan test --filter=AdminUserManagementTest`
Expected: FAIL — a rota `admin.users.updateRole` ainda não existe (erro de
rota não encontrada / `RouteNotFoundException` ao chamar `route(...)`).

- [ ] **Step 8: Adicionar o método `updateRole()` em `app/Http/Controllers/AdminController.php`**

Adicione logo depois do método `destroyUser()`:

```php
    public function updateRole(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode alterar seu próprio perfil.');
        }

        $request->validate([
            'perfil' => 'required|in:vendedor,conferente,entrada,admin',
        ], [
            'perfil.required' => 'Selecione um perfil.',
            'perfil.in'        => 'Perfil inválido.',
        ]);

        $user->update([
            'is_admin' => $request->perfil === 'admin',
            'role'     => in_array($request->perfil, ['conferente', 'entrada'], true) ? $request->perfil : null,
        ]);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }
```

- [ ] **Step 9: Adicionar a rota em `routes/web.php`**

Dentro do grupo `Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () { ... })`,
logo abaixo da linha `Route::patch('/usuarios/{user}/senha', [AdminController::class, 'resetPassword'])->name('users.resetPassword');`,
adicione:

```php
    Route::patch('/usuarios/{user}/perfil', [AdminController::class, 'updateRole'])->name('users.updateRole');
```

- [ ] **Step 10: Rodar os testes de `updateRole()` de novo e confirmar que passam**

Run: `php artisan test --filter=AdminUserManagementTest`
Expected: os 13 testes do arquivo passam (6 de `storeUser()` + 7 de
`updateRole()`).

- [ ] **Step 11: Rodar a suíte inteira pra garantir que nada quebrou**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes de sempre (`RegistrationTest` x2,
`ExampleTest` x1, não relacionadas a esta mudança), nenhuma falha nova.

- [ ] **Step 12: Commit**

```bash
git add app/Http/Controllers/AdminController.php routes/web.php tests/Feature/AdminUserManagementTest.php
git commit -m "feat: adiciona rota e acao de editar perfil de usuario existente"
```

---

### Task 2: Frontend — seletor de perfil, badge corrigido e modal de edição

**Files:**
- Modify: `resources/views/admin/users.blade.php`
- Test: `tests/Feature/AdminUserManagementTest.php` (mesmo arquivo da Task 1)

**Interfaces:**
- Consumes: rota `admin.users.updateRole` e campo `perfil` de `storeUser()`
  (Task 1).
- Produces: nada consumido por tasks futuras — esta é a última task da
  fatia.

- [ ] **Step 1: Escrever os testes de UI (badge da tabela e formulário)**

Adicione ao final de `tests/Feature/AdminUserManagementTest.php`, antes do
`}` de fechamento da classe:

```php

    public function test_users_index_shows_correct_badge_for_each_perfil(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Logado']);
        User::factory()->create(['is_admin' => false, 'role' => 'conferente', 'name' => 'Fulano Conferente']);
        User::factory()->create(['is_admin' => false, 'role' => 'entrada', 'name' => 'Fulano Entrada']);
        User::factory()->create(['is_admin' => false, 'role' => null, 'name' => 'Fulano Vendedor']);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee('>Admin<', false);
        $response->assertSee('>Conferente<', false);
        $response->assertSee('>Entrada<', false);
        $response->assertSee('>Vendedor<', false);
    }

    public function test_users_index_does_not_show_perfil_button_on_own_row(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $html = $response->getContent();
        $this->assertStringNotContainsString("modal-perfil-{$admin->id}", $html);
    }

    public function test_users_index_shows_perfil_button_on_other_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $outro = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee("modal-perfil-{$outro->id}", false);
    }

    public function test_create_user_form_has_perfil_select_with_four_options(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertSee('name="perfil"', false);
        $response->assertSee('value="vendedor"', false);
        $response->assertSee('value="conferente"', false);
        $response->assertSee('value="entrada"', false);
        $response->assertSee('value="admin"', false);
    }
```

- [ ] **Step 2: Rodar os testes de UI e confirmar que falham**

Run: `php artisan test --filter=AdminUserManagementTest`
Expected: FAIL nos 4 testes novos — a view ainda não tem o `<select
name="perfil">`, ainda mostra só "Admin"/"Usuário" na tabela, e não existe
nenhum `modal-perfil-{{ $u->id }}`.

- [ ] **Step 3: Atualizar a coluna "Perfil" da tabela em `resources/views/admin/users.blade.php`**

Troque este trecho:

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                @if($u->is_admin)
                                    <span style="background:#ede9fe; color:#7c3aed; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Admin</span>
                                @else
                                    <span style="background:#f3f4f6; color:#6b7280; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Usuário</span>
                                @endif
                            </td>
```

Por:

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                @if($u->is_admin)
                                    <span style="background:#ede9fe; color:#7c3aed; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Admin</span>
                                @elseif($u->role === 'conferente')
                                    <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Conferente</span>
                                @elseif($u->role === 'entrada')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Entrada</span>
                                @else
                                    <span style="background:#f3f4f6; color:#6b7280; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Vendedor</span>
                                @endif
                            </td>
```

- [ ] **Step 4: Adicionar o botão "Perfil" ao lado do botão "Senha"**

Troque este trecho (a célula de ações que hoje só tem o botão "Senha"):

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-senha-{{ $u->id }}').style.display='flex'"
                                        style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;"
                                        onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                                    Senha
                                </button>
                            </td>
```

Por:

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                <div style="display:flex; gap:6px; justify-content:center;">
                                    <button onclick="document.getElementById('modal-senha-{{ $u->id }}').style.display='flex'"
                                            style="background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;"
                                            onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='#eff6ff'">
                                        Senha
                                    </button>
                                    @if($u->id !== auth()->id())
                                        <button onclick="document.getElementById('modal-perfil-{{ $u->id }}').style.display='flex'"
                                                style="background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;"
                                                onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                                            Perfil
                                        </button>
                                    @endif
                                </div>
                            </td>
```

- [ ] **Step 5: Adicionar o modal "Perfil" logo depois do modal "Senha" existente**

Localize o fechamento do bloco do modal de senha (a `</div>` que fecha
`modal-senha-{{ $u->id }}`, logo antes de `@empty`) e adicione o novo
modal na sequência:

```blade
                        {{-- Modal editar perfil --}}
                        @if($u->id !== auth()->id())
                        <div id="modal-perfil-{{ $u->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:400px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Editar Perfil</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $u->name }}</p>
                                @php
                                    $perfilAtual = $u->is_admin ? 'admin' : ($u->role ?? 'vendedor');
                                @endphp
                                <form method="POST" action="{{ route('admin.users.updateRole', $u) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div style="margin-bottom:20px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Perfil</label>
                                        <select name="perfil" style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                            <option value="vendedor" {{ $perfilAtual === 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                                            <option value="conferente" {{ $perfilAtual === 'conferente' ? 'selected' : '' }}>Conferente</option>
                                            <option value="entrada" {{ $perfilAtual === 'entrada' ? 'selected' : '' }}>Entrada</option>
                                            <option value="admin" {{ $perfilAtual === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    </div>
                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-perfil-{{ $u->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#1d4ed8); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
```

- [ ] **Step 6: Trocar o checkbox "Conceder acesso Admin" pelo seletor de perfil no formulário "Novo Usuário"**

Troque este trecho:

```blade
                <div style="margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; color:#374151;">
                        <input type="checkbox" name="is_admin" value="1" {{ old('is_admin') ? 'checked' : '' }}
                               style="width:16px; height:16px; accent-color:#05018D; cursor:pointer;">
                        Conceder acesso Admin
                    </label>
                </div>
```

Por:

```blade
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:12px; font-weight:600; color:#374151; margin-bottom:5px;">Perfil</label>
                    <select name="perfil" style="width:100%; border:1px solid #d1d5db; border-radius:7px; padding:9px 12px; font-size:14px; box-sizing:border-box;">
                        <option value="vendedor" {{ old('perfil', 'vendedor') === 'vendedor' ? 'selected' : '' }}>Vendedor</option>
                        <option value="conferente" {{ old('perfil') === 'conferente' ? 'selected' : '' }}>Conferente</option>
                        <option value="entrada" {{ old('perfil') === 'entrada' ? 'selected' : '' }}>Entrada</option>
                        <option value="admin" {{ old('perfil') === 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                </div>
```

- [ ] **Step 7: Rodar os testes de UI de novo e confirmar que passam**

Run: `php artisan test --filter=AdminUserManagementTest`
Expected: os 17 testes do arquivo passam (6 + 7 da Task 1 + 4 novos).

- [ ] **Step 8: Rodar a suíte inteira**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes de sempre, nenhuma nova.

- [ ] **Step 9: Limpar cache de views**

Run: `php artisan view:clear`

- [ ] **Step 10: Commit**

```bash
git add resources/views/admin/users.blade.php tests/Feature/AdminUserManagementTest.php
git commit -m "feat: adiciona selecao de perfil no formulario e modal de edicao de usuario"
```
