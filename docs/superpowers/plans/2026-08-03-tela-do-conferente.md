# Tela do Conferente — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first real new screen of the Conferência module: a conferente lists approved-but-not-yet-checked purchase requests and records the physical check-in result (quantity received, photo, OK/Divergente) per item, with the correct routing based on `tipo_entrega`.

**Architecture:** Laravel 12 app. New `ConferenciaController` with `index()` (list) and `conferir()` (per-item action) methods, a new route group mirroring the existing `admin` group's middleware pattern, a new Blade view mirroring `admin/index.blade.php`'s table+modal visual style, and a new nav link guarded by `User::isConferente()` (already implemented in a prior sub-project). Photos are stored via Laravel's `public` disk (already configured) and recorded in the existing `conferencia_fotos` table.

**Tech Stack:** Laravel 12, PHP 8.4, Blade, PHPUnit (via `php artisan test`), `Illuminate\Http\UploadedFile::fake()` / `Storage::fake('public')` for file-upload testing.

## Global Constraints

- The listing shows only `purchase_requests` where `status = 'aprovado'` AND `status_conferencia` is null — once acted on, a request must disappear from this list.
- OK/Divergente is always a manual choice by the conferente — never auto-derived from quantity mismatch.
- Photo is required on every conferência action (OK or Divergente): `image`, mimes `jpg,jpeg,png,webp`, max 5MB (`max:5120` in Laravel's KB-based validator).
- Routing (exact `status_conferencia` values, already defined as valid strings by the base sub-project's migration):
  - Resultado `ok` → `conferido_ok`
  - Resultado `divergente`, `tipo_entrega = estoque`, ação `salvar` → `divergente`
  - Resultado `divergente`, `tipo_entrega = entrega_direta`, ação `salvar` → `divergente`
  - Resultado `divergente`, `tipo_entrega = entrega_direta`, ação `avancar_mesmo_assim` → `avancado_mesmo_assim`
  - Any other combination of ação `avancar_mesmo_assim` (i.e., `tipo_entrega = estoque`, or resultado `ok`) must be rejected with a 403 — this is a server-side guard against a forged POST bypassing the estoque/divergente lock, not just a UI restriction.
- The requisição's `status` column (pendente/aprovado/rejeitado) must never change in this plan — only `status_conferencia` and the other conferência-specific columns.
- No changes to Tela de Pendências, Entrada, Vendedor screens, or SLA/dashboard — out of scope.
- Access is gated by the existing `App\Http\Middleware\ConferenteMiddleware` (already implemented, not yet attached to any route) and `User::isConferente()` (already implemented: `true` when `role === 'conferente'` or `isAdmin()`).
- Follow existing visual/code patterns in this repo: `resources/views/admin/index.blade.php` for table+modal structure, `resources/views/layouts/navigation.blade.php`'s `@if(Auth::user()->isAdmin())` pattern for the new nav link.

Spec reference: `docs/superpowers/specs/2026-08-03-tela-do-conferente-design.md`

---

## File Structure

- Modify: `routes/web.php` — add `ConferenciaController` import and a new `conferencia` route group.
- Modify: `resources/views/layouts/navigation.blade.php` — add a "🔍 Conferência" nav link (desktop + mobile), guarded by `Auth::user()->isConferente()`.
- Create: `app/Http/Controllers/ConferenciaController.php` — `index()` and `conferir()`.
- Create: `resources/views/conferencia/index.blade.php` — listing + per-item modal.
- Test: `tests/Feature/ConferenciaControllerTest.php`.

---

### Task 1: Route, controller skeleton, listing, and access control

**Files:**
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Create: `app/Http/Controllers/ConferenciaController.php`
- Create: `resources/views/conferencia/index.blade.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: `App\Http\Middleware\ConferenteMiddleware` (existing), `User::isConferente()` (existing), `PurchaseRequest` model with existing `status`/`status_conferencia`/`tipo_entrega` columns and `user()` relation, `PurchaseRequest::factory()` (existing).
- Produces: route `conferencia.index` (`GET /conferencia`), `ConferenciaController::index()` returning `view('conferencia.index', compact('requests'))` where `$requests` is a paginated (15/page) collection of `PurchaseRequest` with `status = 'aprovado'` and `status_conferencia` null, ordered newest-first. This is what Task 2 renders the modal on top of.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/ConferenciaControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConferenciaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('conferencia.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('conferencia.index'));

        $response->assertForbidden();
    }

    public function test_conferente_can_access_index(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('conferencia.index'));

        $response->assertOk();
    }

    public function test_index_lists_only_approved_requests_without_status_conferencia(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $pending = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Pendente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Ja Conferido']);
        PurchaseRequest::factory()->create(['status' => 'pendente', 'status_conferencia' => null, 'product_name' => 'Produto Nao Aprovado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('Produto Pendente');
        $response->assertDontSee('Produto Ja Conferido');
        $response->assertDontSee('Produto Nao Aprovado');
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: FAIL — route `conferencia.index` doesn't exist yet, so every test errors on `route('conferencia.index')` with "Route [conferencia.index] not defined."

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/ConferenciaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Illuminate\Http\Request;

class ConferenciaController extends Controller
{
    public function index()
    {
        $requests = PurchaseRequest::with('user')
            ->where('status', 'aprovado')
            ->whereNull('status_conferencia')
            ->latest()
            ->paginate(15);

        return view('conferencia.index', compact('requests'));
    }
}
```

- [ ] **Step 4: Create the view**

Create `resources/views/conferencia/index.blade.php`:

```blade
@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Conferência</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Requisições aprovadas aguardando conferência</p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solicitada</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Tipo de Entrega</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->product_name }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#374151;">{{ $req->quantity }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->tipo_entrega === 'entrega_direta')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Entrega Direta</span>
                                @else
                                    <span style="background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Estoque</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Conferir
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma requisição aguardando conferência
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

</div>

@endsection
```

Note: the "Conferir" button in this step is not yet wired to a modal — Task 2 adds the modal and makes the button open it. This step only needs the listing itself to be correct and testable.

- [ ] **Step 5: Wire the route**

In `routes/web.php`, add these imports near the other controller/middleware imports (the file already imports `use App\Http\Middleware\AdminMiddleware;` — add `ConferenteMiddleware` the same way, not fully-qualified inline):

```php
use App\Http\Controllers\ConferenciaController;
use App\Http\Middleware\ConferenteMiddleware;
```

Then add this new route group, placed after the existing `admin` group (before `require __DIR__.'/auth.php';`):

```php
Route::middleware(['auth', ConferenteMiddleware::class])->prefix('conferencia')->name('conferencia.')->group(function () {
    Route::get('/', [ConferenciaController::class, 'index'])->name('index');
});
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS (5 tests)

- [ ] **Step 7: Add the nav link**

In `resources/views/layouts/navigation.blade.php`, add the desktop link right after the `@endif` that closes the Admin link block (after line 30, i.e. immediately after the existing):

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

add immediately below it (still inside the `<div class="hidden sm:flex sm:items-center sm:ms-8">` wrapper):

```blade
                    @if(Auth::user()->isConferente())
                    <a href="{{ route('conferencia.index') }}"
                       style="color: {{ request()->routeIs('conferencia.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('conferencia.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('conferencia.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('conferencia.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        🔍 Conferência
                    </a>
                    @endif
```

And for mobile, right after the existing mobile Admin link block:

```blade
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">⚙ Admin</a>
            @endif
```

add immediately below it (still inside the same mobile menu `<div class="pt-2 pb-3 px-4">` wrapper):

```blade
            @if(Auth::user()->isConferente())
            <a href="{{ route('conferencia.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">🔍 Conferência</a>
            @endif
```

- [ ] **Step 8: Manually verify the nav link and page render**

Run: `php artisan serve --port=8135` in the background, then:

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index()->render();
echo (str_contains(\$html, 'Conferência') ? 'Titulo presente: OK' : 'Titulo AUSENTE') . PHP_EOL;
echo (str_contains(\$html, 'Nenhuma requisição aguardando conferência') || str_contains(\$html, 'Conferir') ? 'Lista renderizou: OK' : 'Lista com problema') . PHP_EOL;
"
```

Expected output: `Titulo presente: OK` and `Lista renderizou: OK`. Report the real output in your report file — do not fabricate it. Stop the server afterward.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/ConferenciaController.php resources/views/conferencia/index.blade.php resources/views/layouts/navigation.blade.php routes/web.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona listagem da tela do conferente"
```

---

### Task 2: `conferir()` action — validation, routing logic, photo upload, and modal

**Files:**
- Modify: `app/Http/Controllers/ConferenciaController.php`
- Modify: `resources/views/conferencia/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::fotosConferencia()` (existing `hasMany` to `ConferenciaFoto`), `ConferenciaFoto` model (existing, fillable `purchase_request_id`/`caminho_arquivo`/`nome_original`), the `conferencia.index` route/view from Task 1.
- Produces: route `conferencia.conferir` (`PATCH /conferencia/{purchaseRequest}`), `ConferenciaController::conferir(Request $request, PurchaseRequest $purchaseRequest)`.

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/ConferenciaControllerTest.php` (inside the existing class, after the Task 1 tests). This step also adds the `use Illuminate\Http\UploadedFile;` and `use Illuminate\Support\Facades\Storage;` imports at the top of the file, alongside the existing `use` statements:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
```

```php
    public function test_conferir_with_resultado_ok_persists_conferido_ok_and_photo(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
            'quantity' => 10,
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 10,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertRedirect();
        $purchaseRequest = $purchaseRequest->fresh();
        $this->assertSame('conferido_ok', $purchaseRequest->status_conferencia);
        $this->assertSame(10, $purchaseRequest->quantidade_recebida);
        $this->assertSame($conferente->id, $purchaseRequest->conferente_id);
        $this->assertSame('aprovado', $purchaseRequest->status);
        $this->assertCount(1, $purchaseRequest->fotosConferencia);
        Storage::disk('public')->assertExists($purchaseRequest->fotosConferencia->first()->caminho_arquivo);
    }

    public function test_conferir_divergente_com_tipo_entrega_estoque_persists_divergente(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'Faltaram itens na caixa.',
            'acao' => 'salvar',
        ]);

        $this->assertSame('divergente', $purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_divergente_com_tipo_entrega_entrega_direta_e_avancar_mesmo_assim(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
        ]);

        $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'Embalagem avariada, seguindo mesmo assim.',
            'acao' => 'avancar_mesmo_assim',
        ]);

        $this->assertSame('avancado_mesmo_assim', $purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_rejects_avancar_mesmo_assim_when_tipo_entrega_is_estoque(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'observacao_conferencia' => 'Tentativa de burlar a trava.',
            'acao' => 'avancar_mesmo_assim',
        ]);

        $response->assertForbidden();
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_rejects_avancar_mesmo_assim_when_resultado_is_ok(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'avancar_mesmo_assim',
        ]);

        $response->assertForbidden();
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_requires_observacao_when_divergente(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'divergente',
            'acao' => 'salvar',
        ]);

        $response->assertSessionHasErrors('observacao_conferencia');
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }

    public function test_conferir_requires_foto(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => 3,
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $response->assertSessionHasErrors('foto');
        $this->assertNull($purchaseRequest->fresh()->status_conferencia);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: FAIL — the 7 new tests fail with "Route [conferencia.conferir] not defined" (route doesn't exist yet).

- [ ] **Step 3: Wire the route**

In `routes/web.php`, add this line inside the `conferencia` group created in Task 1, right after the `index` route:

```php
    Route::patch('/{purchaseRequest}', [ConferenciaController::class, 'conferir'])->name('conferir');
```

- [ ] **Step 4: Run tests to verify the route now resolves, and see the real remaining failures**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: Still FAIL (7 tests) but now because `conferir()` doesn't exist on the controller (`Call to undefined method`), not because of a missing route.

- [ ] **Step 5: Implement `conferir()`**

In `app/Http/Controllers/ConferenciaController.php`, add the `use Illuminate\Http\Request;` import is already there from Task 1 (`use Illuminate\Http\Request;`), then add this method:

```php
    public function conferir(Request $request, PurchaseRequest $purchaseRequest)
    {
        $request->validate([
            'quantidade_recebida'     => 'required|integer|min:0',
            'foto'                    => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'resultado'               => 'required|in:ok,divergente',
            'observacao_conferencia'  => 'required_if:resultado,divergente|nullable|string|max:500',
            'acao'                    => 'required|in:salvar,avancar_mesmo_assim',
        ], [
            'quantidade_recebida.required'       => 'Informe a quantidade recebida.',
            'foto.required'                      => 'A foto é obrigatória.',
            'foto.image'                          => 'O arquivo precisa ser uma imagem.',
            'foto.mimes'                          => 'Formatos aceitos: jpg, jpeg, png, webp.',
            'foto.max'                            => 'A foto deve ter no máximo 5MB.',
            'resultado.required'                 => 'Selecione o resultado da conferência.',
            'observacao_conferencia.required_if' => 'A observação é obrigatória quando divergente.',
        ]);

        $podeAvancarMesmoAssim = $request->resultado === 'divergente' && $purchaseRequest->tipo_entrega === 'entrega_direta';

        if ($request->acao === 'avancar_mesmo_assim' && !$podeAvancarMesmoAssim) {
            abort(403, 'Ação não permitida para esta combinação de resultado e tipo de entrega.');
        }

        if ($request->resultado === 'ok') {
            $statusConferencia = 'conferido_ok';
        } elseif ($request->acao === 'avancar_mesmo_assim') {
            $statusConferencia = 'avancado_mesmo_assim';
        } else {
            $statusConferencia = 'divergente';
        }

        $purchaseRequest->update([
            'quantidade_recebida'    => $request->quantidade_recebida,
            'status_conferencia'     => $statusConferencia,
            'observacao_conferencia' => $request->observacao_conferencia,
            'conferente_id'          => auth()->id(),
        ]);

        $path = $request->file('foto')->store('conferencia', 'public');
        $purchaseRequest->fotosConferencia()->create([
            'caminho_arquivo' => $path,
            'nome_original'   => $request->file('foto')->getClientOriginalName(),
        ]);

        return redirect()->route('conferencia.index')->with('success', 'Conferência registrada com sucesso!');
    }
```

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS (12 tests total — 5 from Task 1, 7 new)

- [ ] **Step 7: Add the modal to the view**

In `resources/views/conferencia/index.blade.php`, replace the "Conferir" button (inside the `@forelse` loop, currently a plain `<button>` with no `onclick`) with a button that opens a per-row modal, and add the modal markup right after the row's closing `</tr>` (still inside the `@forelse` loop, before `@empty`):

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Conferir
                                </button>
                            </td>
                        </tr>

                        <div id="modal-conferir-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Conferir Item</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                                <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-{{ $req->id }}">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Recebida</label>
                                        <input type="number" name="quantidade_recebida" value="{{ $req->quantity }}" min="0" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Foto</label>
                                        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Resultado</label>
                                        <select name="resultado" required onchange="atualizaResultado{{ $req->id }}(this.value)"
                                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                            <option value="ok">OK</option>
                                            <option value="divergente">Divergente</option>
                                        </select>
                                    </div>

                                    <div id="campo-observacao-{{ $req->id }}" style="display:none; margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação</label>
                                        <textarea name="observacao_conferencia" rows="3"
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                    </div>

                                    <input type="hidden" name="acao" id="campo-acao-{{ $req->id }}" value="salvar">

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit" onclick="document.getElementById('campo-acao-{{ $req->id }}').value='salvar'"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Salvar
                                        </button>
                                        @if($req->tipo_entrega === 'entrega_direta')
                                        <button type="submit" id="btn-avancar-{{ $req->id }}" onclick="document.getElementById('campo-acao-{{ $req->id }}').value='avancar_mesmo_assim'"
                                                style="display:none; padding:9px 24px; border-radius:8px; background:#d97706; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Avançar Mesmo Assim
                                        </button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        </div>
```

Then, right before `@endforelse` (still inside the `@forelse` loop scope, as a per-row inline `<script>` block right after the modal `</div>` shown above), add the JS toggle:

```blade
                        <script>
                        function atualizaResultado{{ $req->id }}(valor) {
                            document.getElementById('campo-observacao-{{ $req->id }}').style.display = valor === 'divergente' ? 'block' : 'none';
                            var btnAvancar = document.getElementById('btn-avancar-{{ $req->id }}');
                            if (btnAvancar) {
                                btnAvancar.style.display = valor === 'divergente' ? 'inline-block' : 'none';
                            }
                        }
                        </script>
```

- [ ] **Step 8: Manually verify the modal renders with the conditional button**

Run: `php artisan serve --port=8135` in the background, then:

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$dropship = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
\$estoque = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque']);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index()->render();
echo (str_contains(\$html, 'btn-avancar-' . \$dropship->id) ? 'Botao avancar presente para entrega_direta: OK' : 'Botao avancar AUSENTE') . PHP_EOL;
echo (!str_contains(\$html, 'btn-avancar-' . \$estoque->id) ? 'Botao avancar ausente para estoque: OK' : 'Botao avancar aparece indevidamente') . PHP_EOL;
"
```

Expected output: `Botao avancar presente para entrega_direta: OK` and `Botao avancar ausente para estoque: OK`. Report the real output in your report file — do not fabricate it. Stop the server afterward.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/ConferenciaController.php resources/views/conferencia/index.blade.php routes/web.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona acao de conferir item na tela do conferente"
```

---

### Task 3: Full regression pass and storage setup

**Files:** none (verification only, plus running `php artisan storage:link` which creates a symlink, not a tracked file change)

**Interfaces:** none — this task only runs the full suite plus manual browser verification to confirm nothing else broke and the feature works end-to-end.

- [ ] **Step 1: Run `php artisan storage:link`**

Run: `php artisan storage:link`
Expected: `The [public\storage] link has been connected to [storage\app\public].` (or equivalent — this makes uploaded photos web-accessible; needed for the manual verification below and for real usage, though the automated tests use `Storage::fake()` and don't need this symlink).

- [ ] **Step 2: Run the entire test suite**

Run: `php artisan test`
Expected: All tests PASS except the 3 pre-existing, unrelated failures already known (`RegistrationTest` x2 on the deliberately-absent `/register` route, `ExampleTest` x1 on `/` deliberately redirecting). Confirm the failure count and names match exactly those 3, nothing new — total passing count should be 12 more than before this plan started (5 + 7 new tests in this plan).

- [ ] **Step 3: Verify the full flow end-to-end through the real HTTP stack (not a direct controller call)**

Tasks 1-2's manual checks used `$controller->index()->render()` directly, which skips routing, middleware, and CSRF. This step instead drives the same route a real browser would hit, using an actual HTTP request/response cycle, via a throwaway PHPUnit test so the result is deterministic and reportable (not dependent on a human clicking through a browser). Create a temporary file `tests/Feature/ConferenciaSmokeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConferenciaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_http_flow_list_then_conferir(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
            'quantity' => 5,
        ]);

        $indexResponse = $this->actingAs($conferente)->get(route('conferencia.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('Conferir');
        $indexResponse->assertSee($req->product_name);

        $conferirResponse = $this->actingAs($conferente)->patch(route('conferencia.conferir', $req), [
            'quantidade_recebida' => 5,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);
        $conferirResponse->assertRedirect(route('conferencia.index'));
        $conferirResponse->assertSessionHas('success');

        $this->assertSame('conferido_ok', $req->fresh()->status_conferencia);

        $afterConferir = $this->actingAs($conferente)->get(route('conferencia.index'));
        $afterConferir->assertOk();
        $afterConferir->assertDontSee($req->product_name);
    }
}
```

Run: `php artisan test --filter=ConferenciaSmokeTest`
Expected: PASS (1 test). Report the real output (pass/fail, full text if it fails) in your report file.

Then delete the throwaway file — it exists only to exercise the real HTTP stack once, not to remain as a permanent regression test (Tasks 1-2's tests already cover both endpoints individually with the correct level of permanence):

```bash
rm tests/Feature/ConferenciaSmokeTest.php
```

- [ ] **Step 4: Commit (only if a fix was needed)**

If Step 2 or Step 3 uncovered a regression or bug, fix it, re-run the full suite from Step 2, then commit the fix separately with a message describing what broke and why. If nothing needed fixing, skip this step.

---

## Plan Self-Review Notes

- **Spec coverage:** §4 (rotas/nav) → Task 1 Steps 5+7. §5 (`index()`) → Task 1 Step 3. §5 (`conferir()` + validação + roteamento) → Task 2 Step 5. §6 (views: listagem + modal) → Task 1 Step 4, Task 2 Step 7. §8 (test plan: acesso, listagem filtrada, todos os caminhos de roteamento, rejeição de forjar `avancar_mesmo_assim`, campos obrigatórios, `status` inalterado, verificação visual) → Tasks 1-3.
- §7 (fora de escopo: Pendências, Entrada, Vendedor, SLA) has no tasks by design — verified no task touches those areas.
- Field names and values are consistent across tasks: `resultado` (`ok`/`divergente`), `acao` (`salvar`/`avancar_mesmo_assim`), `status_conferencia` values (`conferido_ok`/`divergente`/`avancado_mesmo_assim`) match exactly between the controller code in Task 2 and the test assertions in both Task 1 (indirectly, via factory states) and Task 2.
- The 403-on-forged-`acao` guard (a security-relevant requirement from the spec) has two dedicated tests (`test_conferir_rejects_avancar_mesmo_assim_when_tipo_entrega_is_estoque`, `test_conferir_rejects_avancar_mesmo_assim_when_resultado_is_ok`) rather than being left implicit.
