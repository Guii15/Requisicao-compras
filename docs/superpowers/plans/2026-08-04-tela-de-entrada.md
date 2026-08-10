# Tela de Entrada Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Criar do zero a Tela de Entrada — pra quem recebe fisicamente o
produto no depósito (nova role `entrada`) registrar vendedor destino e
quantidade, depois que o item já passou pela conferência (OK ou avançado
mesmo assim) — e refletir isso na Tela do Vendedor.

**Architecture:** Novo `EntradaController` (index + darEntrada), nova view
`resources/views/entrada/index.blade.php`, seguindo o mesmo padrão visual
já usado em `pendencias/index.blade.php` (tabela desktop + cards mobile,
modal por item). Novos campos em `purchase_requests`
(`vendedor_destino`, `quantidade_entrada`, `entrada_concluida_em`) via
migration. Nova role `entrada` espelhando a role `conferente` já existente.
A Tela do Vendedor ganha um 6º estado de etiqueta, checado com prioridade
sobre os demais.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`php artisan test`), SQLite (dev local).

## Global Constraints

- Acesso à Tela de Entrada: `EntradaMiddleware`, que libera `role === 'entrada'`
  OU admin (mesmo padrão de `ConferenteMiddleware`/`isConferente()`).
- Lista mostra só `status_conferencia IN ('conferido_ok', 'avancado_mesmo_assim')`
  E `entrada_concluida_em` nulo.
- **Sem campo de status novo** — o sinal de "já teve entrada" é só
  `entrada_concluida_em` estar preenchido ou não.
- **Sem `entrada_iniciada_em`** nesta fatia (decisão do brainstorming — fica
  pra fatia futura de SLA).
- Trava 409 no `darEntrada()`: só processa se, no momento do PATCH, o item
  ainda tiver `status_conferencia` em `('conferido_ok', 'avancado_mesmo_assim')`
  E `entrada_concluida_em` ainda for nulo.
- Etiqueta "Entrada Realizada" na Tela do Vendedor tem prioridade sobre
  TODAS as outras etiquetas de conferência — é o primeiro `@if` da cascata,
  antes até de `conferido_ok`.
- Sem upload de foto próprio nesta tela — reaproveita
  `fotosConferencia`/`Storage::url()` já usado na Tela de Pendências.
- Nenhuma mudança em `ConferenciaController`, `PendenciaController`,
  `AdminController`, ou no e-mail `PurchaseRequestApproved` já existente.

---

### Task 1: Migration, role, middleware e listagem

