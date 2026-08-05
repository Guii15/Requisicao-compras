# SLA + Dashboard de Métricas Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar os dois timestamps que faltam (`aprovado_em`, `conferencia_concluida_em`) e uma tela `/metricas` acessível a qualquer usuário logado, mostrando tempos médios de cada etapa do fluxo e uma lista de requisições "estouradas" (paradas há mais de 24h numa etapa).

**Architecture:** Task 1 grava os dois timestamps novos exatamente nos pontos onde os eventos já acontecem hoje (`AdminController::update()` quando aprova, `ConferenciaController::conferir()` quando confere). Task 2 lê esses timestamps (mais o `entrada_concluida_em` que já existe) pra calcular médias e montar a lista de estourados, numa tela nova sem restrição de papel.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`php artisan test`), SQLite (dev local), Carbon (já vem com Laravel).

## Global Constraints

- `entrada_iniciada_em` está fora de escopo — nenhuma fórmula depende dele.
- Limite de "estourado": mais de 24 horas sem avançar de etapa
  (`now()->subHours(24)` como corte).
- Dashboard acessível a qualquer usuário autenticado — nenhuma restrição
  de papel, nem no middleware da rota, nem no link do menu.
- Médias calculadas só sobre itens com os dois timestamps do intervalo
  preenchidos; sem itens suficientes, mostra "Sem dados suficientes" em
  vez de erro de divisão por zero.
- Itens `divergente` (aguardando Pendências) não entram na lista de
  estourados nesta fatia.

---

### Task 1: Timestamps de SLA (`aprovado_em`, `conferencia_concluida_em`)

**Files:**
- Create: `database/migrations/2026_08_05_000000_add_sla_timestamps_to_purchase_requests_table.php`
- Modify: `app/Models/PurchaseRequest.php`
- Modify: `app/Http/Controllers/AdminController.php:113-118` (método `update()`)
- Modify: `app/Http/Controllers/ConferenciaController.php:79-84` (método `conferir()`)
- Test: `tests/Feature/PurchaseRequestControllerTest.php` (arquivo já existente — testes de `AdminController::update()` ficam aqui hoje, mesmo o controller sendo `AdminController`, porque testam o fluxo de aprovação do lado do vendedor)
- Test: `tests/Feature/ConferenciaControllerTest.php` (arquivo já existente)

**Interfaces:**
- Produces: colunas `aprovado_em` (timestamp nullable) e
  `conferencia_concluida_em` (timestamp nullable) em `purchase_requests`,
  ambas com cast `datetime` no model `PurchaseRequest`. Task 2 lê essas
  colunas diretamente via Eloquent (`$purchaseRequest->aprovado_em`,
  `$purchaseRequest->conferencia_concluida_em`).

- [ ] **Step 1: Escrever a migration**

Crie `database/migrations/2026_08_05_000000_add_sla_timestamps_to_purchase_requests_table.php`:

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
            $table->timestamp('aprovado_em')->nullable()->after('status');
            $table->timestamp('conferencia_concluida_em')->nullable()->after('conferente_id');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['aprovado_em', 'conferencia_concluida_em']);
        });
    }
};
```

- [ ] **Step 2: Rodar a migration**

Run: `php artisan migrate`
Expected: `Migrating: 2026_08_05_000000_add_sla_timestamps_to_purchase_requests_table` seguido de `Migrated:` sem erro.

- [ ] **Step 3: Atualizar `$fillable` e `$casts` em `app/Models/PurchaseRequest.php`**

No array `$fillable`, adicione `'aprovado_em'` e `'conferencia_concluida_em'`
(qualquer posição serve, mas mantenha perto de `'status'` e
`'conferente_id'` respectivamente pra facilitar leitura):

```php
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
        'aprovado_em',
        'admin_note',
        'valor',
        'tipo_entrega',
        'status_conferencia',
        'quantidade_recebida',
        'observacao_conferencia',
        'conferente_id',
        'conferencia_concluida_em',
        'vendedor_destino',
        'quantidade_entrada',
        'entrada_concluida_em',
    ];

    protected $casts = [
        'entrada_concluida_em' => 'datetime',
        'aprovado_em' => 'datetime',
        'conferencia_concluida_em' => 'datetime',
    ];
