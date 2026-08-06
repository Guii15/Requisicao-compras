# Tela de Pendências Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Criar uma tela pro admin (que já faz o papel de comprador neste
sistema) ver e resolver itens divergentes de estoque presos na conferência —
aceitando mesmo assim ou cancelando o item — e propagar o novo status
`cancelado` pras duas telas que já mostram `status_conferencia` hoje.

**Architecture:** Novo `PendenciaController` (index + resolver) e nova view
`resources/views/pendencias/index.blade.php`, seguindo exatamente o mesmo
padrão visual/técnico já usado em `conferencia/index.blade.php` (tabela
desktop + cards mobile com breakpoint `@media (max-width: 768px)`, modal por
item com toggle JS de campo obrigatório). Duas telas já existentes
(`requests/index.blade.php` e `conferencia/index.blade.php`) recebem um novo
caso `cancelado` nas suas etiquetas de status.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`php artisan test`), SQLite (dev local).

## Global Constraints

- Acesso à Tela de Pendências: só `AdminMiddleware` (mesmo guarda do painel
  Admin) — nenhum outro papel acessa.
- Lista mostra só `status='aprovado'` AND `status_conferencia='divergente'`
  AND `tipo_entrega='estoque'`.
- Duas ações apenas: `aceitar` (→ `status_conferencia='avancado_mesmo_assim'`,
  observação opcional) e `cancelar` (→ `status_conferencia='cancelado'`,
  observação **obrigatória**).
- A observação da resolução é **anexada** a `admin_note` (nunca sobrescreve
  a nota de aprovação original).
- Trava 409 no `resolver()`: só processa se, no momento do PATCH, o item
  ainda for `status='aprovado'` + `status_conferencia='divergente'` +
  `tipo_entrega='estoque'`.
- `cancelado` é só mais um valor de string livre em `status_conferencia`
  (sem migration) — mas precisa de um novo `@elseif` nas cascatas de
  `requests/index.blade.php` e `conferencia/index.blade.php` (nunca usar
  `@else` genérico pra esse campo, já que ele pode ganhar mais valores no
  futuro).
- Nenhuma mudança em `ConferenciaController` nem em `AdminController`.
- Sem aba de "pendências resolvidas" nesta fatia — a lista se limpa sozinha.

---

### Task 1: Rota, controller (index) e tela de listagem

