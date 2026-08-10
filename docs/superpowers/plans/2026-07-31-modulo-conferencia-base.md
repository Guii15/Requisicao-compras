# Módulo de Conferência — Base de Dados + Roles — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the data model and access-control foundation for the "Conferência" module (physical product check-in step) — new columns/tables plus a `conferente` role — with no new screens yet.

**Architecture:** Laravel 12 app. `purchase_requests` already stores one product per row (no header/items split), so conferência fields are added directly to that table. A new `conferencia_fotos` table holds photo records (one-to-many from `purchase_requests`). Access control follows the existing `AdminMiddleware` pattern: a new `ConferenteMiddleware` gates on a new `users.role` column, which coexists with the existing `is_admin` boolean (admins get implicit conferente access).

**Tech Stack:** Laravel 12, PHP 8.4, SQLite, PHPUnit (via `php artisan test`), Eloquent factories.

## Global Constraints

- Do not modify `is_admin` behavior or the existing `AdminMiddleware` — must remain byte-for-byte backward compatible.
- No new screens, routes, or UI in this plan — model/migration/middleware layer only.
- No SLA/timestamp columns (`aprovado_em`, `conferencia_iniciada_em`, etc.) — explicitly out of scope per spec.
- All testing is local only (SQLite in-memory test DB via existing `phpunit.xml` config). Nothing touches staging or production.
- Follow existing migration style in this repo: anonymous class migrations, `Schema::table(...)->after(...)` placement, explicit `down()` that drops what `up()` added (see `database/migrations/2026_05_13_145457_add_product_url_to_purchase_requests_table.php` for the pattern).

Spec reference: `docs/superpowers/specs/2026-07-31-modulo-conferencia-base-design.md`

---

## File Structure

- Create: `database/factories/PurchaseRequestFactory.php` — factory for `PurchaseRequest`, needed because none exists yet and every task below needs to create valid requests in tests.
- Create: `database/migrations/2026_07_31_120000_add_conferencia_fields_to_purchase_requests_table.php`
- Create: `database/migrations/2026_07_31_120100_create_conferencia_fotos_table.php`
- Create: `database/migrations/2026_07_31_120200_add_role_to_users_table.php`
- Modify: `app/Models/PurchaseRequest.php` — add new fillable fields, `conferente()` and `fotosConferencia()` relations.
- Create: `app/Models/ConferenciaFoto.php` — new model, `purchaseRequest()` relation.
- Modify: `app/Models/User.php` — add `role` to fillable, add `isConferente()`.
- Create: `app/Http/Middleware/ConferenteMiddleware.php` — mirrors `app/Http/Middleware/AdminMiddleware.php`.
- Test: `tests/Feature/PurchaseRequestFactoryTest.php`
- Test: `tests/Feature/PurchaseRequestConferenciaFieldsTest.php`
- Test: `tests/Feature/ConferenciaFotoTest.php`
- Test: `tests/Unit/UserIsConferenteTest.php`
- Test: `tests/Feature/ConferenteMiddlewareTest.php`

---

### Task 1: PurchaseRequest factory

**Files:**
- Create: `database/factories/PurchaseRequestFactory.php`
- Test: `tests/Feature/PurchaseRequestFactoryTest.php`

