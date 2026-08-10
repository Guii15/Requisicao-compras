# Ajustes na Tela do Conferente (câmera + abas) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the photo field open the camera (still allowing gallery selection), and split the Tela do Conferente listing into "Aguardando"/"Conferidos" tabs with a result badge on the conferred side.

**Architecture:** Laravel 12 app. `ConferenciaController::index()` gains a `Request $request` parameter and reads `?aba=` to switch the query's `whereNull`/`whereNotNull` on `status_conferencia`. `resources/views/conferencia/index.blade.php` gains two tab links (same full-page-link pattern already used in `admin/index.blade.php`) and a conditional last column (action button vs result badge). The photo `<input>` gains a `capture` attribute — a pure HTML change, no backend impact.

**Tech Stack:** Laravel 12, PHP 8.4, Blade, PHPUnit (via `php artisan test`).

## Global Constraints

- Default behavior (no `?aba=` or `?aba=aguardando`) must be identical to today: only `status='aprovado'` AND `status_conferencia` null.
- `?aba=conferidos` shows `status='aprovado'` AND `status_conferencia` NOT null (any of the 3 values: `conferido_ok`, `divergente`, `avancado_mesmo_assim`), each with a distinct colored badge — no action button on this tab.
- Badge colors (exact, from the spec): `conferido_ok` → bg `#dcfce7` text `#16a34a` "OK"; `divergente` → bg `#fee2e2` text `#dc2626` "Divergente"; `avancado_mesmo_assim` → bg `#dbeafe` text `#2563eb` "Avançado Mesmo Assim".
- Tabs follow the exact link-based (not JS) pattern already used in `resources/views/admin/index.blade.php` (~lines 61-74).
- Photo input gains `capture="environment"` alongside the existing `accept` attribute — do not remove gallery access, both must remain possible (this is what `capture` does on mobile browsers: it offers camera AND gallery, it does not force camera-only).
- No changes to `conferir()`'s validation/routing/guard logic, to Tela de Pendências, Entrada, Vendedor, or SLA/dashboard — out of scope.
- Pagination must preserve the selected tab (`withQueryString()`).

Spec reference: `docs/superpowers/specs/2026-08-03-ajustes-tela-conferente-design.md`

---

## File Structure

- Modify: `app/Http/Controllers/ConferenciaController.php` — `index()` gains `Request $request` and an `$aba` branch.
- Modify: `resources/views/conferencia/index.blade.php` — tabs, conditional last column, photo `capture` attribute, per-aba empty message.
- Test: `tests/Feature/ConferenciaControllerTest.php` (existing file, extended).

---

### Task 1: `index()` aba filter

