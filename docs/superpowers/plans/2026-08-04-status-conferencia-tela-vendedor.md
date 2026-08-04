# Status de Conferência na Tela do Vendedor — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mostrar, na tela "Minhas Requisições" do vendedor, uma etiqueta de
status da conferência (Aguardando conferência / Conferido + resultado) ao
lado do nome do produto, sem precisar de notificação nem mudança de tela.

**Architecture:** Mudança puramente de view — `PurchaseRequest::status_conferencia`
já vem carregado no model sem nenhuma alteração de controller. Um bloco Blade
condicional idêntico é inserido em dois lugares do mesmo arquivo
(`resources/views/requests/index.blade.php`): logo abaixo do nome do produto
na tabela desktop, e logo abaixo do nome do produto no card mobile.

**Tech Stack:** Laravel 12, Blade, PHPUnit (`php artisan test`), SQLite (dev local).

## Global Constraints

- Regra de exibição (spec `docs/superpowers/specs/2026-08-04-status-conferencia-tela-vendedor-design.md`):
  - Se `status_conferencia` estiver preenchido (`conferido_ok`, `divergente`
    ou `avancado_mesmo_assim`) → mostra "Conferido" com o resultado,
    **independente do `status` da requisição**.
  - Senão, se `status === 'aprovado'` → mostra "Aguardando conferência".
  - Em qualquer outro caso (`pendente`, ou `rejeitado` sem conferência) → não
    mostra nenhuma etiqueta.
- Etiqueta é somente leitura (sem link, sem ação).
- Nenhuma mudança em `app/Http/Controllers/PurchaseRequestController.php` —
  `status_conferencia` já vem carregado sem `select()` restritivo.
- Nenhuma mudança na Tela do Conferente nem no `ConferenciaController`.
- "Deu entrada" e qualquer campo de "chegada no galpão" ficam fora de escopo
  (não existem no banco ainda).

---

### Task 1: Etiqueta de status de conferência na Tela do Vendedor

**Files:**
- Modify: `resources/views/requests/index.blade.php`
- Test: `tests/Feature/PurchaseRequestControllerTest.php`

**Interfaces:**
- Consumes: `PurchaseRequest::$status` (`'pendente'|'aprovado'|'rejeitado'`)
  e `PurchaseRequest::$status_conferencia` (`null|'conferido_ok'|'divergente'|'avancado_mesmo_assim'`) —
  ambos já existentes no model, carregados por `PurchaseRequestController::index()`
  (`app/Http/Controllers/PurchaseRequestController.php:14-16`) sem `select()`
  restritivo.
- Produces: nada consumido por tarefas futuras — esta é a única tarefa do plano.

- [ ] **Step 1: Escrever os testes que falham**

Abra `tests/Feature/PurchaseRequestControllerTest.php` e adicione estes
métodos de teste dentro da classe `PurchaseRequestControllerTest` (depois do
último método existente, antes do `}` final da classe). Cada requisição
precisa ter `user_id` igual ao usuário logado no teste, porque
`PurchaseRequestController::index()` filtra por `where('user_id', auth()->id())`.

```php
    public function test_index_shows_aguardando_conferencia_for_aprovado_without_status_conferencia(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => null,
            'product_name' => 'Produto Aguardando',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Aguardando conferência');
    }

    public function test_index_shows_conferido_ok_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto OK',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido ✓ OK');
        $response->assertDontSee('Aguardando conferência');
    }

    public function test_index_shows_divergente_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'divergente',
            'product_name' => 'Produto Divergente',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido — Divergente');
    }

    public function test_index_shows_avancado_mesmo_assim_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'avancado_mesmo_assim',
            'product_name' => 'Produto Avancado',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido — Avançado Mesmo Assim');
    }

    public function test_index_pendente_shows_no_conferencia_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pendente',
            'status_conferencia' => null,
            'product_name' => 'Produto Pendente',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Aguardando conferência');
        $response->assertDontSee('Conferido ✓ OK');
    }

    public function test_index_rejeitado_without_conferencia_shows_no_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejeitado',
            'status_conferencia' => null,
            'product_name' => 'Produto Rejeitado',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertDontSee('Aguardando conferência');
        $response->assertDontSee('Conferido ✓ OK');
    }

    public function test_index_rejeitado_with_status_conferencia_still_shows_badge(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'rejeitado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Rejeitado Conferido',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $response->assertSee('Conferido ✓ OK');
    }

    public function test_index_badge_appears_in_both_desktop_and_mobile_blocks(): void
    {
        $user = User::factory()->create();
        PurchaseRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'product_name' => 'Produto Dois Layouts',
        ]);

        $response = $this->actingAs($user)->get(route('requests.index'));

        $html = $response->getContent();
        $this->assertSame(2, substr_count($html, 'Conferido ✓ OK'));
    }
```