**Interfaces:**
- Consumes: `App\Models\User` (existing), `App\Models\PurchaseRequest` (existing, fields: `user_id`, `requester_name`, `product_name`, `product_code`, `quantity`, `reason`, `urgency`, `justification`, `status`).
- Produces: `PurchaseRequest::factory()` — usable by every later task's tests. Default `status` is `'pendente'`. State method `aprovado()` sets `status` to `'aprovado'`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PurchaseRequestFactoryTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_valid_persisted_purchase_request(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertDatabaseHas('purchase_requests', [
            'id' => $request->id,
            'status' => 'pendente',
        ]);
        $this->assertNotNull($request->user_id);
        $this->assertNotNull($request->product_name);
    }

    public function test_aprovado_state_sets_status_to_aprovado(): void
    {
        $request = PurchaseRequest::factory()->aprovado()->create();

        $this->assertSame('aprovado', $request->status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PurchaseRequestFactoryTest`
Expected: FAIL — `Class "Database\Factories\PurchaseRequestFactory" not found` (or factory resolution error), since `PurchaseRequest` has no `HasFactory` trait / factory class yet.

- [ ] **Step 3: Add `HasFactory` to the model**

In `app/Models/PurchaseRequest.php`, add the trait (keep everything else in the file unchanged):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requester_name',
        'product_name',
        'product_code',
        'product_url',
        'supplier',
        'quantity',
        'reason',
        'urgency',
        'justification',
        'status',
        'admin_note',
        'valor',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Create the factory**

Create `database/factories/PurchaseRequestFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PurchaseRequest>
 */
class PurchaseRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'requester_name' => fake()->name(),
            'product_name' => fake()->words(3, true),
            'product_code' => null,
            'quantity' => fake()->numberBetween(1, 10),
            'reason' => fake()->sentence(),
            'urgency' => fake()->randomElement(['baixa', 'media', 'alta']),
            'justification' => fake()->paragraph(),
            'status' => 'pendente',
        ];
    }

    /**
     * Indicate that the request has been approved.
     */
    public function aprovado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aprovado',
        ]);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=PurchaseRequestFactoryTest`
Expected: PASS (2 tests)

- [ ] **Step 6: Commit**

```bash
git add app/Models/PurchaseRequest.php database/factories/PurchaseRequestFactory.php tests/Feature/PurchaseRequestFactoryTest.php
git commit -m "test: adiciona factory de PurchaseRequest para uso nos testes do modulo de conferencia"
```

---

### Task 2: Conferência fields on `purchase_requests`

**Files:**
- Create: `database/migrations/2026_07_31_120000_add_conferencia_fields_to_purchase_requests_table.php`
- Modify: `app/Models/PurchaseRequest.php`
- Test: `tests/Feature/PurchaseRequestConferenciaFieldsTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::factory()` from Task 1.
- Produces: `PurchaseRequest` gains fillable `tipo_entrega` (string, default `'estoque'`), `status_conferencia` (string, nullable), `quantidade_recebida` (int, nullable), `observacao_conferencia` (text, nullable), `conferente_id` (nullable FK to `users.id`). Relation `conferente(): BelongsTo` → `User`, using foreign key `conferente_id`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PurchaseRequestConferenciaFieldsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestConferenciaFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tipo_entrega_defaults_to_estoque(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertSame('estoque', $request->fresh()->tipo_entrega);
    }

    public function test_status_conferencia_defaults_to_null(): void
    {
        $request = PurchaseRequest::factory()->create();

        $this->assertNull($request->fresh()->status_conferencia);
    }

    public function test_conferencia_fields_are_mass_assignable_and_persist(): void
    {
        $conferente = User::factory()->create();

        $request = PurchaseRequest::factory()->create([
            'tipo_entrega' => 'entrega_direta',
            'status_conferencia' => 'divergente',
            'quantidade_recebida' => 3,
            'observacao_conferencia' => 'Caixa amassada',
            'conferente_id' => $conferente->id,
        ]);

        $request = $request->fresh();
        $this->assertSame('entrega_direta', $request->tipo_entrega);
        $this->assertSame('divergente', $request->status_conferencia);
        $this->assertSame(3, $request->quantidade_recebida);
        $this->assertSame('Caixa amassada', $request->observacao_conferencia);
        $this->assertTrue($request->conferente->is($conferente));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PurchaseRequestConferenciaFieldsTest`
Expected: FAIL — `SQLSTATE... no such column: tipo_entrega` (or similar), since neither migration nor fillable/relation exist yet.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_07_31_120000_add_conferencia_fields_to_purchase_requests_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('tipo_entrega')->default('estoque')->after('valor');
            $table->string('status_conferencia')->nullable()->after('tipo_entrega');
            $table->integer('quantidade_recebida')->nullable()->after('status_conferencia');
            $table->text('observacao_conferencia')->nullable()->after('quantidade_recebida');
            $table->foreignId('conferente_id')->nullable()->after('observacao_conferencia')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['conferente_id']);
            $table->dropColumn([
                'tipo_entrega',
                'status_conferencia',
                'quantidade_recebida',
                'observacao_conferencia',
                'conferente_id',
            ]);
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `app/Models/PurchaseRequest.php`, update `$fillable` and add the `conferente()` relation:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'requester_name',
        'product_name',
        'product_code',
        'product_url',
        'supplier',
        'quantity',
        'reason',
        'urgency',
        'justification',
        'status',
        'admin_note',
        'valor',
        'tipo_entrega',
        'status_conferencia',
        'quantidade_recebida',
        'observacao_conferencia',
        'conferente_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conferente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conferente_id');
    }
}
```

- [ ] **Step 5: Run migrations for the test DB and run the test**

Run: `php artisan test --filter=PurchaseRequestConferenciaFieldsTest`
Expected: PASS (3 tests). `php artisan test` runs migrations automatically against the in-memory SQLite DB configured in `phpunit.xml` — no manual migrate step needed for tests.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_31_120000_add_conferencia_fields_to_purchase_requests_table.php app/Models/PurchaseRequest.php tests/Feature/PurchaseRequestConferenciaFieldsTest.php
git commit -m "feat: adiciona campos de conferencia em purchase_requests"
```

