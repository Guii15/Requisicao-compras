# Campo `tipo_entrega` no Formulário do Vendedor — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let the vendedor choose `tipo_entrega` (`estoque` or `entrega_direta`) when creating or editing a purchase request, instead of always relying on the DB default.

**Architecture:** Laravel 12 app. Adds one `<select>` field to each of the two existing vendor-facing forms (`requests/create.blade.php`, `requests/edit.blade.php`) and one validation rule + one persisted field to each of the two corresponding `PurchaseRequestController` actions (`store`, `update`). No new files except tests — this is a targeted edit to four existing files.

**Tech Stack:** Laravel 12, PHP 8.4, Blade, PHPUnit (via `php artisan test`).

## Global Constraints

- `tipo_entrega` is one shared value per form submission (like `urgency`/`supplier`/`reason`), not per individual product row within the same multi-product create submission.
- The field must appear on both the create form and the edit form.
- Label: "Tipo de Entrega". Options: `estoque` → "Estoque (CD)" (default/pre-selected), `entrega_direta` → "Entrega Direta (Dropship)".
- No changes to the admin panel (`resources/views/admin/*`, `AdminController`) in this plan.
- No changes to Tela do Conferente, Tela de Pendências, or any other future slice — this plan only touches the vendor create/edit forms.
- All testing is local only (SQLite in-memory test DB via existing `phpunit.xml` config).
- Follow existing validation-message style in this repo (custom message per rule, e.g. `'urgency.required' => 'Selecione a urgência.'` in both `store()` and `update()`).

Spec reference: `docs/superpowers/specs/2026-08-03-tipo-entrega-formulario-vendedor-design.md`

---

## File Structure

- Modify: `resources/views/requests/create.blade.php:220-227` — insert the `tipo_entrega` select as the row-3/column-2 field (next to "Motivo", which currently sits alone in that grid row).
- Modify: `resources/views/requests/edit.blade.php:65-67` — insert a new grid row containing the `tipo_entrega` select (paired with an empty placeholder div, matching the existing 2-column grid pattern).
- Modify: `app/Http/Controllers/PurchaseRequestController.php` — add `tipo_entrega` validation + persistence to `store()` (lines ~166-206) and `update()` (lines ~119-149).
- Create: `tests/Feature/PurchaseRequestControllerTest.php` — first feature test file for this controller; covers create and edit flows for `tipo_entrega`.

---

### Task 1: `tipo_entrega` on the create flow