**Files:**
- Create: `app/Http/Controllers/PendenciaController.php`
- Create: `resources/views/pendencias/index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Test: `tests/Feature/PendenciaControllerTest.php` (novo)

**Interfaces:**
- Consumes: `PurchaseRequest` model (campos `status`, `status_conferencia`,
  `tipo_entrega`, `quantity`, `quantidade_recebida`, `observacao_conferencia`,
  `product_name`, `requester_name`, `supplier`), relações já existentes
  `conferente()` (belongsTo User) e `fotosConferencia()` (hasMany
  ConferenciaFoto, campo `caminho_arquivo`). `AdminMiddleware` já existente
  em `app/Http/Middleware/AdminMiddleware.php`.
- Produces: rota nomeada `pendencias.index` (GET `/pendencias`), usada pela
  Task 2 pro redirect após resolver.

- [ ] **Step 1: Escrever os testes de acesso e listagem que falham**

Crie `tests/Feature/PendenciaControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendenciaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('pendencias.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('pendencias.index'));

        $response->assertForbidden();
    }

    public function test_conferente_is_forbidden(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('pendencias.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_divergente_estoque_aprovado(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'estoque',
            'product_name' => 'Pendencia Real',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque',
            'product_name' => 'Ainda Aguardando',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'tipo_entrega' => 'estoque',
            'product_name' => 'Ja Conferido OK',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'tipo_entrega' => 'estoque',
            'product_name' => 'Ja Avancado',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'entrega_direta',
            'product_name' => 'Divergente Mas Dropship',
        ]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertSee('Pendencia Real');
        $response->assertDontSee('Ainda Aguardando');
        $response->assertDontSee('Ja Conferido OK');
        $response->assertDontSee('Ja Avancado');
        $response->assertDontSee('Divergente Mas Dropship');
    }

    public function test_index_shows_conferente_name_and_observacao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conferente = User::factory()->create(['role' => 'conferente', 'name' => 'Fulano Conferente']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'tipo_entrega' => 'estoque',
            'conferente_id' => $conferente->id,
            'observacao_conferencia' => 'Chegou quebrado, faltam 2 unidades',
            'quantity' => 10,
            'quantidade_recebida' => 8,
        ]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertSee('Fulano Conferente');
        $response->assertSee('Chegou quebrado, faltam 2 unidades');
        $response->assertSee('10');
        $response->assertSee('8');
    }

    public function test_index_shows_empty_message_when_no_pendencias(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('pendencias.index'));

        $response->assertSee('Nenhuma pendência no momento.');
    }
}
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=PendenciaControllerTest`
Expected: FAIL — rota `pendencias.index` não existe ainda.

- [ ] **Step 3: Criar o controller**

Crie `app/Http/Controllers/PendenciaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class PendenciaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
            ->where('status', 'aprovado')
            ->where('status_conferencia', 'divergente')
            ->where('tipo_entrega', 'estoque')
            ->latest()
            ->paginate(15);

        return view('pendencias.index', compact('requests'));
    }
}
```

- [ ] **Step 4: Adicionar a rota**

Em `routes/web.php`, adicione o import no topo (junto dos outros `use App\Http\Controllers\...`):

```php
use App\Http\Controllers\PendenciaController;
```

E adicione este grupo depois do grupo `conferencia` (depois da linha
`});` que fecha o grupo `Route::middleware(['auth', ConferenteMiddleware::class])->prefix('conferencia')...`, antes de `require __DIR__.'/auth.php';`):

```php
Route::middleware(['auth', AdminMiddleware::class])->prefix('pendencias')->name('pendencias.')->group(function () {
    Route::get('/', [PendenciaController::class, 'index'])->name('index');
});
```

- [ ] **Step 5: Criar a view de listagem**

Crie `resources/views/pendencias/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')