```

(O `protected $casts` já existe no arquivo com a linha
`'entrada_concluida_em' => 'datetime'` — só adicione as duas linhas novas
dentro do array existente, não crie um segundo `protected $casts`.)

- [ ] **Step 4: Escrever o teste de `aprovado_em` em `tests/Feature/PurchaseRequestControllerTest.php`**

Adicione ao final da classe, antes do `}` de fechamento:

```php

    public function test_admin_update_sets_aprovado_em_on_first_approval(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'pendente', 'aprovado_em' => null]);

        $this->actingAs($admin)->patch(route('admin.requests.update', $purchaseRequest), [
            'status' => 'aprovado',
        ]);

        $fresh = $purchaseRequest->fresh();
        $this->assertNotNull($fresh->aprovado_em);
    }

    public function test_admin_update_preserves_aprovado_em_on_subsequent_edits(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $dataOriginal = now()->subDays(2);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'aprovado_em' => $dataOriginal,
        ]);

        $this->actingAs($admin)->patch(route('admin.requests.update', $purchaseRequest), [
            'status' => 'aprovado',
            'supplier' => 'Novo Fornecedor',
        ]);

        $fresh = $purchaseRequest->fresh();
        $this->assertTrue($dataOriginal->equalTo($fresh->aprovado_em));
    }

    public function test_admin_update_does_not_set_aprovado_em_when_rejecting(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $purchaseRequest = PurchaseRequest::factory()->create(['status' => 'pendente', 'aprovado_em' => null]);

        $this->actingAs($admin)->patch(route('admin.requests.update', $purchaseRequest), [
            'status' => 'rejeitado',
        ]);

        $this->assertNull($purchaseRequest->fresh()->aprovado_em);
    }
```

- [ ] **Step 5: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: FAIL nos 3 testes novos — `AdminController::update()` ainda não
grava `aprovado_em`.

- [ ] **Step 6: Atualizar `AdminController::update()` em `app/Http/Controllers/AdminController.php`**

Localize o bloco (linhas 113-118 no arquivo atual):

```php
        $purchaseRequest->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
            'valor'      => $request->valor ?: null,
            'supplier'   => $supplier,
        ]);
```

Substitua por:

```php
        $purchaseRequest->update([
            'status'      => $request->status,
            'admin_note'  => $request->admin_note,
            'valor'       => $request->valor ?: null,
            'supplier'    => $supplier,
            'aprovado_em' => ($request->status === 'aprovado' && $oldStatus !== 'aprovado')
                ? now()
                : $purchaseRequest->aprovado_em,
        ]);
```

(`$oldStatus` já existe logo acima desse trecho, capturado antes do
update — não precisa criar de novo.)

- [ ] **Step 7: Rodar o teste de novo e confirmar que passa**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: os 3 testes novos passam.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_08_05_000000_add_sla_timestamps_to_purchase_requests_table.php app/Models/PurchaseRequest.php app/Http/Controllers/AdminController.php tests/Feature/PurchaseRequestControllerTest.php
git commit -m "feat: adiciona timestamp aprovado_em na aprovacao de requisicao"
```

- [ ] **Step 9: Escrever o teste de `conferencia_concluida_em` em `tests/Feature/ConferenciaControllerTest.php`**

Adicione ao final da classe, antes do `}` de fechamento:

```php

    public function test_conferir_sets_conferencia_concluida_em(): void
    {
        Storage::fake('public');
        $conferente = User::factory()->create(['role' => 'conferente']);
        $purchaseRequest = PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'conferencia_concluida_em' => null,
        ]);

        $this->actingAs($conferente)->patch(route('conferencia.conferir', $purchaseRequest), [
            'quantidade_recebida' => $purchaseRequest->quantity,
            'foto' => UploadedFile::fake()->image('produto.jpg'),
            'resultado' => 'ok',
            'acao' => 'salvar',
        ]);

        $this->assertNotNull($purchaseRequest->fresh()->conferencia_concluida_em);
    }
```

- [ ] **Step 10: Rodar o teste e confirmar que falha**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: FAIL — `conferir()` ainda não grava `conferencia_concluida_em`.