---

### Task 3: `conferencia_fotos` table and model

**Files:**
- Create: `database/migrations/2026_07_31_120100_create_conferencia_fotos_table.php`
- Create: `app/Models/ConferenciaFoto.php`
- Modify: `app/Models/PurchaseRequest.php`
- Test: `tests/Feature/ConferenciaFotoTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::factory()` from Task 1, `PurchaseRequest` from Task 2.
- Produces: `ConferenciaFoto` model with fillable `purchase_request_id`, `caminho_arquivo`, `nome_original`, and relation `purchaseRequest(): BelongsTo`. `PurchaseRequest` gains `fotosConferencia(): HasMany` → `ConferenciaFoto`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ConferenciaFotoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConferenciaFotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_foto_can_be_created_and_belongs_to_purchase_request(): void
    {
        $request = PurchaseRequest::factory()->create();

        $foto = ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/abc123.jpg',
            'nome_original' => 'foto.jpg',
        ]);

        $this->assertTrue($foto->purchaseRequest->is($request));
    }

    public function test_purchase_request_has_many_fotos_conferencia(): void
    {
        $request = PurchaseRequest::factory()->create();
        ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/foto1.jpg',
            'nome_original' => 'foto1.jpg',
        ]);
        ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/foto2.jpg',
            'nome_original' => 'foto2.jpg',
        ]);

        $this->assertCount(2, $request->fresh()->fotosConferencia);
    }

    public function test_deleting_purchase_request_cascades_to_fotos(): void
    {
        $request = PurchaseRequest::factory()->create();
        ConferenciaFoto::create([
            'purchase_request_id' => $request->id,
            'caminho_arquivo' => 'conferencia/2026/07/foto1.jpg',
            'nome_original' => 'foto1.jpg',
        ]);

        $request->delete();

        $this->assertDatabaseCount('conferencia_fotos', 0);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ConferenciaFotoTest`
Expected: FAIL — `Class "App\Models\ConferenciaFoto" not found`.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_07_31_120100_create_conferencia_fotos_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conferencia_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->string('caminho_arquivo');
            $table->string('nome_original')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conferencia_fotos');
    }
};
```

- [ ] **Step 4: Create the model**

Create `app/Models/ConferenciaFoto.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenciaFoto extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'caminho_arquivo',
        'nome_original',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }
}
```

- [ ] **Step 5: Add the inverse relation on `PurchaseRequest`**

In `app/Models/PurchaseRequest.php`, add the import and relation method (keep everything else from Task 2 unchanged):

```php
use Illuminate\Database\Eloquent\Relations\HasMany;
```

```php
    public function fotosConferencia(): HasMany
    {
        return $this->hasMany(ConferenciaFoto::class);
    }
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=ConferenciaFotoTest`
Expected: PASS (3 tests)

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_31_120100_create_conferencia_fotos_table.php app/Models/ConferenciaFoto.php app/Models/PurchaseRequest.php tests/Feature/ConferenciaFotoTest.php
git commit -m "feat: adiciona tabela e model conferencia_fotos"
```

---

### Task 4: `role` column and `User::isConferente()`

**Files:**
- Create: `database/migrations/2026_07_31_120200_add_role_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Unit/UserIsConferenteTest.php`

**Interfaces:**
- Consumes: `User::factory()` (existing).
- Produces: `User::isConferente(): bool` — `true` when `role === 'conferente'` or `isAdmin()` is `true`. `role` added to `$fillable`.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/UserIsConferenteTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIsConferenteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_role_conferente_is_conferente(): void
    {
        $user = User::factory()->create(['role' => 'conferente']);

        $this->assertTrue($user->isConferente());
    }

    public function test_admin_is_conferente_even_without_role(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'role' => null]);

        $this->assertTrue($admin->isConferente());
    }

    public function test_regular_user_without_role_is_not_conferente(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $this->assertFalse($user->isConferente());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserIsConferenteTest`
Expected: FAIL — `SQLSTATE... no such column: role` (mass assignment / column error), since the migration and method don't exist yet.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_07_31_120200_add_role_to_users_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `app/Models/User.php`, add `role` to `$fillable` and add `isConferente()` right after `isAdmin()`:

```php
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
    ];

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isConferente(): bool
    {
        return $this->role === 'conferente' || $this->isAdmin();
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=UserIsConferenteTest`
Expected: PASS (3 tests)

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_07_31_120200_add_role_to_users_table.php app/Models/User.php tests/Unit/UserIsConferenteTest.php
git commit -m "feat: adiciona coluna role em users e User::isConferente()"
```

---

### Task 5: `ConferenteMiddleware`

**Files:**
- Create: `app/Http/Middleware/ConferenteMiddleware.php`
- Test: `tests/Feature/ConferenteMiddlewareTest.php`

**Interfaces:**
- Consumes: `User::isConferente()` from Task 4.
- Produces: `App\Http\Middleware\ConferenteMiddleware` — a Laravel middleware class with a `handle(Request $request, Closure $next): Response` method, ready to be attached to routes in a future plan. Not registered on any route in this task.

- [ ] **Step 1: Write the failing test**

This middleware isn't attached to any route yet, so the test registers a throwaway route directly to exercise it in isolation — the same technique works regardless of which real routes adopt the middleware later.

Create `tests/Feature/ConferenteMiddlewareTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Http\Middleware\ConferenteMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ConferenteMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', ConferenteMiddleware::class])
            ->get('/_test/conferente-only', fn () => 'ok');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/_test/conferente-only');

        $response->assertRedirect(route('login'));
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get('/_test/conferente-only');

        $response->assertForbidden();
    }

    public function test_user_with_role_conferente_is_allowed(): void
    {
        $user = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($user)->get('/_test/conferente-only');

        $response->assertOk();
        $response->assertSee('ok');
    }

    public function test_admin_is_allowed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/_test/conferente-only');

        $response->assertOk();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ConferenteMiddlewareTest`
Expected: FAIL — `Class "App\Http\Middleware\ConferenteMiddleware" not found`.

- [ ] **Step 3: Write the middleware**

Create `app/Http/Middleware/ConferenteMiddleware.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConferenteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isConferente()) {
            abort(403, 'Acesso restrito.');
        }

        return $next($request);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ConferenteMiddlewareTest`
Expected: PASS (4 tests)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Middleware/ConferenteMiddleware.php tests/Feature/ConferenteMiddlewareTest.php
git commit -m "feat: adiciona ConferenteMiddleware"
```