**Files:**
- Create: `database/migrations/2026_08_04_000000_add_entrada_fields_to_purchase_requests_table.php`
- Create: `app/Http/Middleware/EntradaMiddleware.php`
- Create: `app/Http/Controllers/EntradaController.php`
- Create: `resources/views/entrada/index.blade.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/PurchaseRequest.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Test: `tests/Feature/EntradaControllerTest.php` (novo)

**Interfaces:**
- Consumes: `PurchaseRequest` model (`status_conferencia`, `quantity`,
  `quantidade_recebida`, `product_name`, `requester_name`, `supplier`),
  relações já existentes `conferente()` e `fotosConferencia()`.
- Produces: `User::isEntrada(): bool`; colunas `vendedor_destino` (string,
  nullable), `quantidade_entrada` (integer, nullable),
  `entrada_concluida_em` (timestamp, nullable) em `purchase_requests`; rota
  nomeada `entrada.index` (GET `/entrada`) — usada pela Task 2 pro redirect
  após dar entrada.

- [ ] **Step 1: Escrever os testes de acesso e listagem que falham**

Crie `tests/Feature/EntradaControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntradaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('entrada.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('entrada.index'));

        $response->assertForbidden();
    }

    public function test_conferente_is_forbidden(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('entrada.index'));

        $response->assertForbidden();
    }

    public function test_entrada_role_can_access_index(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('entrada.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_conferido_ok_or_avancado_without_entrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'product_name' => 'Item OK Sem Entrada',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Item Avancado Sem Entrada',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente',
            'product_name' => 'Item Divergente Ainda Pendente',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'cancelado',
            'product_name' => 'Item Cancelado',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => null,
            'product_name' => 'Item Aguardando Conferencia',
        ]);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Item Ja Com Entrada',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('Item OK Sem Entrada');
        $response->assertSee('Item Avancado Sem Entrada');
        $response->assertDontSee('Item Divergente Ainda Pendente');
        $response->assertDontSee('Item Cancelado');
        $response->assertDontSee('Item Aguardando Conferencia');
        $response->assertDontSee('Item Ja Com Entrada');
    }

    public function test_index_shows_quantities_and_avancado_warning(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'quantity' => 10,
            'quantidade_recebida' => 7,
            'product_name' => 'Produto Avisado',
        ]);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('10');
        $response->assertSee('7');
        $response->assertSee('Avançado Mesmo Assim');
    }

    public function test_index_shows_empty_message_when_no_items(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('entrada.index'));

        $response->assertSee('Nenhum item liberado aguardando entrada.');
    }
}
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=EntradaControllerTest`
Expected: FAIL — rota `entrada.index` não existe ainda.

- [ ] **Step 3: Criar a migration**

Crie `database/migrations/2026_08_04_000000_add_entrada_fields_to_purchase_requests_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('vendedor_destino')->nullable()->after('quantidade_recebida');
            $table->integer('quantidade_entrada')->nullable()->after('vendedor_destino');
            $table->timestamp('entrada_concluida_em')->nullable()->after('quantidade_entrada');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['vendedor_destino', 'quantidade_entrada', 'entrada_concluida_em']);
        });
    }
};
```

Rode `php artisan migrate` pra aplicar localmente.

- [ ] **Step 4: Adicionar os campos ao `$fillable` do model**

Em `app/Models/PurchaseRequest.php`, no array `$fillable`, adicione depois
de `'conferente_id',`:

```php
        'vendedor_destino',
        'quantidade_entrada',
        'entrada_concluida_em',
```

- [ ] **Step 5: Adicionar `isEntrada()` ao `User`**

Em `app/Models/User.php`, adicione este método (perto de `isConferente()`,
se existir um método parecido no arquivo):

```php
    public function isEntrada(): bool
    {
        return $this->role === 'entrada' || $this->isAdmin();
    }