**Files:**
- Modify: `app/Http/Controllers/ConferenciaController.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::factory()` (existing), the existing `status_conferencia` column values (`conferido_ok`, `divergente`, `avancado_mesmo_assim`).
- Produces: `ConferenciaController::index(Request $request)` — passes `$requests` (paginated, `withQueryString()`) and `$aba` (string, `'aguardando'` or `'conferidos'`) to the `conferencia.index` view. This is what Task 2's view consumes.

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/ConferenciaControllerTest.php` (inside the existing class, after the existing tests). This test file already has `use App\Models\PurchaseRequest;` and `use App\Models\User;` imported — no new imports needed for this task.

```php
    public function test_index_default_still_shows_only_aguardando(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Aguardando']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Conferido']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('Produto Aguardando');
        $response->assertDontSee('Produto Conferido');
    }

    public function test_index_aba_conferidos_shows_all_three_conferred_statuses(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Aguardando']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Produto Avancado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertDontSee('Produto Aguardando');
        $response->assertSee('Produto OK');
        $response->assertSee('Produto Divergente');
        $response->assertSee('Produto Avancado');
    }

    public function test_index_ignores_unknown_aba_value_and_falls_back_to_aguardando(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Aguardando']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'lixo']));

        $response->assertSee('Produto Aguardando');
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: `test_index_aba_conferidos_shows_all_three_conferred_statuses` FAILS (the controller has no `?aba=` handling yet, so it always applies `whereNull('status_conferencia')` — "Produto OK"/"Divergente"/"Avancado" won't be found in the response body). The other two new tests PASS already by coincidence (current behavior already matches "default shows aguardando" and there's no `aba` handling to break on an unknown value) — that's fine, they exist to lock in the behavior going forward, not to prove a bug.

- [ ] **Step 3: Update the controller**

Replace the `index()` method in `app/Http/Controllers/ConferenciaController.php`:

```php
    public function index(Request $request)
    {
        $aba = $request->query('aba') === 'conferidos' ? 'conferidos' : 'aguardando';

        $query = PurchaseRequest::with('user')->where('status', 'aprovado');

        if ($aba === 'conferidos') {
            $query->whereNotNull('status_conferencia');
        } else {
            $query->whereNull('status_conferencia');
        }

        $requests = $query->latest()->paginate(15)->withQueryString();

        return view('conferencia.index', compact('requests', 'aba'));
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS (all tests in the file, including the 3 new ones — total count is 3 more than before this task)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/ConferenciaController.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona filtro de aba (aguardando/conferidos) na listagem do conferente"
```

---

### Task 2: Tabs, result badge, and camera capture in the view

**Files:**
- Modify: `resources/views/conferencia/index.blade.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: `$aba` (string, `'aguardando'`/`'conferidos'`) and `$requests` from Task 1's `index()`.
- Produces: nothing consumed by a later task — this is the last task in this plan besides verification.

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/ConferenciaControllerTest.php`:

```php
    public function test_index_conferidos_shows_correct_badge_for_each_status(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto OK']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente', 'product_name' => 'Produto Divergente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'avancado_mesmo_assim', 'product_name' => 'Produto Avancado']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('>OK<', false);
        $response->assertSee('Divergente');
        $response->assertSee('Avançado Mesmo Assim');
    }

    public function test_index_conferidos_has_no_conferir_button(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertDontSee('Conferir', false);
    }

    public function test_foto_input_has_capture_attribute(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('capture="environment"', false);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: FAIL on all 3 new tests — the view has no tabs/badges/`capture` attribute yet, so `assertSee('OK', ...)`/`assertSee('Divergente')` etc. won't find anything in the "conferidos" response (the "aguardando"-only query means the conferred rows aren't even in `$requests` yet — wait, Task 1 already fixed the query; the failures here are because the VIEW still only renders the "Ação"/Conferir column, never a badge, and has no `capture` attribute), and `test_index_conferidos_has_no_conferir_button` may already pass by accident since the view renders "Conferir" only inside the `@forelse` per-row action cell, which today runs unconditionally regardless of aba — assert its real failure/pass in the RED run and don't worry if one of the three already passes for the wrong reason; Step 4's GREEN run is what matters.

- [ ] **Step 3: Update the view**

In `resources/views/conferencia/index.blade.php`:

**3a.** Add tabs right after the header `<div>` (after line 10's closing `</div>`, before the `@if(session('success'))` block on line 12):

```blade
    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('conferencia.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'aguardando' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }};"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='{{ $aba === 'aguardando' ? '#fff' : '#6b7280' }}'">
            Aguardando
        </a>
        <a href="{{ route('conferencia.index', ['aba' => 'conferidos']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'conferidos' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'conferidos' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'conferidos' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'conferidos' ? '#05018D' : 'transparent' }};"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='{{ $aba === 'conferidos' ? '#fff' : '#6b7280' }}'">
            Conferidos
        </a>
    </div>
```

**3b.** Change the table header's last column (line 29) from a fixed "Ação" label to a conditional one:

```blade
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">{{ $aba === 'conferidos' ? 'Resultado' : 'Ação' }}</th>
```

**3c.** Replace the last `<td>` in the row (lines 47-52, the one with the "Conferir" button) with a conditional block — action button on "aguardando", badge on "conferidos":

```blade
                            <td style="padding:12px 16px; text-align:center;">
                                @if($aba === 'conferidos')
                                    @if($req->status_conferencia === 'conferido_ok')
                                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                                    @elseif($req->status_conferencia === 'divergente')
                                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                                    @else
                                        <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                                    @endif
                                @else
                                    <button onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='flex'"
                                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                        Conferir
                                    </button>
                                @endif
                            </td>
```

**3d.** Wrap the modal + its `<script>` block (lines 55-121, the `<div id="modal-conferir-{{ $req->id }}">...</div>` and the following `<script>...</script>`) so they only render on the "aguardando" tab — there's no action to take on a conferred item in this plan, so no modal is needed there. Wrap both in a single `@if($aba === 'aguardando') ... @endif`:

```blade
                        @if($aba === 'aguardando')
                        <div id="modal-conferir-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            {{-- ... existing modal content unchanged, from the current line 56 through line 110 ... --}}
                        </div>

                        <script>
                        {{-- ... existing script content unchanged, from the current line 114 through line 120 ... --}}
                        </script>
                        @endif
```
(Keep every line of the existing modal/script content exactly as-is between the new `@if`/`@endif` — this step only adds the wrapper, it does not change anything inside.)

**3e.** Add `capture="environment"` to the photo input (inside the modal, currently line 72):

```blade
                                        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" capture="environment" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
```

**3f.** Change the empty-state message (currently line 125, fixed "Nenhuma requisição aguardando conferência") to depend on `$aba`:

```blade
                                {{ $aba === 'conferidos' ? 'Nenhuma requisição conferida ainda' : 'Nenhuma requisição aguardando conferência' }}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS (all tests in the file — 6 more than at the start of this plan: 3 from Task 1, 3 from Task 2)

- [ ] **Step 5: Manually verify the tabs, badges, and capture attribute render correctly**

Run this real tinker command (do NOT substitute a grep on the source template — render through the actual controller so Blade compilation is exercised for real):

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$ok = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);
\$divergente = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'divergente']);
\$request = Illuminate\Http\Request::create('/conferencia', 'GET', ['aba' => 'conferidos']);
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index(\$request)->render();
echo (str_contains(\$html, 'Conferidos') ? 'Aba Conferidos presente: OK' : 'Aba AUSENTE') . PHP_EOL;
echo (str_contains(\$html, '>OK<') ? 'Badge OK presente: OK' : 'Badge OK AUSENTE') . PHP_EOL;
echo (str_contains(\$html, 'Divergente') ? 'Badge Divergente presente: OK' : 'Badge Divergente AUSENTE') . PHP_EOL;
echo (!str_contains(\$html, 'Conferir<') ? 'Sem botao Conferir na aba conferidos: OK' : 'Botao Conferir aparece indevidamente') . PHP_EOL;
echo (str_contains(\$html, 'capture=\"environment\"') ? 'Atributo capture presente: OK' : 'Atributo capture AUSENTE') . PHP_EOL;
\$ok->delete(); \$divergente->delete(); \$conferente->delete();
"
```

Expected output: all 5 lines end in "OK". Report the real output in your report file — do not fabricate it. The last line cleans up the 3 rows this command creates in the real local dev database.

- [ ] **Step 6: Commit**

```bash
git add resources/views/conferencia/index.blade.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona abas Aguardando/Conferidos e captura de foto por camera"
```

---

### Task 3: Full regression pass

**Files:** none (verification only)

**Interfaces:** none.

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: All tests PASS except the 3 pre-existing, unrelated failures already known (`RegistrationTest` x2, `ExampleTest` x1). Total passing count should be 6 more than before this plan started.

- [ ] **Step 2: Manually confirm the "aguardando" tab still shows the working Conferir modal (no regression from Task 2's `@if($aba === 'aguardando')` wrapper)**

Run this real tinker command:

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$pendente = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
\$request = Illuminate\Http\Request::create('/conferencia', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index(\$request)->render();
echo (str_contains(\$html, 'modal-conferir-' . \$pendente->id) ? 'Modal presente na aba aguardando: OK' : 'Modal AUSENTE - regressao') . PHP_EOL;
echo (str_contains(\$html, 'btn-avancar-' . \$pendente->id) ? 'Botao avancar ainda presente para entrega_direta: OK' : 'Botao avancar AUSENTE - regressao') . PHP_EOL;
\$pendente->delete(); \$conferente->delete();
"
```