- [ ] **Step 2: Rodar os testes e confirmar que falham**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: FAIL — as 8 novas asserções `assertSee` não encontram os textos
("Aguardando conferência", "Conferido ✓ OK", etc.) porque a etiqueta ainda
não existe na view.

- [ ] **Step 3: Implementar o bloco na tabela desktop**

Em `resources/views/requests/index.blade.php`, localize este trecho (dentro
do `<td>` de Produto, por volta da linha 187-195):

```blade
                            <td style="padding:14px 16px; font-size:14px; color:#374151;">
                                {{ $req->product_name }}
                                @if($req->product_code)
                                    <span style="display:block; font-size:12px; color:#9ca3af;">Cód: {{ $req->product_code }}</span>
                                @endif
                                @if($req->product_url)
                                    <a href="{{ $req->product_url }}" target="_blank" style="display:block; font-size:11px; color:#1e3a8a; text-decoration:underline; margin-top:2px;">Ver link</a>
                                @endif
                            </td>
```

Substitua por (adiciona o bloco de etiqueta de conferência logo depois do
`@if($req->product_url) ... @endif`):

```blade
                            <td style="padding:14px 16px; font-size:14px; color:#374151;">
                                {{ $req->product_name }}
                                @if($req->product_code)
                                    <span style="display:block; font-size:12px; color:#9ca3af;">Cód: {{ $req->product_code }}</span>
                                @endif
                                @if($req->product_url)
                                    <a href="{{ $req->product_url }}" target="_blank" style="display:block; font-size:11px; color:#1e3a8a; text-decoration:underline; margin-top:2px;">Ver link</a>
                                @endif
                                @if($req->status_conferencia === 'conferido_ok')
                                    <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                                @elseif($req->status_conferencia === 'divergente')
                                    <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
                                @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                                @elseif($req->status === 'aprovado')
                                    <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                                @endif
                            </td>
```

- [ ] **Step 4: Implementar o bloco no card mobile**

No mesmo arquivo, localize este trecho (dentro do cabeçalho do card mobile,
por volta da linha 259-265):

```blade
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#1e3a8a;">{{ $req->product_name }}</div>
                        @if($req->product_code)
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">Cód: {{ $req->product_code }}</div>
                        @endif
                    </div>
```

Substitua por (mesmo bloco condicional de etiqueta, adicionado depois do
`@if($req->product_code) ... @endif`):

```blade
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#1e3a8a;">{{ $req->product_name }}</div>
                        @if($req->product_code)
                            <div style="font-size:12px; color:#9ca3af; margin-top:2px;">Cód: {{ $req->product_code }}</div>
                        @endif
                        @if($req->status_conferencia === 'conferido_ok')
                            <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
                        @elseif($req->status === 'aprovado')
                            <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
                        @endif
                    </div>
```

Note que este bloco `@if/@elseif/@endif` é **texto idêntico** ao do Step 3
(mesmas 4 condições, mesmas cores, mesmos textos) — só muda o elemento HTML
ao redor (`<span>` de estilo diferente vira `<div>` no card, mas o conteúdo
interno das etiquetas é o mesmo `<span>` em ambos). Isso é intencional: é o
mesmo padrão já usado no resto do arquivo para `product_code` (aparece como
`<span style="display:block;...">` na tabela e `<div style="font-size:12px;...">`
no card, mesmo texto).

- [ ] **Step 5: Rodar os testes e confirmar que passam**

Run: `php artisan test --filter=PurchaseRequestControllerTest`
Expected: PASS — todos os testes, incluindo os 8 novos, devem passar.

- [ ] **Step 6: Rodar a suíte completa pra checar regressão**

Run: `php artisan test`
Expected: mesmo número de falhas pré-existentes de antes desta tarefa (3
falhas conhecidas e não relacionadas em `ExampleTest`), nenhuma falha nova.

- [ ] **Step 7: Limpar cache de view local**

Run: `php artisan view:clear`

(Necessário porque o servidor de desenvolvimento local do Guilherme roda
por longos períodos com `php artisan serve --host=0.0.0.0 --port=8000`, e
views compiladas antigas já causaram confusão nesta mesma sessão.)

- [ ] **Step 8: Commit**

```bash
git add resources/views/requests/index.blade.php tests/Feature/PurchaseRequestControllerTest.php
git commit -m "feat: mostra status de conferencia ao lado do produto na tela do vendedor"
```