- [ ] **Step 11: Atualizar `ConferenciaController::conferir()` em `app/Http/Controllers/ConferenciaController.php`**

Localize o bloco (linhas 79-84 no arquivo atual):

```php
        $purchaseRequest->update([
            'quantidade_recebida'    => $request->quantidade_recebida,
            'status_conferencia'     => $statusConferencia,
            'observacao_conferencia' => $request->observacao_conferencia,
            'conferente_id'          => auth()->id(),
        ]);
```

Substitua por:

```php
        $purchaseRequest->update([
            'quantidade_recebida'      => $request->quantidade_recebida,
            'status_conferencia'       => $statusConferencia,
            'observacao_conferencia'   => $request->observacao_conferencia,
            'conferente_id'            => auth()->id(),
            'conferencia_concluida_em' => now(),
        ]);
```

- [ ] **Step 12: Rodar o teste de novo e confirmar que passa**

Run: `php artisan test --filter=ConferenciaControllerTest`
Expected: o teste novo passa, e os outros testes desse arquivo continuam
passando (nenhuma quebra).

- [ ] **Step 13: Rodar a suíte inteira**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes de sempre (`RegistrationTest` x2,
`ExampleTest` x1), nenhuma falha nova.

- [ ] **Step 14: Commit**

```bash
git add app/Http/Controllers/ConferenciaController.php tests/Feature/ConferenciaControllerTest.php
git commit -m "feat: adiciona timestamp conferencia_concluida_em ao conferir item"
```

---

### Task 2: Dashboard de Métricas

**Files:**
- Create: `app/Http/Controllers/MetricasController.php`
- Create: `resources/views/metricas/index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Test: `tests/Feature/MetricasControllerTest.php` (novo)

**Interfaces:**
- Consumes: `PurchaseRequest::$aprovado_em`, `$conferencia_concluida_em`,
  `$entrada_concluida_em` (todos `datetime` cast, Task 1).
- Produces: rota nomeada `metricas.index` (`GET /metricas`).

- [ ] **Step 1: Escrever os testes de acesso e cálculo**

Crie `tests/Feature/MetricasControllerTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricasControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('metricas.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_plain_vendedor_can_access(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'role' => null]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_conferente_can_access(): void
    {
        $conferente = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($conferente)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_entrada_can_access(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);

        $response = $this->actingAs($entrada)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_admin_can_access(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('metricas.index'));

        $response->assertOk();
    }

    public function test_tempo_medio_conferencia_calculado_corretamente(): void
    {
        $user = User::factory()->create();
        $base = now()->subDays(5);

        PurchaseRequest::factory()->create([
            'aprovado_em' => $base,
            'conferencia_concluida_em' => $base->copy()->addHours(2),
        ]);
        PurchaseRequest::factory()->create([
            'aprovado_em' => $base,
            'conferencia_concluida_em' => $base->copy()->addHours(4),
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('3h');
    }

    public function test_mostra_sem_dados_suficientes_quando_nao_ha_itens(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('Sem dados suficientes');
    }

    public function test_item_aguardando_conferencia_ha_mais_de_24h_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'aprovado_em' => now()->subHours(30),
            'product_name' => 'Produto Estourado Conferencia',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('Produto Estourado Conferencia');
        $response->assertSee('Aguardando conferência');
    }

    public function test_item_aguardando_entrada_ha_mais_de_24h_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'conferencia_concluida_em' => now()->subHours(30),
            'entrada_concluida_em' => null,
            'product_name' => 'Produto Estourado Entrada',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertSee('Produto Estourado Entrada');
        $response->assertSee('Aguardando entrada');
    }

    public function test_item_aprovado_ha_menos_de_24h_nao_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => null,
            'aprovado_em' => now()->subHours(2),
            'product_name' => 'Produto Recente Nao Estourado',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertDontSee('Produto Recente Nao Estourado');
    }

    public function test_item_divergente_nao_aparece_estourado_mesmo_apos_24h(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'aprovado_em' => now()->subHours(48),
            'conferencia_concluida_em' => now()->subHours(40),
            'product_name' => 'Produto Divergente Antigo',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertDontSee('Produto Divergente Antigo');
    }

    public function test_item_rejeitado_apos_aprovado_nao_aparece_estourado(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'status' => 'rejeitado',
            'status_conferencia' => null,
            'aprovado_em' => now()->subHours(48),
            'product_name' => 'Produto Aprovado E Depois Rejeitado',
        ]);

        $response = $this->actingAs($user)->get(route('metricas.index'));

        $response->assertDontSee('Produto Aprovado E Depois Rejeitado');
    }
}
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=MetricasControllerTest`
Expected: FAIL — a rota `metricas.index` ainda não existe
(`RouteNotFoundException`).

- [ ] **Step 3: Criar `app/Http/Controllers/MetricasController.php`**

```php
<?php