Expected output: both lines end in "OK". Report the real output.

- [ ] **Step 3: Commit (only if a fix was needed)**

If Step 1 or Step 2 uncovered a regression, fix it, re-run the full suite from Step 1, then commit the fix separately. If nothing needed fixing, skip this step.

---

## Plan Self-Review Notes

- **Spec coverage:** §4.1 (photo capture) → Task 2 Step 3e. §4.2 (`index()` aba) → Task 1. §4.3 (tabs + conditional column) → Task 2 Steps 3a-3c, 3f. §6 test plan (default unchanged, conferidos shows all 3 statuses, badge rendering, camera attribute, visual check) → Tasks 1-3.
- §5 (out of scope: viewing photo, editing past conferência, quantity split, Pendências/Entrada/Vendedor/SLA) has no tasks by design.
- `$aba` variable name and its two valid values (`'aguardando'`/`'conferidos'`) are consistent between Task 1 (controller) and Task 2 (view).
- Task 2 Step 3d's wrapping of the modal/script in `@if($aba === 'aguardando')` is a deliberate addition beyond the spec's literal text (the spec doesn't explicitly say "no modal on Conferidos tab") but follows directly from spec §3 decision 3 ("Sem ação nessa aba (só consulta)") — there is no action to take on a conferred row, so no modal is needed; this is called out explicitly in Task 3 Step 2's regression check to make sure the aguardando tab's modal wasn't accidentally broken by the wrapper.