<style>
.pend-mobile-cards { display: none; }
@media (max-width: 768px) {
    .pend-desktop-table { display: none; }
    .pend-mobile-cards  { display: block; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Pendências</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Itens divergentes de estoque aguardando sua decisão</p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            <strong>Não foi possível resolver a pendência:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="pend-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solic. / Receb.</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Observação do Conferente</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Foto</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->product_name }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; color:#374151;">{{ $req->quantity }} / {{ $req->quantidade_recebida }}</td>
                            <td style="padding:12px 16px; font-size:13px; color:#374151;">{{ $req->observacao_conferencia }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->fotosConferencia->first())
                                    <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#1d4ed8; font-size:12px; text-decoration:underline;">Ver foto</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-resolver-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Resolver
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma pendência no momento.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    <div class="pend-mobile-cards">
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:10px;">{{ $req->product_name }}</div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px; margin-bottom:10px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->requester_name ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Qtd Solic. / Receb.</span>
                        <div style="font-weight:700; color:#374151;">{{ $req->quantity }} / {{ $req->quantidade_recebida }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Foto</span>
                        <div>
                            @if($req->fotosConferencia->first())
                                <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#1d4ed8; font-size:12px; text-decoration:underline;">Ver foto</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div style="font-size:13px; color:#374151; margin-bottom:12px;">
                    <span style="color:#9ca3af;">Observação do Conferente</span>
                    <div>{{ $req->observacao_conferencia }}</div>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button onclick="document.getElementById('modal-resolver-m-{{ $req->id }}').style.display='flex'"
                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Resolver
                    </button>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhuma pendência no momento.</p>
            </div>
        @endforelse
        @if($requests->hasPages())
            <div style="padding:16px 4px; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
```

- [ ] **Step 6: Adicionar link de navegação (admin)**

Em `resources/views/layouts/navigation.blade.php`, logo depois do bloco
`@if(Auth::user()->isAdmin()) ... @endif` do link "⚙ Admin" na versão
desktop (linhas 21-30), adicione mais um link dentro do mesmo `@if`
(reaproveitando a mesma condição `isAdmin()`, sem criar um novo `@if`):

Trecho atual (linhas 21-30):
```blade
                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.index') }}"
                       style="color: {{ request()->routeIs('admin.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('admin.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('admin.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('admin.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        ⚙ Admin
                    </a>
                    @endif
```

Substitua por (adiciona o link "📋 Pendências" antes do `@endif`):
```blade
                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.index') }}"
                       style="color: {{ request()->routeIs('admin.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('admin.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('admin.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('admin.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        ⚙ Admin
                    </a>
                    <a href="{{ route('pendencias.index') }}"
                       style="color: {{ request()->routeIs('pendencias.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('pendencias.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('pendencias.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('pendencias.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        📋 Pendências
                    </a>
                    @endif
```

Agora, no bloco mobile (linhas 104-106):
```blade
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">⚙ Admin</a>
            @endif
```

Substitua por:
```blade
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">⚙ Admin</a>
            <a href="{{ route('pendencias.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">📋 Pendências</a>
            @endif
```

- [ ] **Step 7: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=PendenciaControllerTest`
Expected: PASS — todos os 6 testes.

- [ ] **Step 8: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmo número de falhas pré-existentes de antes (2 em
`Auth\RegistrationTest`, 1 em `ExampleTest` — 3 no total), nenhuma nova.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/PendenciaController.php resources/views/pendencias/index.blade.php routes/web.php resources/views/layouts/navigation.blade.php tests/Feature/PendenciaControllerTest.php
git commit -m "feat: adiciona listagem da Tela de Pendencias"
```

---

### Task 2: Ação de resolver pendência (aceitar / cancelar)

**Files:**
- Modify: `app/Http/Controllers/PendenciaController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/pendencias/index.blade.php`
- Test: `tests/Feature/PendenciaControllerTest.php`

**Interfaces:**
- Consumes: rota `pendencias.index` (Task 1), controller `PendenciaController`
  (Task 1) e a view de listagem (Task 1) — este task adiciona o método
  `resolver()` e os modais na mesma view.
- Produces: rota nomeada `pendencias.resolver` (PATCH `/pendencias/{purchaseRequest}`).

- [ ] **Step 1: Escrever os testes que falham**

Adicione estes métodos em `tests/Feature/PendenciaControllerTest.php`
(dentro da classe, depois do último método existente):

```php
    private function pendenciaAprovadaEstoque(array $overrides = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'tipo_entrega' => 'estoque',
            'quantity' => 10,
            'quantidade_recebida' => 8,
        ], $overrides));
    }

    public function test_resolver_aceitar_sets_avancado_mesmo_assim(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertRedirect(route('pendencias.index'));
        $this->assertSame('avancado_mesmo_assim', $req->fresh()->status_conferencia);
    }

    public function test_resolver_cancelar_sets_cancelado(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'cancelar',
            'observacao' => 'Fornecedor confirmou que não vai reenviar.',
        ]);

        $response->assertRedirect(route('pendencias.index'));
        $this->assertSame('cancelado', $req->fresh()->status_conferencia);
    }

    public function test_resolver_cancelar_requires_observacao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'cancelar',
        ]);

        $response->assertSessionHasErrors('observacao');
        $this->assertSame('divergente', $req->fresh()->status_conferencia);
    }

    public function test_resolver_aceitar_does_not_require_observacao(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertSessionDoesntHaveErrors();
    }

    public function test_resolver_appends_observacao_to_existing_admin_note(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque(['admin_note' => 'Aprovado com urgência.']);

        $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'cancelar',
            'observacao' => 'Fornecedor não tem mais o produto.',
        ]);

        $notaFinal = $req->fresh()->admin_note;
        $this->assertStringContainsString('Aprovado com urgência.', $notaFinal);
        $this->assertStringContainsString('Fornecedor não tem mais o produto.', $notaFinal);
    }

    public function test_resolver_rejects_already_resolved_pendencia(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque(['status_conferencia' => 'cancelado']);

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertStatus(409);
    }

    public function test_resolver_rejects_non_estoque_tipo_entrega(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $req = $this->pendenciaAprovadaEstoque(['tipo_entrega' => 'entrega_direta']);

        $response = $this->actingAs($admin)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertStatus(409);
    }

    public function test_resolver_requires_admin(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = $this->pendenciaAprovadaEstoque();

        $response = $this->actingAs($conferente)->patch(route('pendencias.resolver', $req), [
            'decisao' => 'aceitar',
        ]);

        $response->assertForbidden();
    }
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=PendenciaControllerTest`
Expected: FAIL — rota `pendencias.resolver` não existe ainda (os 8 testes
novos falham).

- [ ] **Step 3: Adicionar o método `resolver()` ao controller**

Em `app/Http/Controllers/PendenciaController.php`, adicione este método
dentro da classe, depois de `index()`:

```php
    public function resolver(Request $request, PurchaseRequest $purchaseRequest)
    {
        if ($purchaseRequest->status !== 'aprovado'
            || $purchaseRequest->status_conferencia !== 'divergente'
            || $purchaseRequest->tipo_entrega !== 'estoque') {
            abort(409, 'Esta pendência já foi resolvida ou não está mais nesse estado.');
        }

        $request->validate([
            'decisao'    => 'required|in:aceitar,cancelar',
            'observacao' => 'required_if:decisao,cancelar|nullable|string|max:500',
        ], [
            'decisao.required'       => 'Selecione uma decisão.',
            'observacao.required_if' => 'A observação é obrigatória ao cancelar o item.',
        ]);

        $novoStatusConferencia = $request->decisao === 'aceitar' ? 'avancado_mesmo_assim' : 'cancelado';

        $notaAnexada = trim(
            ($purchaseRequest->admin_note ? $purchaseRequest->admin_note . "\n" : '')
            . '[Pendência ' . ($request->decisao === 'aceitar' ? 'aceita' : 'cancelada') . '] '
            . ($request->observacao ?: '')
        );

        $purchaseRequest->update([
            'status_conferencia' => $novoStatusConferencia,
            'admin_note'         => $notaAnexada,
        ]);

        return redirect()->route('pendencias.index')->with('success', 'Pendência resolvida com sucesso!');
    }