**Files:**
- Modify: `resources/views/requests/create.blade.php`
- Modify: `app/Http/Controllers/PurchaseRequestController.php` (`store()` method)
- Test: `tests/Feature/PurchaseRequestControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::factory()` (from the base sub-project, already in the codebase at `database/factories/PurchaseRequestFactory.php`), the existing `tipo_entrega` column (string, default `'estoque'`, added in the base sub-project's migration).
- Produces: `PurchaseRequestController::store()` validates and persists `tipo_entrega` on every `PurchaseRequest` row created from one form submission.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/PurchaseRequestControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'requester_name' => 'Vendedor Teste',
            'supplier' => 'Fornecedor Teste',
            'urgency' => 'media',
            'reason' => 'Reposição',
            'justification' => 'Filial 31',
            'tipo_entrega' => 'estoque',
            'products' => [
                ['product_name' => 'Produto A', 'quantity' => 2],
                ['product_name' => 'Produto B', 'quantity' => 1],
            ],
        ], $overrides);
    }

    public function test_store_persists_tipo_entrega_on_every_product_row(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(
            route('requests.store'),
            $this->validStorePayload(['tipo_entrega' => 'entrega_direta'])
        );

        $this->assertDatabaseCount('purchase_requests', 2);
        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto A', 'tipo_entrega' => 'entrega_direta']);
        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto B', 'tipo_entrega' => 'entrega_direta']);
    }

    public function test_store_defaults_to_estoque_when_selected_in_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('requests.store'), $this->validStorePayload());

        $this->assertDatabaseHas('purchase_requests', ['product_name' => 'Produto A', 'tipo_entrega' => 'estoque']);
    }

    public function test_store_rejects_invalid_tipo_entrega(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('requests.store'),
            $this->validStorePayload(['tipo_entrega' => 'algo_invalido'])
        );

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertDatabaseCount('purchase_requests', 0);
    }

    public function test_store_rejects_missing_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $payload = $this->validStorePayload();
        unset($payload['tipo_entrega']);

        $response = $this->actingAs($user)->post(route('requests.store'), $payload);

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertDatabaseCount('purchase_requests', 0);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: FAIL — `test_store_persists_tipo_entrega_on_every_product_row` and `test_store_defaults_to_estoque_when_selected_in_form` fail because `tipo_entrega` is never persisted by `store()` today (column stays at its DB default regardless of what's posted, so the `entrega_direta` assertion fails). `test_store_rejects_invalid_tipo_entrega` and `test_store_rejects_missing_tipo_entrega` fail because there's no validation rule for `tipo_entrega` yet, so the request succeeds and rows get created (the `assertDatabaseCount(...,0)` assertions fail).

- [ ] **Step 3: Add validation and persistence in `store()`**

In `app/Http/Controllers/PurchaseRequestController.php`, update the `store()` method's `$request->validate([...])` call — add `tipo_entrega` to both the rules array and the custom-messages array:

```php
        $request->validate([
            'requester_name'          => 'required|string|max:255',
            'supplier'                => 'nullable|string|max:255',
            'urgency'                 => 'required|in:baixa,media,alta',
            'reason'                  => 'required|string|max:255',
            'justification'           => 'required|string|max:500',
            'tipo_entrega'            => 'required|in:estoque,entrega_direta',
            'products'                => 'required|array|min:1',
            'products.*.product_name' => 'required|string|max:255',
            'products.*.product_code' => 'nullable|string|max:100',
            'products.*.product_url'  => 'nullable|string|max:2048',
            'products.*.quantity'     => 'required|integer|min:1',
        ], [
            'requester_name.required'          => 'O nome do vendedor é obrigatório.',
            'urgency.required'                 => 'Selecione a urgência.',
            'reason.required'                  => 'O motivo é obrigatório.',
            'tipo_entrega.required'            => 'Selecione o tipo de entrega.',
            'tipo_entrega.in'                   => 'Tipo de entrega inválido.',
            'products.required'                => 'Adicione pelo menos um produto.',
            'products.*.product_name.required' => 'Preencha o nome do produto em todos os itens.',
            'products.*.quantity.required'     => 'Preencha a quantidade em todos os itens.',
            'products.*.quantity.min'          => 'A quantidade mínima é 1.',
            'justification.required'           => 'O campo Obs é obrigatório.',
        ]);
```

Then update the `PurchaseRequest::create([...])` call inside the `foreach ($request->products as $product)` loop to include `tipo_entrega`:

```php
            $created[] = PurchaseRequest::create([
                'user_id'        => Auth::id(),
                'requester_name' => $request->requester_name,
                'supplier'       => $request->supplier,
                'urgency'        => $request->urgency,
                'reason'         => $request->reason,
                'justification'  => $request->justification,
                'tipo_entrega'   => $request->tipo_entrega,
                'product_name'   => $product['product_name'],
                'product_code'   => $product['product_code'] ?? null,
                'product_url'    => $product['product_url'] ?? null,
                'quantity'       => $product['quantity'],
                'status'         => 'pendente',
            ]);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: PASS (4 tests) — `test_store_rejects_missing_tipo_entrega` and `test_store_rejects_invalid_tipo_entrega` will still be part of this run; they should already pass once the `required|in:` rule exists.

- [ ] **Step 5: Add the field to the create form**

In `resources/views/requests/create.blade.php`, insert a new field right after the "Motivo" field's closing `</div>` (the one at line 226, immediately before the blank line that precedes the "Obs" field at line 228). The "Motivo" field currently sits alone in its grid row (the grid is `1fr 1fr`), so this becomes its row partner:

```html
                        <div>
                            <label style="{{ $labelStyle }}">Tipo de Entrega <span style="color:#ef4444;">*</span></label>
                            <select name="tipo_entrega" required style="{{ $inputStyle }}"
                                    onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                                    onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                <option value="estoque" {{ old('tipo_entrega', 'estoque')=='estoque' ? 'selected' : '' }}>Estoque (CD)</option>
                                <option value="entrega_direta" {{ old('tipo_entrega')=='entrega_direta' ? 'selected' : '' }}>Entrega Direta (Dropship)</option>
                            </select>
                        </div>
```

- [ ] **Step 6: Manually verify the create form renders and submits correctly**

Run: `php artisan serve --port=8130` in the background, then:

```bash
php artisan tinker --execute="
\$user = App\Models\User::first();
Illuminate\Support\Facades\Auth::login(\$user);
\$request = Illuminate\Http\Request::create('/requisicoes/nova', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\PurchaseRequestController();
\$html = \$controller->create()->render();
echo (str_contains(\$html, 'Tipo de Entrega') ? 'Campo presente: OK' : 'Campo AUSENTE') . PHP_EOL;
echo (str_contains(\$html, 'name=\"tipo_entrega\"') ? 'Select correto: OK' : 'Select AUSENTE') . PHP_EOL;
"
```

Expected output: `Campo presente: OK` and `Select correto: OK`. Stop the server afterward (`taskkill //F //IM php.exe` on Windows, or note the background process ID and kill it).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PurchaseRequestController.php resources/views/requests/create.blade.php tests/Feature/PurchaseRequestControllerTest.php
git commit -m "feat: adiciona campo tipo_entrega na criacao de requisicao"
```

---

### Task 2: `tipo_entrega` on the edit flow

**Files:**
- Modify: `resources/views/requests/edit.blade.php`
- Modify: `app/Http/Controllers/PurchaseRequestController.php` (`update()` method)
- Test: `tests/Feature/PurchaseRequestControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::factory()`, the `tipo_entrega` validation pattern established in Task 1 (`required|in:estoque,entrega_direta`, same message keys).
- Produces: `PurchaseRequestController::update()` validates and persists `tipo_entrega` when editing a pending request.

- [ ] **Step 1: Write the failing tests**

Add these methods to `tests/Feature/PurchaseRequestControllerTest.php` (inside the existing class, after the Task 1 tests):

```php
    public function test_update_changes_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'tipo_entrega' => 'estoque',
        ]);

        $this->actingAs($user)->patch(route('requests.update', $purchaseRequest), [
            'requester_name' => $purchaseRequest->requester_name,
            'supplier' => $purchaseRequest->supplier,
            'urgency' => $purchaseRequest->urgency,
            'reason' => $purchaseRequest->reason,
            'justification' => $purchaseRequest->justification,
            'tipo_entrega' => 'entrega_direta',
            'product_name' => $purchaseRequest->product_name,
            'product_code' => $purchaseRequest->product_code,
            'quantity' => $purchaseRequest->quantity,
        ]);

        $this->assertSame('entrega_direta', $purchaseRequest->fresh()->tipo_entrega);
    }

    public function test_update_rejects_invalid_tipo_entrega(): void
    {
        $user = User::factory()->create();
        $purchaseRequest = PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'tipo_entrega' => 'estoque',
        ]);

        $response = $this->actingAs($user)->patch(route('requests.update', $purchaseRequest), [
            'requester_name' => $purchaseRequest->requester_name,
            'supplier' => $purchaseRequest->supplier,
            'urgency' => $purchaseRequest->urgency,
            'reason' => $purchaseRequest->reason,
            'justification' => $purchaseRequest->justification,
            'tipo_entrega' => 'algo_invalido',
            'product_name' => $purchaseRequest->product_name,
            'product_code' => $purchaseRequest->product_code,
            'quantity' => $purchaseRequest->quantity,
        ]);

        $response->assertSessionHasErrors('tipo_entrega');
        $this->assertSame('estoque', $purchaseRequest->fresh()->tipo_entrega);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: FAIL on the two new tests — `update()` doesn't validate or persist `tipo_entrega` yet, so `test_update_changes_tipo_entrega` finds the value unchanged (`estoque`, not `entrega_direta`), and `test_update_rejects_invalid_tipo_entrega` finds no validation error was raised.