namespace App\Http\Controllers;

use App\Models\PurchaseRequest;
use Carbon\Carbon;

class MetricasController extends Controller
{
    public function index()
    {
        $tempoConferencia = $this->mediaEmHoras('aprovado_em', 'conferencia_concluida_em');
        $tempoEntrada = $this->mediaEmHoras('conferencia_concluida_em', 'entrada_concluida_em');
        $tempoCiclo = $this->mediaEmHoras('aprovado_em', 'entrada_concluida_em');

        $limite = now()->subHours(24);

        $estouradosConferencia = PurchaseRequest::where('status', 'aprovado')
            ->whereNotNull('aprovado_em')
            ->whereNull('status_conferencia')
            ->where('aprovado_em', '<=', $limite)
            ->orderBy('aprovado_em')
            ->get()
            ->map(fn ($r) => $this->itemEstourado($r, 'Aguardando conferência', $r->aprovado_em));

        $estouradosEntrada = PurchaseRequest::where('status', 'aprovado')
            ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim'])
            ->whereNull('entrada_concluida_em')
            ->whereNotNull('conferencia_concluida_em')
            ->where('conferencia_concluida_em', '<=', $limite)
            ->orderBy('conferencia_concluida_em')
            ->get()
            ->map(fn ($r) => $this->itemEstourado($r, 'Aguardando entrada', $r->conferencia_concluida_em));

        $estourados = $estouradosConferencia->concat($estouradosEntrada)->sortBy('desde')->values();

        return view('metricas.index', compact('tempoConferencia', 'tempoEntrada', 'tempoCiclo', 'estourados'));
    }

    private function mediaEmHoras(string $campoInicio, string $campoFim): ?float
    {
        $registros = PurchaseRequest::whereNotNull($campoInicio)
            ->whereNotNull($campoFim)
            ->get([$campoInicio, $campoFim]);

        if ($registros->isEmpty()) {
            return null;
        }

        $totalHoras = $registros->sum(
            fn ($r) => $r->{$campoInicio}->diffInMinutes($r->{$campoFim}) / 60
        );

        return round($totalHoras / $registros->count(), 1);
    }

    private function itemEstourado(PurchaseRequest $r, string $etapa, Carbon $desde): array
    {
        return [
            'id' => $r->id,
            'product_name' => $r->product_name,
            'requester_name' => $r->requester_name,
            'etapa' => $etapa,
            'desde' => $desde,
            'horas_parado' => round($desde->diffInMinutes(now()) / 60, 1),
        ];
    }
}
```

**Nota importante**: o filtro `->where('status', 'aprovado')` nas duas
queries de estourados é essencial — sem ele, uma requisição que foi
aprovada e depois **rejeitada** (status volta pra `rejeitado`, mas
`aprovado_em` continua preenchido pela regra de "preservar" da Task 1)
ficaria aparecendo como "estourada" pra sempre. Mesmo tipo de bug que foi
encontrado e corrigido no `EntradaController` durante o sub-projeto 8
(Tela de Entrada) — lá também faltava checar `status === 'aprovado'` além
de `status_conferencia`.

- [ ] **Step 4: Adicionar a rota em `routes/web.php`**

Dentro do grupo genérico `Route::middleware('auth')->group(function () {
... })` (o mesmo grupo que hoje só tem as rotas `profile.*`), adicione, e
não esqueça o `use` no topo do arquivo:

```php
use App\Http\Controllers\MetricasController;
```

```php
    Route::get('/metricas', [MetricasController::class, 'index'])->name('metricas.index');
```

- [ ] **Step 5: Criar `resources/views/metricas/index.blade.php`**

```blade
@extends('layouts.app')