```

- [ ] **Step 4: Adicionar a rota**

Em `routes/web.php`, dentro do grupo criado na Task 1
(`Route::middleware(['auth', AdminMiddleware::class])->prefix('pendencias')->name('pendencias.')->group(...)`),
adicione a nova rota depois de `Route::get('/', ...)`:

```php
    Route::patch('/{purchaseRequest}', [PendenciaController::class, 'resolver'])->name('resolver');
```

- [ ] **Step 5: Adicionar os modais na view (desktop + mobile)**

Em `resources/views/pendencias/index.blade.php`, dentro do `@forelse($requests as $req)`
do bloco desktop (`pend-desktop-table`), logo depois da `</tr>` que fecha a
linha da tabela (antes do `@empty`), adicione o modal:

```blade
                        <div id="modal-resolver-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Resolver Pendência</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                                <form method="POST" action="{{ route('pendencias.resolver', $req) }}" id="form-resolver-{{ $req->id }}">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Decisão</label>
                                        <select name="decisao" required onchange="atualizaObservacaoPendencia{{ $req->id }}(this.value)"
                                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                            <option value="aceitar">Aceitar Mesmo Assim</option>
                                            <option value="cancelar">Cancelar Item</option>
                                        </select>
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span id="obs-obrigatoria-{{ $req->id }}" style="display:none; color:#dc2626;">*</span></label>
                                        <textarea name="observacao" rows="3"
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                    </div>

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-resolver-{{ $req->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Confirmar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                        function atualizaObservacaoPendencia{{ $req->id }}(valor) {
                            var form = document.getElementById('form-resolver-{{ $req->id }}');
                            var textarea = form.querySelector('textarea[name="observacao"]');
                            var marcador = document.getElementById('obs-obrigatoria-{{ $req->id }}');
                            if (valor === 'cancelar') {
                                textarea.setAttribute('required', 'required');
                                marcador.style.display = 'inline';
                            } else {
                                textarea.removeAttribute('required');
                                marcador.style.display = 'none';
                            }
                        }
                        </script>
```

Agora, no bloco mobile (`pend-mobile-cards`), dentro do `@forelse($requests as $req)`,
logo depois da `</div>` que fecha o card (antes do `@empty`), adicione o
modal equivalente com sufixo `-m-` (mesmo padrão de
`conferencia/index.blade.php` pros modais mobile):

```blade
            <div id="modal-resolver-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Resolver Pendência</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                    <form method="POST" action="{{ route('pendencias.resolver', $req) }}" id="form-resolver-m-{{ $req->id }}">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Decisão</label>
                            <select name="decisao" required onchange="atualizaObservacaoPendenciaMobile{{ $req->id }}(this.value)"
                                    style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                <option value="aceitar">Aceitar Mesmo Assim</option>
                                <option value="cancelar">Cancelar Item</option>
                            </select>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span id="obs-obrigatoria-m-{{ $req->id }}" style="display:none; color:#dc2626;">*</span></label>
                            <textarea name="observacao" rows="3"
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" onclick="document.getElementById('modal-resolver-m-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit"
                                    style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Confirmar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function atualizaObservacaoPendenciaMobile{{ $req->id }}(valor) {
                var form = document.getElementById('form-resolver-m-{{ $req->id }}');
                var textarea = form.querySelector('textarea[name="observacao"]');
                var marcador = document.getElementById('obs-obrigatoria-m-{{ $req->id }}');
                if (valor === 'cancelar') {
                    textarea.setAttribute('required', 'required');
                    marcador.style.display = 'inline';
                } else {
                    textarea.removeAttribute('required');
                    marcador.style.display = 'none';
                }
            }
            </script>
```

- [ ] **Step 6: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=PendenciaControllerTest`
Expected: PASS — todos os 14 testes (6 da Task 1 + 8 novos).

- [ ] **Step 7: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes, nenhuma nova.

- [ ] **Step 8: Limpar cache de view local**

Run: `php artisan view:clear`

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/PendenciaController.php routes/web.php resources/views/pendencias/index.blade.php tests/Feature/PendenciaControllerTest.php
git commit -m "feat: adiciona acao de resolver pendencia (aceitar ou cancelar)"
```

---

### Task 3: Etiqueta "Cancelado" na Tela do Vendedor

**Files:**
- Modify: `resources/views/requests/index.blade.php`
- Test: `tests/Feature/PurchaseRequestControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::$status_conferencia` podendo agora valer
  `'cancelado'` (produzido pela Task 2, mas este task não depende da Task 2
  ter rodado — só precisa que a view saiba lidar com esse valor de string).
- Produces: nada consumido por tarefas futuras.

- [ ] **Step 1: Escrever os testes que falham**

Adicione estes métodos em `tests/Feature/PurchaseRequestControllerTest.php`
(dentro da classe, depois do último método existente):

```php
    public function test_index_shows_cancelado_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Bateria G7 Plus',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Cancelado<', false);
        $response->assertDontSee('Aguardando conferência');
    }

    public function test_index_cancelado_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '>Cancelado<'));
    }
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: FAIL — os 2 testes novos falham porque a view ainda não tem
branch pra `cancelado` (cai no fallback "Aguardando conferência" já que
`status` continua `'aprovado'`).

