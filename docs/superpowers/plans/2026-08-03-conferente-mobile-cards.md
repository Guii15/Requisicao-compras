# Layout Mobile (Cards) na Tela do Conferente — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the Tela do Conferente usable on a phone by replacing the horizontally-scrolling table with stacked cards below a 768px breakpoint, mirroring the existing pattern already proven in `admin/index.blade.php`.

**Architecture:** Pure Blade/CSS change to one file, `resources/views/conferencia/index.blade.php`. A CSS media query toggles two sibling containers (`conf-desktop-table` / `conf-mobile-cards`) via `display:none`/`display:block`. The mobile cards block duplicates the `@forelse` loop (same `$requests` collection, same `$aba`-conditional footer) and duplicates the per-item conferir modal with `-m-` suffixed ids, since a modal inside a `display:none` ancestor renders nothing.

**Tech Stack:** Laravel 12, PHP 8.4, Blade, PHPUnit (via `php artisan test`).

## Global Constraints

- Card shows: product name + "Tipo de Entrega" badge in the header; Vendedor, Fornecedor, Qtd Solicitada, Data in a grid; footer is `$aba`-conditional — "Conferir" button on `aguardando`, result badge (same 3 colors/labels as the desktop table) on `conferidos`.
- Breakpoint and class-toggle mechanism must match `admin/index.blade.php` exactly: `@media (max-width: 768px) { .conf-desktop-table { display: none; } .conf-mobile-cards { display: block; } }`, with `.conf-mobile-cards { display: none; }` as the non-media-query default.
- The mobile modal is a full duplicate of the desktop modal's fields/behavior (quantidade recebida, foto with `capture="environment"`, resultado, observação, avançar mesmo assim), with every `id` attribute and the JS function name suffixed `-m-`/`Mobile` so they never collide with the desktop modal's ids on the same page.
- No changes to `ConferenciaController` or `conferir()`'s logic — this plan is view-only.
- No changes to Tela de Pendências, Entrada, Vendedor, SLA, or the estoque/entrega-direta quantity split (separate, larger, future slice).
- Real acceptance criterion is testing on an actual phone (per spec §6) — automated tests confirm the right markup exists, but the plan explicitly calls out that a human should check it on a real device before considering this done.

Spec reference: `docs/superpowers/specs/2026-08-03-conferente-mobile-cards-design.md`

---

## File Structure

- Modify: `resources/views/conferencia/index.blade.php` — add CSS, add `conf-desktop-table` class to the existing table wrapper, add a new `conf-mobile-cards` block (cards + duplicated modal).
- Test: `tests/Feature/ConferenciaControllerTest.php` (existing file, extended).

---

### Task 1: CSS breakpoint and mobile card list (no modal yet)

**Files:**
- Modify: `resources/views/conferencia/index.blade.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: `$requests` and `$aba` (existing, from `ConferenciaController::index()`).
- Produces: a `.conf-mobile-cards` block in the rendered HTML, one card per `$req` in `$requests`, with an inert "Conferir" button (no `onclick` yet — Task 2 wires it and adds the mobile modal it points to).

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/ConferenciaControllerTest.php` (inside the existing class, after the existing tests):

```php
    public function test_mobile_cards_block_present_with_correct_toggle_classes(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'product_name' => 'Produto Mobile']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('conf-desktop-table', false);
        $response->assertSee('conf-mobile-cards', false);
        $response->assertSee('Produto Mobile');
    }

    public function test_mobile_card_shows_tipo_entrega_badge_and_data_grid(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'tipo_entrega' => 'entrega_direta',
            'requester_name' => 'Vendedor Mobile Teste',
        ]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('Entrega Direta');
        $response->assertSee('Vendedor Mobile Teste');
    }

    public function test_mobile_cards_show_result_badge_on_conferidos_tab(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok', 'product_name' => 'Produto Conferido Mobile']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertSee('Produto Conferido Mobile');
        $response->assertSee('>OK<', false);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: `test_mobile_cards_block_present_with_correct_toggle_classes` FAILS (neither `conf-desktop-table` nor `conf-mobile-cards` exist in the view yet). The other two new tests likely PASS already by coincidence (the text they check for already exists in the desktop table) — that's fine, they exist to lock in the mobile card's content going forward, not to prove a bug. Confirm the real RED output either way.

- [ ] **Step 3: Add the CSS**

In `resources/views/conferencia/index.blade.php`, insert this right after `@section('content')` (line 3) and before the page's first `<div>` (currently line 5):

```blade
<style>
.conf-mobile-cards { display: none; }
@media (max-width: 768px) {
    .conf-desktop-table { display: none; }
    .conf-mobile-cards  { display: block; }
}
</style>
```

- [ ] **Step 4: Add the class to the desktop table wrapper**

Change the existing table wrapper `<div>` (currently line 35):

```blade
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
```

to:

```blade
    <div class="conf-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