---

### Task 6: Full regression pass

**Files:** none (verification only)

**Interfaces:** none — this task only runs the full existing suite plus the new one to confirm nothing else broke.

- [ ] **Step 1: Run the entire test suite**

Run: `php artisan test`
Expected: All tests PASS, including the pre-existing `tests/Feature/Auth/*`, `tests/Feature/ProfileTest.php`, `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`, and all 5 new test files from Tasks 1-5.

- [ ] **Step 2: Manually confirm existing screens still work locally**

Run: `php artisan serve --port=8123` (background), then in a browser (or `curl`) confirm:
- `/login` returns 200
- Log in as the existing admin (`guiiholi513@gmail.com`) and open `/admin` — dashboard, filters (including the "Produto" filter from the previous change), and the monthly modal all still work exactly as before.
- Log in as a regular vendedor and open `/requisicoes` — list and creation form still work.

This confirms the new nullable/defaulted columns didn't break any existing query or view. Stop the server afterward.

- [ ] **Step 3: Commit (only if Step 2 required a fix)**

If Step 2 uncovered a regression, fix it, re-run the full suite from Step 1, then commit the fix separately with a message describing what broke and why. If nothing needed fixing, skip this step — there's nothing to commit.

---

## Plan Self-Review Notes

- **Spec coverage:** Section 4.1 → Task 2. Section 4.2 → Task 3. Section 4.3 → Task 4. Section 5 (`PurchaseRequest`, `ConferenciaFoto`, `User` models) → Tasks 2-4. Section 6 (`ConferenteMiddleware`) → Task 5. Section 8 (test plan: migrations, tinker-equivalent assertions on relations/roles, middleware, regression check) → covered by Tasks 1-6 as automated PHPUnit tests instead of manual `tinker`, which is stronger (repeatable, runs in CI later) and was confirmed equivalent to what the spec asked to verify.
- Section 7 (out of scope) has no tasks by design — verified no task drifts into building screens, forms, uploads, or SLA timestamps.
- All foreign keys, method names, and column names are consistent across tasks (`conferente_id` / `conferente()`, `fotosConferencia()` / `ConferenciaFoto`, `isConferente()`).