- [ ] **Step 3: Adicionar a etiqueta no bloco desktop**

Em `resources/views/requests/index.blade.php`, dentro do `<div>` que já
envolve as 4 condições de status de conferência (criado no sub-projeto 6,
por volta da linha 195-206), localize:

```blade
                                @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                                @elseif($req->status === 'aprovado')
                                    <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                                @endif
```

Substitua por (adiciona o `@elseif` de `cancelado` **antes** do `@elseif($req->status === 'aprovado')`,
já que `cancelado` precisa ser checado antes do fallback de "aprovado sem conferência"):

```blade
                                @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                                @elseif($req->status_conferencia === 'cancelado')
                                    <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Cancelado</span>
                                @elseif($req->status === 'aprovado')
                                    <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                                @endif
```

- [ ] **Step 4: Adicionar a etiqueta no bloco mobile**

No mesmo arquivo, no bloco mobile (mesma estrutura de 4 condições, mais
abaixo no arquivo), localize o trecho idêntico e aplique a mesma mudança:

```blade
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                        @elseif($req->status === 'aprovado')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                        @endif
```

Substitua por:

```blade
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                        @elseif($req->status_conferencia === 'cancelado')
                            <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Cancelado</span>
                        @elseif($req->status === 'aprovado')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                        @endif
```

