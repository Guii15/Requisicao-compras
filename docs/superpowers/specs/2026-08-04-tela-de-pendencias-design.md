# Módulo de Conferência — Sub-projeto 7: Tela de Pendências

> Referência geral do módulo completo: `docs/PLANO-CONFERENCIA.md`, seção 4.2.
> Referências dos sub-projetos anteriores: Tela do Conferente (3, 4, 5) e
> status de conferência na Tela do Vendedor (6).

## 1. Objetivo desta fatia

Quando o conferente marca um item como `divergente` e o `tipo_entrega` é
`estoque` (produto vai pro CD, não é dropship), esse item fica "preso" — hoje
ele só aparece na aba Conferidos da Tela do Conferente, sem nenhum lugar
específico pro admin (que faz o papel de comprador neste sistema) decidir o
que fazer. Esta fatia cria a Tela de Pendências: uma lista só com esses itens
travados, com duas ações possíveis — aceitar mesmo assim ou cancelar o item.

## 2. Contexto relevante do sistema atual

- Não existe role separada de "comprador" — quem aprova/rejeita requisição
  hoje é o `admin` (`AdminController::update()`,
  `app/Http/Middleware/AdminMiddleware.php`). A Tela de Pendências usa o
  mesmo guarda de acesso.
- `status_conferencia` é uma coluna `string` simples (não é ENUM no banco —
  `database/migrations/2026_07_31_120000_add_conferencia_fields_to_purchase_requests_table.php:16`),
  então adicionar um valor novo (`cancelado`) não precisa de migration.
- Valores existentes: `null`, `conferido_ok`, `divergente`,
  `avancado_mesmo_assim`.
- `admin_note` já existe e já é usado por `AdminController::update()` pra
  guardar a observação do admin na aprovação/rejeição — o vendedor já vê
  isso hoje via botão "Ver obs." em `resources/views/requests/index.blade.php:215-222`.
- `PurchaseRequest::conferente()` (belongsTo User) e `fotosConferencia()`
  (hasMany ConferenciaFoto, campo `caminho_arquivo` relativo ao disco
  `public`) já existem e já são usados pela Tela do Conferente.
- `ConferenciaController::conferir()` já tem o padrão de trava 409
  (`abort(409, ...)` se o item já não estiver mais no estado esperado) —
  mesmo padrão será usado aqui.
- **Efeito colateral em telas já existentes**: o novo valor `status_conferencia = 'cancelado'`
  precisa ser tratado em dois lugares que hoje assumem só 3 valores possíveis
  não-nulos:
  - `resources/views/requests/index.blade.php` (Tela do Vendedor, sub-projeto 6):
    a cascata `@if(conferido_ok) @elseif(divergente) @elseif(avancado_mesmo_assim) @elseif(status==='aprovado')`
    não tem branch pra `cancelado` — cairia no fallback "Aguardando conferência", errado.
  - `resources/views/conferencia/index.blade.php` (Tela do Conferente, aba
    Conferidos): a cascata ali é `@if(conferido_ok) @elseif(divergente) @else (avancado_mesmo_assim)`
    — um `@else` genérico que hoje assume que só sobra `avancado_mesmo_assim`.
    Um item `cancelado` cairia nesse `@else` e mostraria a etiqueta errada
    ("Avançado Mesmo Assim"). Precisa virar `@elseif` explícito + novo
    `@elseif('cancelado')`.

## 3. Decisões tomadas no brainstorming

1. **Acesso**: só admin (`AdminMiddleware`), mesmo guarda do painel Admin.
2. **Lista**: `status = 'aprovado'` AND `status_conferencia = 'divergente'`
   AND `tipo_entrega = 'estoque'`. Sem abas — a lista se limpa sozinha
   quando o admin resolve (o item some porque `status_conferencia` deixa de
   ser `divergente`). Sem "aba de resolvidas" nesta fatia.
3. **Duas ações apenas** (não três): "Aceitar Mesmo Assim" e "Cancelar Item".
   "Recontatar fornecedor" fica fora do sistema (é conversa por telefone/
   WhatsApp) — se o admin quiser registrar isso, usa o campo de observação.
4. **Aceitar Mesmo Assim** → `status_conferencia = 'avancado_mesmo_assim'`
   (reaproveita o mesmo valor que o conferente usa pra dropship — pro resto
   do fluxo, os dois casos significam a mesma coisa: "seguiu apesar da
   divergência"). Observação **opcional** (a divergência já tem a
   observação original do conferente).
5. **Cancelar Item** → `status_conferencia = 'cancelado'` (valor novo).
   Observação **obrigatória**, pra explicar o motivo (mesmo padrão de
   `required_if` já usado em `ConferenciaController::conferir()` pra
   divergência).
6. **Nota do admin**: a observação da resolução da pendência é **anexada**
   ao `admin_note` já existente (não sobrescreve a nota de aprovação
   original) — formato: nota antiga (se houver) + quebra de linha + nova
   nota, com um prefixo indicando que é da resolução de pendência.
7. **Foto**: mostra a foto tirada pelo conferente (já salva via
   `fotosConferencia`), ajuda o admin a decidir.
8. **Ajuste retroativo necessário**: `cancelado` precisa virar uma 5ª
   etiqueta (vermelha "Cancelado") na Tela do Vendedor, e uma 4ª etiqueta
   explícita na aba Conferidos da Tela do Conferente (substituindo o `@else`
   genérico por `@elseif` + novo caso).
