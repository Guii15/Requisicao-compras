# Atribuir Perfil de Usuário na Tela de Usuários do Admin

> Referência: `docs/PLANO-CONFERENCIA.md` (roles `conferente`/`entrada` criadas
> nos sub-projetos anteriores). Gap identificado na revisão final do
> sub-projeto 8 (Tela de Entrada): não existia nenhuma UI pra atribuir esses
> papéis, só `php artisan tinker` manualmente.

## 1. Objetivo desta fatia

Hoje a tela `/admin/usuarios` só permite criar usuário com um checkbox
"Conceder acesso Admin" (`is_admin`), e não existe nenhuma forma de definir
ou alterar `role` (`conferente`/`entrada`) pela interface — é preciso mexer
direto no banco. Esta fatia adiciona um seletor de perfil unificado na
criação de usuário, corrige a exibição da coluna "Perfil" (hoje só mostra
"Admin"/"Usuário", ignorando `role`), e adiciona a capacidade de editar o
perfil de um usuário já existente.

## 2. Contexto relevante do sistema atual

- `User::isAdmin()` = `is_admin` bool. `User::isConferente()` = `role ===
  'conferente' || isAdmin()`. `User::isEntrada()` = `role === 'entrada' ||
  isAdmin()`. `User::isVendedor()` = `!isAdmin() && role === null`.
- `AdminController::users()` (`app/Http/Controllers/AdminController.php`)
  lista todos os usuários pra `admin/users.blade.php`.
- `AdminController::storeUser()` valida e cria usuário com `name`, `email`,
  `password`, `is_admin` (boolean). Não seta `role`.
- `AdminController::destroyUser()` e `resetPassword()` já têm o padrão de
  bloquear ação na própria conta (`if ($user->id === auth()->id())`).
- `resources/views/admin/users.blade.php`: tabela com colunas Nome / E-mail
  / Perfil / Ações. Coluna Perfil hoje é `@if($u->is_admin)` binário. Botão
  "Senha" abre modal (`modal-senha-{{ $u->id }}`) com formulário PATCH pra
  `admin.users.resetPassword`. Formulário "Novo Usuário" tem checkbox
  `is_admin`.
- `routes/web.php`: grupo `admin.` já tem `users.index`, `users.store`,
  `users.destroy`, `users.resetPassword`.

## 3. Decisões tomadas no brainstorming

1. **Seletor único de perfil**, não dois controles separados. Um
   `<select name="perfil">` com 4 opções mutuamente exclusivas: `vendedor`
   (padrão), `conferente`, `entrada`, `admin`. O backend traduz pra
   `is_admin`/`role`:
   - `admin` → `is_admin = true`, `role = null`
   - `conferente` → `is_admin = false`, `role = 'conferente'`
   - `entrada` → `is_admin = false`, `role = 'entrada'`
   - `vendedor` → `is_admin = false`, `role = null`
2. **Editar perfil de usuário existente**: novo botão "Perfil" ao lado do
   botão "Senha" já existente, mesmo padrão visual (abre modal). O modal
   vem pré-preenchido com o perfil atual do usuário.
3. **Proteção de auto-edição**: o botão "Perfil" não aparece na própria
   linha do admin logado (mesmo padrão já usado no botão "Remover"), pra
   evitar que ele se rebaixe e perca acesso ao próprio painel.
4. **Coluna "Perfil" corrigida**: mostra a etiqueta certa pra cada um dos 4
   estados (hoje só distingue Admin/Usuário, ignorando `role`).

## 4. Mudanças

### 4.1 `app/Http/Controllers/AdminController.php`

**`storeUser()`** — troca a validação de `is_admin` por `perfil`:

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

**`updateRole()`** (novo método):

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

Note: a checagem `$user->id === auth()->id()` no backend é uma segunda
camada de defesa — a UI já esconde o botão pra própria linha, mas a rota
precisa recusar mesmo que a requisição seja forjada.

### 4.2 `routes/web.php`

Adiciona ao grupo `admin.` (junto dos outros `users.*`):

```php
Route::patch('/usuarios/{user}/perfil', [AdminController::class, 'updateRole'])->name('users.updateRole');
```

### 4.3 `resources/views/admin/users.blade.php`

- **Coluna "Perfil" da tabela**: troca o `@if($u->is_admin)` binário por
  uma cascata com as 4 etiquetas:
  ```blade
  @if($u->is_admin)
      <span style="background:#ede9fe; color:#7c3aed; ...">Admin</span>
  @elseif($u->role === 'conferente')
      <span style="background:#dbeafe; color:#2563eb; ...">Conferente</span>
  @elseif($u->role === 'entrada')
      <span style="background:#fef3c7; color:#d97706; ...">Entrada</span>
  @else
      <span style="background:#f3f4f6; color:#6b7280; ...">Vendedor</span>
  @endif
  ```
  (cores livres, mas cada perfil com uma cor própria, distinta das outras
  telas pra não confundir com status de requisição).
- **Botão "Perfil"** ao lado do botão "Senha" existente, só quando
  `$u->id !== auth()->id()` (mesma condição já usada no botão "Remover").
  Abre `modal-perfil-{{ $u->id }}`.
- **Modal "Perfil"** (novo, mesmo padrão visual do `modal-senha-{{ $u->id
  }}`): formulário PATCH pra `admin.users.updateRole`, com um `<select
  name="perfil">` de 4 `<option>`s, pré-selecionado conforme o perfil atual
  do usuário (calculado com a mesma lógica da coluna da tabela).
- **Formulário "Novo Usuário"**: troca o checkbox `is_admin` pelo mesmo
  `<select name="perfil">` (4 opções, `vendedor` selecionado por padrão).

## 5. Fora de escopo

- Não altera o comportamento de `isAdmin()`/`isConferente()`/`isEntrada()`/
  `isVendedor()` no model `User` — só como esses valores são atribuídos
  pela UI.
- Não adiciona um 5º perfil ou hierarquia de permissões nova.
- Não migra usuários existentes — eles já têm `is_admin`/`role` corretos no
  banco (setados via tinker), essa fatia só afeta como isso é editado daqui
  pra frente.

## 6. Plano de teste (local apenas)

1. Feature tests em `tests/Feature/AdminControllerTest.php` (arquivo já
   existente):
   - `storeUser()` com `perfil=admin` cria usuário com `is_admin=true`,
     `role=null`.
   - `storeUser()` com `perfil=conferente` cria `is_admin=false`,
     `role='conferente'`.
   - `storeUser()` com `perfil=entrada` cria `is_admin=false`,
     `role='entrada'`.
   - `storeUser()` com `perfil=vendedor` cria `is_admin=false`,
     `role=null`.
   - `storeUser()` rejeita `perfil` ausente ou inválido.
   - `updateRole()` muda o perfil de um usuário existente corretamente
     pros 4 casos.
   - `updateRole()` retorna 302 com erro (não altera nada) quando o admin
     tenta mudar o próprio perfil.
   - `updateRole()` é bloqueado (403) pra quem não é admin (mesmo padrão
     dos outros testes de acesso do `AdminMiddleware`).
2. Feature test confirmando que a tabela mostra a etiqueta certa pra cada
   um dos 4 perfis (`assertSee` com o texto exato de cada badge).
3. Confirmar visualmente em local: criar um usuário conferente pela tela,
   depois mudar ele pra entrada, depois mudar de volta — ver a etiqueta da
   tabela mudar em cada passo.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
