# Módulo de Conferência — Sub-projeto 8: Tela de Entrada

> Referência geral do módulo completo: `docs/PLANO-CONFERENCIA.md`, seção 4.3.
> Referências dos sub-projetos anteriores: Tela do Conferente (3, 4, 5),
> status de conferência na Tela do Vendedor (6), Tela de Pendências (7).

## 1. Objetivo desta fatia

Depois que um item passa pela conferência com sucesso (`conferido_ok`) ou é
liberado apesar de divergência (`avancado_mesmo_assim`), alguém do depósito
precisa fisicamente registrar que o produto entrou — pra quem vai (vendedor
destino) e quanto de fato entrou. Hoje isso não existe no sistema: o único
sinal que o "pessoal da entrada" recebe é um e-mail automático quando a
compra é aprovada (`ENTRADA_EMAIL`), sem nenhuma tela.

**Descoberta durante o brainstorming**: o `PLANO-CONFERENCIA.md` descreve
essa fatia como "AJUSTAR existente", mas não existe nenhuma Tela de Entrada
no código hoje — só o e-mail. Esta fatia cria a tela do zero.

## 2. Contexto relevante do sistema atual

- Roles existentes: `admin` (`is_admin`, faz o papel de comprador) e
  `conferente` (`role = 'conferente'`, via `User::isConferente()` que
  também libera admin). Não existe role de entrada.
- `AdminController::update()` (`app/Http/Controllers/AdminController.php:120-129`)
  já dispara um e-mail (`PurchaseRequestApproved`) pro `ENTRADA_EMAIL` quando
  a requisição vira `aprovado` — esse e-mail continua existindo, é
  independente desta fatia (fora de escopo mexer nele).
- `status_conferencia` pode valer `null`, `conferido_ok`, `divergente`,
  `avancado_mesmo_assim`, `cancelado` (`cancelado` criado no sub-projeto 7,
  via Tela de Pendências).
- `ConferenteMiddleware` (`app/Http/Middleware/ConferenteMiddleware.php`) é
  o padrão a seguir pra criar `EntradaMiddleware`.
- `fotosConferencia()` (hasMany ConferenciaFoto) e `Storage::url(...)` já
  são usados pra exibir a foto tirada na conferência — mesmo padrão da Tela
  de Pendências (`resources/views/pendencias/index.blade.php`, link "Ver foto").

## 3. Decisões tomadas no brainstorming

1. **Escopo confirmado**: tela nova do zero, não é ajuste de nada existente.
2. **Acesso**: nova role `entrada` (`User::isEntrada()` = `role === 'entrada' || isAdmin()`,
   mesmo padrão de `isConferente()`), novo `EntradaMiddleware` espelhando
   `ConferenteMiddleware`.
3. **Sem status novo — só timestamp**: em vez de criar um campo de status
   pra "entrada realizada" (que duplicaria informação e poderia conflitar
   com o `status_conferencia` já existente), o sinal de "já entrou" é
   simplesmente `entrada_concluida_em` estar preenchido ou não. Mais simples,
   sem redundância.
4. **Sem timestamp de fila nesta fatia**: o `PLANO-CONFERENCIA.md` pede um
   `entrada_iniciada_em` (quando alguém abre o item pela primeira vez, pra
   medir tempo de fila). Isso exigiria um mecanismo novo (chamada ao
   servidor quando um modal abre, sem recarregar a página) que não existe
   em nenhuma tela do sistema hoje. Decisão: fica pra uma fatia futura de
   SLA (o próprio plano já separa "timestamps de SLA" como item posterior
   à Tela de Entrada). Esta fatia só registra `entrada_concluida_em`.
5. **Campos novos em `purchase_requests`**:
   - `vendedor_destino` (string, nullable) — pra quem vai o produto,
     pré-preenchido com `requester_name` mas editável (o campo original
     `requester_name` nunca é sobrescrito).
   - `quantidade_entrada` (integer, nullable) — quantidade que de fato
     entrou, pré-preenchida com `quantidade_recebida` mas editável (pode
     divergir se, por algum motivo, menos for enviado ao vendedor).
   - `entrada_concluida_em` (timestamp, nullable).
6. **Lista se limpa sozinha**: mesmo padrão da Tela de Pendências — assim
   que "Dar Entrada" é clicado, o item some da lista (não precisa de aba de
   histórico nesta fatia).
7. **Sem upload de foto próprio**: reaproveita a foto já tirada na
   conferência (`fotosConferencia`), mesmo link "Ver foto" já usado na Tela
   de Pendências.
8. **Etiqueta "Entrada Realizada" na Tela do Vendedor**: adicionar um 6º
   estado (verde), que tem prioridade sobre todos os outros — se
   `entrada_concluida_em` está preenchido, mostra "Entrada Realizada"
   independente do valor de `status_conferencia`.

## 4. Mudanças

### 4.1 Migration

Nova migration `add_entrada_fields_to_purchase_requests_table`:
```php
Schema::table('purchase_requests', function (Blueprint $table) {
    $table->string('vendedor_destino')->nullable()->after('quantidade_recebida');
    $table->integer('quantidade_entrada')->nullable()->after('vendedor_destino');
    $table->timestamp('entrada_concluida_em')->nullable()->after('quantidade_entrada');
});
```
Com `down()` revertendo (`dropColumn(['vendedor_destino', 'quantidade_entrada', 'entrada_concluida_em'])`).

### 4.2 `app/Models/User.php`