9. **Trava 409**: só permite resolver se, no momento do PATCH, o item ainda
   estiver `status='aprovado'` + `status_conferencia='divergente'` +
   `tipo_entrega='estoque'` — mesmo padrão do `conferir()`.

## 4. Mudanças

### 4.1 Migration — nenhuma
`status_conferencia` já é `string` livre, `cancelado` é só mais um valor de
aplicação, sem mudança de schema.

### 4.2 `routes/web.php`
Novo grupo, mesmo padrão do grupo de conferência:
```php
use App\Http\Controllers\PendenciaController;

Route::middleware(['auth', AdminMiddleware::class])->prefix('pendencias')->name('pendencias.')->group(function () {
    Route::get('/', [PendenciaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [PendenciaController::class, 'resolver'])->name('resolver');
});
```

### 4.3 `app/Http/Controllers/PendenciaController.php` (novo)

```php
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
        'decisao.required'          => 'Selecione uma decisão.',
        'observacao.required_if'    => 'A observação é obrigatória ao cancelar o item.',
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

### 4.4 `resources/views/pendencias/index.blade.php` (novo)

Segue o mesmo padrão visual das outras telas do módulo (cabeçalho, tabela
desktop + cards mobile com o CSS `@media (max-width: 768px)` já usado em
`conferencia/index.blade.php` e `requests/index.blade.php`). Por item:
produto, vendedor, fornecedor, qtd solicitada vs. `quantidade_recebida`,
observação do conferente (`observacao_conferencia`), nome de quem conferiu
(`conferente->name`), foto (`fotosConferencia->first()`, via
`Storage::url($foto->caminho_arquivo)`), botão "Resolver" que abre modal com
select (Aceitar Mesmo Assim / Cancelar Item) + textarea de observação
(obrigatória só se Cancelar, mesmo toggle JS de
`conferencia/index.blade.php`).

Mensagem de lista vazia: "Nenhuma pendência no momento."

### 4.5 `resources/views/requests/index.blade.php` — nova etiqueta "Cancelado"

Nos dois blocos (desktop e mobile) da etiqueta de conferência adicionada no
sub-projeto 6, adicionar um novo `@elseif` antes do `@elseif($req->status === 'aprovado')`:
```blade
@elseif($req->status_conferencia === 'cancelado')
    <span style="...; background:#fee2e2; color:#dc2626; ...">Cancelado</span>
```
(mesma cor vermelha já usada pra "Conferido — Divergente", mas texto
diferente pra não confundir os dois estados).

### 4.6 `resources/views/conferencia/index.blade.php` — corrige o `@else` genérico

Nos dois lugares (tabela desktop e card mobile) onde hoje é:
```blade
@if($req->status_conferencia === 'conferido_ok')
    ...OK...
@elseif($req->status_conferencia === 'divergente')
    ...Divergente...
@else
    ...Avançado Mesmo Assim...
@endif
```
Troca o `@else` por `@elseif($req->status_conferencia === 'avancado_mesmo_assim')`
e adiciona um `@elseif($req->status_conferencia === 'cancelado')` novo, com
etiqueta vermelha "Cancelado".

## 5. Fora de escopo

- Aba de "pendências resolvidas" / histórico — a lista só mostra as
  pendências abertas.
- Tela de Entrada, timestamps de SLA, dashboard de métricas.
- Notificação/e-mail quando uma pendência é resolvida.
- Reverter uma pendência já resolvida (decisão em aberto no próprio
  `PLANO-CONFERENCIA.md`, seção 8).

## 6. Plano de teste (local apenas)

1. Feature tests em `tests/Feature/PendenciaControllerTest.php` (novo):
   - Acesso: guest redireciona, usuário comum recebe 403, conferente
     recebe 403 (só admin acessa), admin acessa.
   - `index()` lista só itens `aprovado` + `divergente` + `estoque` — não
     mostra `aguardando`, não mostra `conferido_ok`, não mostra
     `avancado_mesmo_assim`, não mostra `divergente` com `tipo_entrega=entrega_direta`.
   - `resolver()` com `decisao=aceitar` grava `status_conferencia=avancado_mesmo_assim`.
   - `resolver()` com `decisao=cancelar` grava `status_conferencia=cancelado`.
   - `resolver()` com `decisao=cancelar` sem `observacao` falha a validação.
   - `resolver()` com `decisao=aceitar` sem `observacao` funciona (opcional).
   - `admin_note` fica anexado (nota antiga preservada + nota nova), não
     sobrescrito.
   - 409 ao tentar resolver um item que não está mais `divergente`+`estoque`
     (idempotência/race).
2. Feature tests em `tests/Feature/PurchaseRequestControllerTest.php`
   (arquivo já existente): nova etiqueta "Cancelado" aparece quando
   `status_conferencia = 'cancelado'`, nos dois layouts (desktop e mobile).
3. Feature tests em `tests/Feature/ConferenciaControllerTest.php` (arquivo
   já existente): aba Conferidos mostra etiqueta "Cancelado" correta pra
   `status_conferencia = 'cancelado'` (não mais o `@else` genérico), nos
   dois layouts.
4. Confirmar visualmente em local: resolver uma pendência de verdade (aceitar
   e cancelar em dois itens de teste diferentes) e ver o item sumir da Tela
   de Pendências, aparecer certo na Tela do Vendedor e na aba Conferidos.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