```

- [ ] **Step 5: Add the mobile cards block**

Insert this new block right after the `conf-desktop-table` div's closing `</div>` (currently line 166, right before the page's final `</div>` at line 168):

```blade
    <div class="conf-mobile-cards">
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $req->product_name }}</div>
                    @if($req->tipo_entrega === 'entrega_direta')
                        <span style="background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Entrega Direta</span>
                    @else
                        <span style="background:#e0e7ff; color:#3730a3; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Estoque</span>
                    @endif
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px; margin-bottom:12px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->requester_name ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Qtd Solicitada</span>
                        <div style="font-weight:700; font-size:15px; color:#374151;">{{ $req->quantity }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Data</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    @if($aba === 'conferidos')
                        @if($req->status_conferencia === 'conferido_ok')
                            <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                        @else
                            <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                        @endif
                    @else
                        <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                            Conferir
                        </button>
                    @endif
                </div>

            </div>
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">{{ $aba === 'conferidos' ? 'Nenhuma requisição conferida ainda' : 'Nenhuma requisição aguardando conferência' }}</p>
            </div>
        @endforelse
        @if($requests->hasPages())
            <div style="padding:16px 4px; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
```

Note: the mobile "Conferir" button here has no `onclick` yet — it's inert in this task, matching how the desktop table's "Conferir" button started inert in the sub-project that first built it. Task 2 wires it to a new mobile-only modal.

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS (all tests — 3 more than before this task)

- [ ] **Step 7: Manually verify the mobile block renders correctly**

Run this real tinker command (render through the actual controller, not a grep on the source):

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$req = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
\$request = Illuminate\Http\Request::create('/conferencia', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index(\$request)->render();
echo (str_contains(\$html, 'conf-mobile-cards') ? 'Bloco mobile presente: OK' : 'AUSENTE') . PHP_EOL;
echo (str_contains(\$html, '@media (max-width: 768px)') ? 'Media query presente: OK' : 'AUSENTE') . PHP_EOL;
\$req->delete(); \$conferente->delete();
"
```

Expected output: both lines end in "OK". Report the real output — do not fabricate it. The command cleans up its own test rows.

- [ ] **Step 8: Commit**

```bash
git add resources/views/conferencia/index.blade.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona layout de cards mobile na listagem do conferente"
```

---

### Task 2: Mobile conferir modal

**Files:**
- Modify: `resources/views/conferencia/index.blade.php`
- Test: `tests/Feature/ConferenciaControllerTest.php`

**Interfaces:**
- Consumes: the mobile card block from Task 1, the existing `conferencia.conferir` route (unchanged — same endpoint the desktop modal already posts to).
- Produces: a fully working mobile conferir modal (`modal-conferir-m-{id}`), wired to the mobile "Conferir" button.

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/ConferenciaControllerTest.php`:

```php
    public function test_mobile_modal_has_unique_ids_not_colliding_with_desktop_modal(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('modal-conferir-m-' . $req->id, false);
        $response->assertSee('form-conferir-m-' . $req->id, false);
    }

    public function test_mobile_modal_not_rendered_on_conferidos_tab(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $req = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => 'conferido_ok']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index', ['aba' => 'conferidos']));

        $response->assertDontSee('modal-conferir-m-' . $req->id, false);
    }

    public function test_mobile_modal_avancar_button_only_for_entrega_direta(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);
        $dropship = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
        $estoque = PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'estoque']);

        $response = $this->actingAs($conferente)->get(route('conferencia.index'));

        $response->assertSee('<button type="submit" id="btn-avancar-m-' . $dropship->id . '"', false);
        $response->assertDontSee('id="btn-avancar-m-' . $estoque->id . '"', false);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: FAIL on all 3 new tests — the mobile modal doesn't exist yet.

- [ ] **Step 3: Wire the mobile "Conferir" button and add the mobile modal**

In `resources/views/conferencia/index.blade.php`, inside the mobile cards block from Task 1, change the "Conferir" button:

```blade
                    @else
                        <button onclick="document.getElementById('modal-conferir-m-{{ $req->id }}').style.display='flex'"
                                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                            Conferir
                        </button>
                    @endif
```

Then, right after the card's closing `</div>` and still inside the `@forelse` loop's `$aba === 'aguardando'`/else branch — add the mobile modal wrapped in the same `@if($aba === 'aguardando')` condition used by the desktop modal, right before `@empty`:

```blade
            </div>

            @if($aba === 'aguardando')
            <div id="modal-conferir-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Conferir Item</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                    <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-m-{{ $req->id }}">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Recebida</label>
                            <input type="number" name="quantidade_recebida" value="{{ $req->quantity }}" min="0" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Foto</label>
                            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" capture="environment" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Resultado</label>
                            <select name="resultado" required onchange="atualizaResultadoMobile{{ $req->id }}(this.value)"
                                    style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                <option value="ok">OK</option>
                                <option value="divergente">Divergente</option>
                            </select>
                        </div>

                        <div id="campo-observacao-m-{{ $req->id }}" style="display:none; margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação</label>
                            <textarea name="observacao_conferencia" rows="3"
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                        </div>

                        <input type="hidden" name="acao" id="campo-acao-m-{{ $req->id }}" value="salvar">

                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" onclick="document.getElementById('modal-conferir-m-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit" onclick="document.getElementById('campo-acao-m-{{ $req->id }}').value='salvar'"
                                    style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Salvar
                            </button>
                            @if($req->tipo_entrega === 'entrega_direta')
                            <button type="submit" id="btn-avancar-m-{{ $req->id }}" onclick="document.getElementById('campo-acao-m-{{ $req->id }}').value='avancar_mesmo_assim'"
                                    style="display:none; padding:9px 24px; border-radius:8px; background:#d97706; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Avançar Mesmo Assim
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function atualizaResultadoMobile{{ $req->id }}(valor) {
                document.getElementById('campo-observacao-m-{{ $req->id }}').style.display = valor === 'divergente' ? 'block' : 'none';
                var btnAvancar = document.getElementById('btn-avancar-m-{{ $req->id }}');
                if (btnAvancar) {
                    btnAvancar.style.display = valor === 'divergente' ? 'inline-block' : 'none';
                }
            }
            </script>
            @endif
        @empty
```