```

- [ ] **Step 6: Criar o middleware**

Crie `app/Http/Middleware/EntradaMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EntradaMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isEntrada()) {
            abort(403, 'Acesso restrito.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 7: Criar o controller (só `index()` por enquanto)**

Crie `app/Http/Controllers/EntradaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;

class EntradaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim'])
            ->whereNull('entrada_concluida_em')
            ->latest()
            ->paginate(15);

        return view('entrada.index', compact('requests'));
    }
}
```

- [ ] **Step 8: Adicionar a rota**

Em `routes/web.php`, adicione o import no topo:

```php
use App\Http\Controllers\EntradaController;
use App\Http\Middleware\EntradaMiddleware;
```

E adicione este grupo depois do grupo `pendencias` (antes de
`require __DIR__.'/auth.php';`):

```php
Route::middleware(['auth', EntradaMiddleware::class])->prefix('entrada')->name('entrada.')->group(function () {
    Route::get('/', [EntradaController::class, 'index'])->name('index');
});
```

- [ ] **Step 9: Criar a view de listagem**

Crie `resources/views/entrada/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')

<style>
.entr-mobile-cards { display: none; }
@media (max-width: 768px) {
    .entr-desktop-table { display: none; }
    .entr-mobile-cards  { display: block; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Entrada</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Itens liberados pela conferência aguardando entrada</p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            <strong>Não foi possível registrar a entrada:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="entr-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solic. / Receb.</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Foto</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">
                                {{ $req->product_name }}
                                @if($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="display:block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; width:fit-content;">⚠ Avançado Mesmo Assim</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; color:#374151;">{{ $req->quantity }} / {{ $req->quantidade_recebida }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->fotosConferencia->first())
                                    <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#1d4ed8; font-size:12px; text-decoration:underline;">Ver foto</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Dar Entrada
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhum item liberado aguardando entrada.
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

    <div class="entr-mobile-cards">
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:6px;">{{ $req->product_name }}</div>
                @if($req->status_conferencia === 'avancado_mesmo_assim')
                    <span style="display:inline-block; margin-bottom:10px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">⚠ Avançado Mesmo Assim</span>
                @endif

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

                <div style="display:flex; justify-content:flex-end;">
                    <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Dar Entrada
                    </button>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhum item liberado aguardando entrada.</p>
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

Note: os botões "Dar Entrada" ainda não abrem modal nem têm `onclick` —
isso é adicionado na Task 2, junto com o método `darEntrada()`.

- [ ] **Step 10: Adicionar link de navegação (role entrada + admin)**

Em `resources/views/layouts/navigation.blade.php`, siga o mesmo padrão dos
links "🔍 Conferência" e "📋 Pendências" já existentes. Localize o bloco
`@if(Auth::user()->isConferente()) ... @endif` do link "🔍 Conferência" na
versão desktop e adicione, logo depois do `@endif` dele, um novo bloco:

```blade
                    @if(Auth::user()->isEntrada())
                    <a href="{{ route('entrada.index') }}"
                       style="color: {{ request()->routeIs('entrada.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('entrada.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('entrada.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('entrada.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        📦 Entrada
                    </a>
                    @endif
```

E no bloco mobile, logo depois da linha
`<a href="{{ route('conferencia.index') }}" ...>🔍 Conferência</a>` (dentro
do `@if(Auth::user()->isConferente()) ... @endif` mobile), adicione:

```blade
            @if(Auth::user()->isEntrada())
            <a href="{{ route('entrada.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">📦 Entrada</a>
            @endif
```

- [ ] **Step 11: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=EntradaControllerTest`
Expected: PASS — todos os 7 testes.

- [ ] **Step 12: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes (2 em `Auth\RegistrationTest`, 1
em `ExampleTest`), nenhuma nova.

- [ ] **Step 13: Commit**

```bash
git add database/migrations/2026_08_04_000000_add_entrada_fields_to_purchase_requests_table.php app/Http/Middleware/EntradaMiddleware.php app/Http/Controllers/EntradaController.php resources/views/entrada/index.blade.php app/Models/User.php app/Models/PurchaseRequest.php routes/web.php resources/views/layouts/navigation.blade.php tests/Feature/EntradaControllerTest.php
git commit -m "feat: adiciona listagem da Tela de Entrada"
```

---

### Task 2: Ação de dar entrada

**Files:**
- Modify: `app/Http/Controllers/EntradaController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/entrada/index.blade.php`
- Test: `tests/Feature/EntradaControllerTest.php`

**Interfaces:**
- Consumes: rota `entrada.index` (Task 1), controller `EntradaController`
  (Task 1) e a view de listagem (Task 1) — este task adiciona o método
  `darEntrada()` e os modais na mesma view.
- Produces: rota nomeada `entrada.darEntrada` (PATCH `/entrada/{purchaseRequest}`).

- [ ] **Step 1: Escrever os testes que falham**

Adicione estes métodos em `tests/Feature/EntradaControllerTest.php`
(dentro da classe, depois do último método existente):

```php
    private function itemLiberadoParaEntrada(array $overrides = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'quantity' => 10,
            'quantidade_recebida' => 10,
            'requester_name' => 'Vendedor Original',
        ], $overrides));
    }

    public function test_dar_entrada_sets_vendedor_quantidade_e_timestamp(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertRedirect(route('entrada.index'));
        $fresh = $req->fresh();
        $this->assertSame('Vendedor Original', $fresh->vendedor_destino);
        $this->assertSame(10, $fresh->quantidade_entrada);
        $this->assertNotNull($fresh->entrada_concluida_em);
    }

    public function test_dar_entrada_requires_vendedor_destino(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'quantidade_entrada' => 10,
        ]);

        $response->assertSessionHasErrors('vendedor_destino');
        $this->assertNull($req->fresh()->entrada_concluida_em);
    }

    public function test_dar_entrada_requires_quantidade_entrada(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
        ]);

        $response->assertSessionHasErrors('quantidade_entrada');
    }

    public function test_dar_entrada_rejects_already_concluded_item(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada(['entrada_concluida_em' => now()]);

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertStatus(409);
    }

    public function test_dar_entrada_rejects_item_not_conferido_ok_or_avancado(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $req = $this->itemLiberadoParaEntrada(['status_conferencia' => 'divergente']);

        $response = $this->actingAs($entrada)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertStatus(409);
    }

    public function test_dar_entrada_requires_entrada_role(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = $this->itemLiberadoParaEntrada();

        $response = $this->actingAs($conferente)->patch(route('entrada.darEntrada', $req), [
            'vendedor_destino' => 'Vendedor Original',
            'quantidade_entrada' => 10,
        ]);

        $response->assertForbidden();
    }
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=EntradaControllerTest`
Expected: FAIL — rota `entrada.darEntrada` não existe ainda.

- [ ] **Step 3: Adicionar o método `darEntrada()` ao controller**

Em `app/Http/Controllers/EntradaController.php`, adicione este método
dentro da classe (e adicione `use Illuminate\Http\Request;` no topo do
arquivo, junto dos outros `use`):

```php
    public function darEntrada(Request $request, PurchaseRequest $purchaseRequest)
    {
        if (!in_array($purchaseRequest->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)
            || $purchaseRequest->entrada_concluida_em !== null) {
            abort(409, 'Este item já teve entrada registrada ou não está mais liberado pela conferência.');
        }

        $request->validate([
            'vendedor_destino'   => 'required|string|max:255',
            'quantidade_entrada' => 'required|integer|min:0',
        ], [
            'vendedor_destino.required'   => 'Informe o vendedor destino.',
            'quantidade_entrada.required' => 'Informe a quantidade que entrou.',
        ]);

        $purchaseRequest->update([
            'vendedor_destino'     => $request->vendedor_destino,
            'quantidade_entrada'   => $request->quantidade_entrada,
            'entrada_concluida_em' => now(),
        ]);

        return redirect()->route('entrada.index')->with('success', 'Entrada registrada com sucesso!');
    }
```

- [ ] **Step 4: Adicionar a rota**

Em `routes/web.php`, dentro do grupo criado na Task 1
(`Route::middleware(['auth', EntradaMiddleware::class])->prefix('entrada')->name('entrada.')->group(...)`),
adicione a nova rota depois de `Route::get('/', ...)`:

```php
    Route::patch('/{purchaseRequest}', [EntradaController::class, 'darEntrada'])->name('darEntrada');
```

- [ ] **Step 5: Adicionar os modais e conectar os botões (desktop + mobile)**

Em `resources/views/entrada/index.blade.php`, no bloco desktop, troque o
botão sem ação:

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Dar Entrada
                                </button>
                            </td>
```

Por:

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Dar Entrada
                                </button>
                            </td>
```

Logo depois da `</tr>` que fecha a linha da tabela (antes do `@empty`),
adicione o modal:

```blade
                        <div id="modal-entrada-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Dar Entrada</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }}</p>

                                <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" id="form-entrada-{{ $req->id }}">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Vendedor Destino</label>
                                        <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Dada Entrada</label>
                                        <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida }}" min="0" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='none'"
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
```

Agora, no bloco mobile, troque o botão equivalente:

```blade
                <div style="display:flex; justify-content:flex-end;">
                    <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Dar Entrada
                    </button>
                </div>
```

Por:

```blade
                <div style="display:flex; justify-content:flex-end;">
                    <button onclick="document.getElementById('modal-entrada-m-{{ $req->id }}').style.display='flex'"
                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Dar Entrada
                    </button>
                </div>
```

E, logo depois da `</div>` que fecha o card (antes do `@empty` do bloco
mobile), adicione o modal equivalente com sufixo `-m-`:

```blade
            <div id="modal-entrada-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Dar Entrada</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }}</p>

                    <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" id="form-entrada-m-{{ $req->id }}">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Vendedor Destino</label>
                            <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Dada Entrada</label>
                            <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida }}" min="0" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" onclick="document.getElementById('modal-entrada-m-{{ $req->id }}').style.display='none'"
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
```

- [ ] **Step 6: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=EntradaControllerTest`
Expected: PASS — todos os 13 testes (7 da Task 1 + 6 novos).

- [ ] **Step 7: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes, nenhuma nova.

- [ ] **Step 8: Limpar cache de view local**

Run: `php artisan view:clear`

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/EntradaController.php routes/web.php resources/views/entrada/index.blade.php tests/Feature/EntradaControllerTest.php
git commit -m "feat: adiciona acao de dar entrada"
```

---

### Task 3: Etiqueta "Entrada Realizada" na Tela do Vendedor

**Files:**
- Modify: `resources/views/requests/index.blade.php`
- Test: `tests/Feature/PurchaseRequestControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::$entrada_concluida_em` (produzido pela Task 1
  como coluna, e preenchido pela Task 2 — mas este task não depende de
  nenhuma delas ter rodado, só precisa que a coluna exista no schema, o que
  a Task 1 garante via migration).
- Produces: nada consumido por tarefas futuras (última tarefa do plano).

- [ ] **Step 1: Escrever os testes que falham**

Adicione estes métodos em `tests/Feature/PurchaseRequestControllerTest.php`
(dentro da classe, depois do último método existente):

```php
    public function test_index_shows_entrada_realizada_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Bateria G8 Plus',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Entrada Realizada<', false);
        $response->assertDontSee('>Conferido ✓ OK<', false);
    }

    public function test_index_entrada_realizada_takes_priority_over_cancelado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'cancelado',
            'entrada_concluida_em' => now(),
            'product_name' => 'Caso Extremo Cancelado Com Entrada',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Entrada Realizada<', false);
        $response->assertDontSee('>Cancelado<', false);
    }

    public function test_index_entrada_realizada_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => now(),
            'product_name' => 'Produto Entrada Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, '>Entrada Realizada<'));
    }

    public function test_index_without_entrada_still_shows_conferido_ok(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => null,
            'product_name' => 'Produto Sem Entrada Ainda',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('>Conferido ✓ OK<', false);
        $response->assertDontSee('>Entrada Realizada<', false);
    }
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: FAIL — os 3 primeiros testes novos falham porque a cascata ainda
não conhece `entrada_concluida_em` (o quarto já passa antes da mudança,
serve de proteção contra regressão).

- [ ] **Step 3: Adicionar a etiqueta no bloco desktop**

Em `resources/views/requests/index.blade.php`, localize o `<div>` que
envolve a cascata de etiquetas (criado no sub-projeto 6, por volta da linha
195-207):

```blade
                                <div>
                                    @if($req->status_conferencia === 'conferido_ok')
                                        <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                                    @elseif($req->status_conferencia === 'divergente')
                                        <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
                                    @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                        <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                                    @elseif($req->status_conferencia === 'cancelado')
                                        <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Cancelado</span>
                                    @elseif($req->status === 'aprovado')
                                        <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                                    @endif
                                </div>
```

Substitua por (o primeiro `@if($req->status_conferencia === 'conferido_ok')`
vira `@elseif`, e um novo `@if($req->entrada_concluida_em)` entra na frente
de tudo):

```blade
                                <div>
                                    @if($req->entrada_concluida_em)
                                        <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Entrada Realizada</span>
                                    @elseif($req->status_conferencia === 'conferido_ok')
                                        <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                                    @elseif($req->status_conferencia === 'divergente')
                                        <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
                                    @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                        <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                                    @elseif($req->status_conferencia === 'cancelado')
                                        <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Cancelado</span>
                                    @elseif($req->status === 'aprovado')
                                        <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                                    @endif
                                </div>
```

- [ ] **Step 4: Adicionar a etiqueta no bloco mobile**

No mesmo arquivo, localize o trecho idêntico dentro do bloco mobile (mais
abaixo no arquivo, dentro do `<div>` do cabeçalho do card):

```blade
                        @if($req->status_conferencia === 'conferido_ok')
                            <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                        @elseif($req->status_conferencia === 'cancelado')
                            <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Cancelado</span>
                        @elseif($req->status === 'aprovado')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                        @endif
```

Substitua por:

```blade
                        @if($req->entrada_concluida_em)
                            <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Entrada Realizada</span>
                        @elseif($req->status_conferencia === 'conferido_ok')
                            <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
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
git commit -m "feat: adiciona etiqueta Entrada Realizada na tela do vendedor"
```