- [ ] **Step 3: Add validation and persistence in `update()`**

In `app/Http/Controllers/PurchaseRequestController.php`, update the `update()` method's `$request->validate([...])` call:

```php
        $request->validate([
            'requester_name' => 'required|string|max:255',
            'supplier'       => 'nullable|string|max:255',
            'urgency'        => 'required|in:baixa,media,alta',
            'reason'         => 'required|string|max:255',
            'justification'  => 'required|string|max:500',
            'tipo_entrega'   => 'required|in:estoque,entrega_direta',
            'product_name'   => 'required|string|max:255',
            'product_code'   => 'nullable|string|max:100',
            'product_url'    => 'nullable|url|max:2048',
            'quantity'       => 'required|integer|min:1',
        ], [
            'requester_name.required' => 'O nome do vendedor é obrigatório.',
            'urgency.required'        => 'Selecione a urgência.',
            'reason.required'         => 'O motivo é obrigatório.',
            'tipo_entrega.required'   => 'Selecione o tipo de entrega.',
            'tipo_entrega.in'         => 'Tipo de entrega inválido.',
            'product_name.required'   => 'O nome do produto é obrigatório.',
            'quantity.required'       => 'A quantidade é obrigatória.',
            'quantity.min'            => 'A quantidade mínima é 1.',
            'justification.required'  => 'O campo Obs é obrigatório.',
        ]);
```

Then update the `$purchaseRequest->update([...])` call to include `tipo_entrega`:

```php
        $purchaseRequest->update([
            'requester_name' => $request->requester_name,
            'supplier'       => $request->supplier,
            'urgency'        => $request->urgency,
            'reason'         => $request->reason,
            'justification'  => $request->justification,
            'tipo_entrega'   => $request->tipo_entrega,
            'product_name'   => $request->product_name,
            'product_code'   => $request->product_code,
            'product_url'    => $request->product_url,
            'quantity'       => $request->quantity,
        ]);
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: PASS (6 tests total — 4 from Task 1, 2 new from this task)

- [ ] **Step 5: Add the field to the edit form**

In `resources/views/requests/edit.blade.php`, insert a new grid row right after the "Urgência"/"Motivo" row closes (after line 65's `</div>`, before line 67's `<div>` that starts the "Observação" field):

```html
            <div class="edit-grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:6px;">Tipo de Entrega *</label>
                    <select name="tipo_entrega" required style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        <option value="estoque" {{ old('tipo_entrega', $purchaseRequest->tipo_entrega) === 'estoque' ? 'selected' : '' }}>Estoque (CD)</option>
                        <option value="entrega_direta" {{ old('tipo_entrega', $purchaseRequest->tipo_entrega) === 'entrega_direta' ? 'selected' : '' }}>Entrega Direta (Dropship)</option>
                    </select>
                </div>
                <div></div>
            </div>
```

- [ ] **Step 6: Manually verify the edit form renders with the current value selected**

Run: `php artisan serve --port=8130` in the background, then:

```bash
php artisan tinker --execute="
\$req = App\Models\PurchaseRequest::where('status', 'pendente')->first();
if (!\$req) { echo 'Nenhuma requisicao pendente para testar' . PHP_EOL; exit; }
Illuminate\Support\Facades\Auth::login(App\Models\User::find(\$req->user_id));
\$request = Illuminate\Http\Request::create('/requisicoes/' . \$req->id . '/editar', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\PurchaseRequestController();
\$html = \$controller->edit(\$req)->render();
echo (str_contains(\$html, 'Tipo de Entrega') ? 'Campo presente: OK' : 'Campo AUSENTE') . PHP_EOL;
"
```

Expected output: `Campo presente: OK` (or the "nenhuma requisicao pendente" message if there's no pending request locally to test with — in that case, create one first via the create form). Stop the server afterward.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/PurchaseRequestController.php resources/views/requests/edit.blade.php tests/Feature/PurchaseRequestControllerTest.php
git commit -m "feat: adiciona campo tipo_entrega na edicao de requisicao"
```

---

### Task 3: Full regression pass

**Files:** none (verification only)

**Interfaces:** none — this task only runs the full existing suite plus the new one to confirm nothing else broke.

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: All tests PASS except the 3 pre-existing, unrelated failures already known from the base sub-project (`RegistrationTest` x2 on the deliberately-absent `/register` route, `ExampleTest` x1 on `/` deliberately redirecting) — confirm the failure count and names match exactly those 3, nothing new.

- [ ] **Step 2: Manually confirm the admin panel still works unaffected**

Run: `php artisan serve --port=8130` in the background, then:

```bash
php artisan tinker --execute="
\$admin = App\Models\User::where('is_admin', true)->first();
Illuminate\Support\Facades\Auth::login(\$admin);
\$request = Illuminate\Http\Request::create('/admin', 'GET');
app()->instance('request', \$request);
\$controller = new App\Http\Controllers\AdminController();
\$html = \$controller->index(\$request)->render();
echo 'Admin renderizou: ' . strlen(\$html) . ' bytes' . PHP_EOL;
"
```

Expected: renders without error, byte count similar to before (no crash from the new `tipo_entrega` values now flowing into rows the admin panel already reads). Stop the server afterward.

- [ ] **Step 3: Commit (only if Step 1 or Step 2 required a fix)**

If a regression was found and fixed, re-run the full suite from Step 1, then commit the fix separately with a message describing what broke and why. If nothing needed fixing, skip this step.

---

## Plan Self-Review Notes

- **Spec coverage:** §4.1 (create form) → Task 1 Step 5. §4.1 validation/persistence → Task 1 Steps 3-4. §4.2 (edit form) → Task 2 Step 5. §4.3 (`update()`) → Task 2 Steps 3-4. §6 test plan (create with multiple products, missing field, edit change, invalid value, visual check) → Tasks 1-3.
- §5 (out of scope: admin panel, other slices, per-product tipo_entrega) has no tasks by design — verified no task touches `admin/index.blade.php`, `AdminController`, or adds per-product select logic to the create form's JS.
- Field names, validation rule (`required|in:estoque,entrega_direta`), and message keys (`tipo_entrega.required`, `tipo_entrega.in`) are consistent between Task 1 (`store()`) and Task 2 (`update()`).