- [ ] **Step 5: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: PASS — todos os testes do arquivo.

- [ ] **Step 6: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes, nenhuma nova.

- [ ] **Step 7: Limpar cache de view local**

Run: `php artisan view:clear`

- [ ] **Step 8: Commit**

```bash
git add resources/views/requests/index.blade.php tests/Feature/PurchaseRequestControllerTest.php
git commit -m "feat: adiciona etiqueta Cancelado na tela do vendedor"
```

---

### Task 4: Corrige o fallback genérico na Tela do Conferente

**Files:**
- Modify: `resources/views/conferencia/index.blade.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::$status_conferencia` podendo agora valer
  `'cancelado'` (mesma observação da Task 3 — não depende de outra task ter
  rodado).
- Produces: nada consumido por tarefas futuras (última tarefa do plano).

- [ ] **Step 1: Escrever os testes que falham**

Adicione estes métodos em `tests/Feature/ConferenciaControllerTest.php`
(dentro da classe, depois do último método existente):

```php
    public function test_index_conferidos_shows_cancelado_badge_not_avancado(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado Conferencia',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('>Cancelado<', false);
        $response->assertDontSee('Avançado Mesmo Assim');
    }

    public function test_index_conferidos_cancelado_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'product_name' => 'Produto Cancelado Dois Layouts',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '>Cancelado<'));
    }

    public function test_index_conferidos_still_shows_avancado_mesmo_assim_correctly(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Produto Ainda Avancado',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('Avançado Mesmo Assim');
        $response->assertDontSee('>Cancelado<', false);
    }
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: FAIL — os 2 primeiros testes novos falham porque o `@else`
genérico hoje mostra "Avançado Mesmo Assim" pra qualquer valor que não seja
`conferido_ok` nem `divergente`, incluindo `cancelado`. O terceiro teste
(`avancado_mesmo_assim` continua correto) já passa antes da mudança — serve
de proteção contra regressão.

- [ ] **Step 3: Corrigir o bloco desktop**

Em `resources/views/conferencia/index.blade.php`, localize este trecho (na
célula de Resultado da tabela desktop, dentro do `@if($aba === 'conferidos')`):

```blade
                                    @if($req->status_conferencia === 'conferido_ok')
                                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                                    @elseif($req->status_conferencia === 'divergente')
                                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                                    @else
                                        <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                                    @endif
```

Substitua por:

```blade
                                    @if($req->status_conferencia === 'conferido_ok')
                                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                                    @elseif($req->status_conferencia === 'divergente')
                                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                                    @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                        <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                                    @elseif($req->status_conferencia === 'cancelado')
                                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Cancelado</span>
                                    @endif
```

- [ ] **Step 4: Corrigir o bloco mobile**

No mesmo arquivo, localize o trecho idêntico dentro do bloco mobile
(`conf-mobile-cards`):

```blade
                        @if($req->status_conferencia === 'conferido_ok')
                            <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                        @else
                            <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                        @endif
```

Substitua por:

```blade
                        @if($req->status_conferencia === 'conferido_ok')
                            <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                        @elseif($req->status_conferencia === 'cancelado')
                            <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Cancelado</span>
                        @endif
```

- [ ] **Step 5: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS — todos os testes do arquivo.

- [ ] **Step 6: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes, nenhuma nova.

- [ ] **Step 7: Limpar cache de view local**

Run: `php artisan view:clear`

- [ ] **Step 8: Commit**

```bash
git add resources/views/conferencia/index.blade.php tests/Feature/ConferenciaControllerTest.php
git commit -m "fix: corrige fallback generico e adiciona etiqueta Cancelado na aba Conferidos"
```