Adiciona:
```php
public function isEntrada(): bool
{
    return $this->role === 'entrada' || $this->isAdmin();
}
```

### 4.3 `app/Http/Middleware/EntradaMiddleware.php` (novo)

Idêntico a `ConferenteMiddleware.php`, trocando `isConferente()` por
`isEntrada()`.

### 4.4 `app/Models/PurchaseRequest.php`

Adiciona `vendedor_destino`, `quantidade_entrada`, `entrada_concluida_em`
ao `$fillable`.

### 4.5 `routes/web.php`

Novo grupo, mesmo padrão dos outros:
```php
Route::middleware(['auth', EntradaMiddleware::class])->prefix('entrada')->name('entrada.')->group(function () {
    Route::get('/', [EntradaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [EntradaController::class, 'darEntrada'])->name('darEntrada');
});
```

### 4.6 `app/Http/Controllers/EntradaController.php` (novo)

```php
public function index()
{
    $requests = PurchaseRequest::with(['user', 'conferente', 'fotosConferencia'])
        ->whereIn('status_conferencia', ['conferido_ok', 'avancado_mesmo_assim'])
        ->whereNull('entrada_concluida_em')
        ->latest()
        ->paginate(15);

    return view('entrada.index', compact('requests'));
}

public function darEntrada(Request $request, PurchaseRequest $purchaseRequest)
{
    if (!in_array($purchaseRequest->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)
        || $purchaseRequest->entrada_concluida_em !== null) {
        abort(409, 'Este item já teve entrada registrada ou não está mais liberado pela conferência.');
    }

    $request->validate([
        'vendedor_destino'    => 'required|string|max:255',
        'quantidade_entrada'  => 'required|integer|min:0',
    ]);

    $purchaseRequest->update([
        'vendedor_destino'     => $request->vendedor_destino,
        'quantidade_entrada'   => $request->quantidade_entrada,
        'entrada_concluida_em' => now(),
    ]);

    return redirect()->route('entrada.index')->with('success', 'Entrada registrada com sucesso!');
}
```

### 4.7 `resources/views/entrada/index.blade.php` (novo)

Mesmo padrão visual/técnico de `pendencias/index.blade.php` (tabela desktop
+ cards mobile, `@media (max-width: 768px)`, modal por item). Por item:
produto, vendedor (original), fornecedor, qtd solicitada / recebida, link
"Ver foto", aviso "⚠ Avançado Mesmo Assim" quando aplicável, e no modal os
dois campos editáveis (Vendedor Destino pré-preenchido com
`requester_name`, Quantidade pré-preenchida com `quantidade_recebida`) +
botão "Dar Entrada".

### 4.8 `resources/views/layouts/navigation.blade.php`

Novo link "📦 Entrada", condicionado a `Auth::user()->isEntrada()`, nos
blocos desktop e mobile — mesmo padrão dos links "🔍 Conferência" e "📋 Pendências".

### 4.9 `resources/views/requests/index.blade.php` — etiqueta "Entrada Realizada"

Nos dois blocos (desktop e mobile) da cascata de etiquetas de conferência,
adiciona um novo `@if` **antes** de todos os outros:
```blade
@if($req->entrada_concluida_em)
    <span style="...; background:#dcfce7; color:#16a34a; ...">Entrada Realizada</span>
@elseif($req->status_conferencia === 'cancelado')
    ... (resto da cascata como já está hoje)
```
(A cascata atual vira `@if` → `@elseif` a partir daqui, com o `@if` original
de `conferido_ok` virando mais um `@elseif` no meio.)

## 5. Fora de escopo

- Timestamp `entrada_iniciada_em` / tempo de fila — fatia futura de SLA.
- Dashboard de métricas.
- Alterar o e-mail automático (`ENTRADA_EMAIL`) já existente.
- Tela de Vendedor mostrar a foto (PLANO §4.4 menciona isso, mas é uma
  fatia separada, não decidida ainda).
- Editar/reverter uma entrada já registrada.

## 6. Plano de teste (local apenas)

1. Feature tests em `tests/Feature/EntradaControllerTest.php` (novo):
   - Acesso: guest redireciona, usuário comum 403, conferente 403 (a menos
     que também seja admin), role `entrada` acessa, admin acessa.
   - `index()` lista só `status_conferencia IN (conferido_ok, avancado_mesmo_assim)`
     com `entrada_concluida_em` nulo — não mostra `divergente`, não mostra
     `cancelado`, não mostra item já com `entrada_concluida_em` preenchido.
   - `darEntrada()` preenche `vendedor_destino`, `quantidade_entrada`,
     `entrada_concluida_em`.
   - `darEntrada()` exige `vendedor_destino` e `quantidade_entrada`.
   - 409 ao tentar dar entrada de novo num item já concluído, ou num item
     que não está mais `conferido_ok`/`avancado_mesmo_assim`.
2. Feature tests em `tests/Feature/PurchaseRequestControllerTest.php`
   (arquivo já existente): etiqueta "Entrada Realizada" aparece quando
   `entrada_concluida_em` preenchido, com prioridade sobre qualquer valor
   de `status_conferencia` (ex: mesmo com `status_conferencia = 'cancelado'`,
   se `entrada_concluida_em` está preenchido — caso extremo — mostra
   "Entrada Realizada", não "Cancelado").
3. Confirmar visualmente em local: dar entrada de verdade num item de
   teste, ver ele sumir da Tela de Entrada e aparecer "Entrada Realizada"
   na Tela do Vendedor.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