@section('content')

<style>
.met-mobile-cards { display: none; }
@media (max-width: 768px) {
    .met-desktop-table { display: none; }
    .met-mobile-cards  { display: block; }
    .met-stats { grid-template-columns: 1fr !important; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Métricas</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Tempo médio de cada etapa e requisições estouradas</p>
    </div>

    <div class="met-stats" style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #05018D;">
            <p style="margin:0; font-size:{{ $tempoConferencia !== null ? '26px' : '15px' }}; font-weight:800; color:#05018D;">
                {{ $tempoConferencia !== null ? $tempoConferencia . 'h' : 'Sem dados suficientes' }}
            </p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Tempo médio de conferência</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #16a34a;">
            <p style="margin:0; font-size:{{ $tempoEntrada !== null ? '26px' : '15px' }}; font-weight:800; color:#16a34a;">
                {{ $tempoEntrada !== null ? $tempoEntrada . 'h' : 'Sem dados suficientes' }}
            </p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Tempo médio de entrada</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #b40000;">
            <p style="margin:0; font-size:{{ $tempoCiclo !== null ? '26px' : '15px' }}; font-weight:800; color:#b40000;">
                {{ $tempoCiclo !== null ? $tempoCiclo . 'h' : 'Sem dados suficientes' }}
            </p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Tempo total do ciclo</p>
        </div>
    </div>

    <div style="margin-bottom:12px; font-size:15px; font-weight:700; color:#111827;">Requisições Estouradas</div>

    <div class="met-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Etapa</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Parado desde</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Tempo parado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estourados as $item)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $item['product_name'] }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $item['requester_name'] ?? '—' }}</td>
                            <td style="padding:12px 16px;">
                                <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">{{ $item['etapa'] }}</span>
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $item['desde']->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#b40000;">{{ $item['horas_parado'] }}h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma requisição estourada no momento
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="met-mobile-cards">
        @forelse($estourados as $item)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:6px;">{{ $item['product_name'] }}</div>
                <span style="display:inline-block; margin-bottom:10px; background:#fef3c7; color:#d97706; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">{{ $item['etapa'] }}</span>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $item['requester_name'] ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Tempo parado</span>
                        <div style="font-weight:700; color:#b40000;">{{ $item['horas_parado'] }}h</div>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhuma requisição estourada no momento</p>
            </div>
        @endforelse
    </div>

</div>

@endsection
```

- [ ] **Step 6: Adicionar o link "📊 Métricas" no menu (desktop e mobile) em `resources/views/layouts/navigation.blade.php`**

No bloco desktop, logo depois do fechamento `</div>` da lista de links
condicionados a papel (ou seja, como o ÚLTIMO link antes do `</div>` que
fecha `class="hidden sm:flex sm:items-center sm:ms-8"`), adicione, **sem
nenhum `@if` de papel** (visível pra todo `Auth::user()`):

```blade
                    <a href="{{ route('metricas.index') }}"
                       style="color: {{ request()->routeIs('metricas.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('metricas.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('metricas.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('metricas.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        📊 Métricas
                    </a>
```

No bloco mobile, logo depois do último `@endif` da lista de links
condicionados a papel (antes do `</div>` que fecha `class="pt-2 pb-3
px-4"`), adicione, também sem `@if` de papel:

```blade
            <a href="{{ route('metricas.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px; margin-top:2px;">📊 Métricas</a>
```

- [ ] **Step 7: Rodar os testes de novo e confirmar que passam**

Run: `php artisan test --filter=MetricasControllerTest`
Expected: os 11 testes do arquivo passam.

- [ ] **Step 8: Rodar a suíte inteira**

Run: `php artisan test`
Expected: mesmas 3 falhas pré-existentes de sempre, nenhuma nova.

- [ ] **Step 9: Limpar cache de views**

Run: `php artisan view:clear`

- [ ] **Step 10: Commit**

```bash
git add app/Http/Controllers/MetricasController.php resources/views/metricas/index.blade.php routes/web.php resources/views/layouts/navigation.blade.php tests/Feature/MetricasControllerTest.php
git commit -m "feat: adiciona dashboard de metricas de SLA"
```