(The `</div>` shown as the first line above is the card's own closing tag from Task 1 — this step's content is inserted between that and the pre-existing `@empty` line, mirroring exactly where the desktop table places its modal relative to its own `@empty`.)

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: PASS (all tests — 3 more than at the start of this task)

- [ ] **Step 5: Manually verify the mobile modal renders and the desktop modal still works (no id collisions)**

Run this real tinker command:

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$req = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
\$request = Illuminate\Http\Request::create('/conferencia', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index(\$request)->render();
echo (substr_count(\$html, 'id=\"modal-conferir-' . \$req->id . '\"') === 1 ? 'Modal desktop unico: OK' : 'Duplicado ou ausente - problema') . PHP_EOL;
echo (substr_count(\$html, 'id=\"modal-conferir-m-' . \$req->id . '\"') === 1 ? 'Modal mobile unico: OK' : 'Duplicado ou ausente - problema') . PHP_EOL;
\$req->delete(); \$conferente->delete();
"
```

Expected output: both lines end in "OK" (each modal id appears exactly once — proving desktop and mobile modals don't collide and neither duplicated itself). Report the real output.

- [ ] **Step 6: Commit**

```bash
git add resources/views/conferencia/index.blade.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona modal de conferencia duplicado para o layout mobile"
```

---

### Task 3: Full regression pass

**Files:** none (verification only)

**Interfaces:** none.

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: All tests PASS except the 3 pre-existing, unrelated failures already known (`RegistrationTest` x2, `ExampleTest` x1). Total passing count should be 6 more than before this plan started.

- [ ] **Step 2: Manually confirm the desktop table still works unaffected (no regression from adding the `conf-desktop-table` class or the new mobile block)**

Run this real tinker command:

```bash
php artisan tinker --execute="
Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());
\$conferente = App\Models\User::factory()->create(['role' => 'conferente']);
Illuminate\Support\Facades\Auth::login(\$conferente);
\$req = App\Models\PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null, 'tipo_entrega' => 'entrega_direta']);
\$request = Illuminate\Http\Request::create('/conferencia', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\ConferenciaController();
\$html = \$controller->index(\$request)->render();
echo (str_contains(\$html, 'modal-conferir-' . \$req->id) ? 'Modal desktop presente: OK' : 'AUSENTE - regressao') . PHP_EOL;
echo (str_contains(\$html, 'btn-avancar-' . \$req->id) ? 'Botao avancar desktop presente: OK' : 'AUSENTE - regressao') . PHP_EOL;
\$req->delete(); \$conferente->delete();
"
```

Expected output: both lines end in "OK". Report the real output.

- [ ] **Step 3: Commit (only if a fix was needed)**

If Step 1 or Step 2 uncovered a regression, fix it, re-run the full suite from Step 1, then commit the fix separately. If nothing needed fixing, skip this step.

- [ ] **Step 4: Remind the human that real-device testing is the actual acceptance criterion**

This step has no command — it's a note for the controller, not the implementer subagent: per spec §6, automated tests only confirm the right markup exists. The human (Guilherme) needs to reload the page on his actual phone to confirm the cards look and behave correctly before this sub-project is considered truly done.

---

## Plan Self-Review Notes

- **Spec coverage:** §4.1 (CSS) → Task 1 Step 3. §4.2 (wrap table) → Task 1 Step 4. §4.3 (mobile card block) → Task 1 Step 5. §4.4 (duplicated modal) → Task 2 Step 3. §6 test plan (markup presence on both tabs, real render check, real-device note) → Tasks 1-3.
- §5 (out of scope: `conferir()` logic, Pendências/Entrada/Vendedor/SLA/quantity-split) has no tasks by design.
- id/function-name suffixes are consistent: `-m-` for element ids (`modal-conferir-m-`, `form-conferir-m-`, `campo-observacao-m-`, `campo-acao-m-`, `btn-avancar-m-`) and `Mobile` for the JS function name (`atualizaResultadoMobile{{ $req->id }}`), matching between Task 2's code and its tests.
- Task 2's uniqueness test (`substr_count(...) === 1`) directly guards against the exact failure mode this duplication pattern risks: an id appearing twice (invalid HTML, breaks `getElementById`) or not at all.
