# Módulo de Conferência — Sub-projeto 3: Tela do Conferente

> Referência geral do módulo completo: `PLANO-CONFERENCIA.md` (fornecido pelo Guilherme).
> Referências dos sub-projetos anteriores:
> `docs/superpowers/specs/2026-07-31-modulo-conferencia-base-design.md` (colunas/tabela/roles/middleware)
> `docs/superpowers/specs/2026-08-03-tipo-entrega-formulario-vendedor-design.md` (campo tipo_entrega no formulário)

## 1. Objetivo desta fatia

Construir a primeira tela nova de verdade do módulo: o conferente lista as requisições
aprovadas ainda não conferidas, e confere cada item individualmente (quantidade recebida,
foto, OK/Divergente), com o roteamento correto de acordo com `tipo_entrega`.

## 2. Contexto relevante do sistema atual

- `purchase_requests` já tem (do sub-projeto 1): `tipo_entrega` (`estoque`/`entrega_direta`,
  default `estoque`), `status_conferencia` (nullable — `aguardando_conferencia`,
  `conferido_ok`, `divergente`, `avancado_mesmo_assim`), `quantidade_recebida`,
  `observacao_conferencia`, `conferente_id`.
- Tabela `conferencia_fotos` já existe (do sub-projeto 1): `purchase_request_id`,
  `caminho_arquivo`, `nome_original`, timestamps. Model `ConferenciaFoto` com relação
  `purchaseRequest()`. `PurchaseRequest::fotosConferencia()` já existe (hasMany).
- `User::isConferente()` já existe (do sub-projeto 1): `true` se `role === 'conferente'`
  ou `isAdmin()` (admin tem acesso implícito).
- `App\Http\Middleware\ConferenteMiddleware` já existe (do sub-projeto 1), mas **ainda não
  está ligado a nenhuma rota**.
- `status` (pendente/aprovado/rejeitado) é o campo que já existe e controla o fluxo
  Solicitante→Comprador; ele não muda nesta fatia — `status_conferencia` é um campo
  paralelo/independente.
- Padrão visual de referência: `resources/views/admin/index.blade.php` (tabela +
  modal por linha pra "Atualizar", com `document.getElementById('modal-{{ $req->id }}')`).
- Nav: `resources/views/layouts/navigation.blade.php` já tem o padrão
  `@if(Auth::user()->isAdmin())` guardando o link "⚙ Admin" (desktop ~linha 21-28, mobile
  ~linha 94-95).
- Disco de arquivos: `config/filesystems.php` já tem o disco `public` padrão do Laravel
  configurado (`storage/app/public`, visibility `public`, URL via `APP_URL/storage`), mas
  o symlink (`php artisan storage:link`) ainda não foi criado neste projeto.

## 3. Decisões tomadas no brainstorming

1. **Escopo**: listagem + ação de conferir por item + roteamento formam um sub-projeto só
   (não quebrado em fatias menores) — são uma unidade lógica coesa.
2. **O que a listagem mostra**: só `status = 'aprovado'` E `status_conferencia` ainda vazio/
   nulo. Assim que o conferente age num item, ele desaparece da lista.
3. **Interface de ação**: modal por item, mesmo padrão visual do modal "Atualizar" que já
   existe no admin (não uma página separada).
4. **OK vs Divergente**: escolha sempre manual do conferente — o sistema não sugere nem
   força com base na quantidade recebida vs solicitada.
5. **Foto**: jpg/jpeg/png/webp, até 5MB, obrigatória em todo item conferido (OK ou
   Divergente).
6. **Roteamento exato**:
   - Resultado OK → `status_conferencia = 'conferido_ok'`.
   - Resultado Divergente, `tipo_entrega = estoque`, botão "Salvar" → `status_conferencia
     = 'divergente'`.
   - Resultado Divergente, `tipo_entrega = entrega_direta`, botão "Salvar" (normal) →
     `status_conferencia = 'divergente'` (mesmo valor que o caso estoque — só muda se o
     conferente clicar no botão especial abaixo).
   - Resultado Divergente, `tipo_entrega = entrega_direta`, botão **"Avançar Mesmo Assim"**
     (só aparece/habilita nesse cenário) → `status_conferencia = 'avancado_mesmo_assim'`.
7. **`status` da requisição não muda** nesta fatia — continua `'aprovado'`. Só
   `status_conferencia` progride.

## 4. Acesso e navegação

### 4.1 Rotas (`routes/web.php`)
Novo grupo, mesmo padrão do grupo `admin`:
```php
Route::middleware(['auth', ConferenteMiddleware::class])->prefix('conferencia')->name('conferencia.')->group(function () {
    Route::get('/', [ConferenciaController::class, 'index'])->name('index');
    Route::patch('/{purchaseRequest}', [ConferenciaController::class, 'conferir'])->name('conferir');
});
```

### 4.2 Navegação (`resources/views/layouts/navigation.blade.php`)
Novo link "🔍 Conferência" → `route('conferencia.index')`, guardado por
`@if(Auth::user()->isConferente())`, no mesmo lugar/estilo do link "⚙ Admin" (desktop e
mobile).

## 5. Controller novo: `ConferenciaController`

### `index()`
```php
$requests = PurchaseRequest::with('user')
    ->where('status', 'aprovado')
    ->whereNull('status_conferencia')
    ->latest()
    ->paginate(15);

return view('conferencia.index', compact('requests'));
```

### `conferir(Request $request, PurchaseRequest $purchaseRequest)`
- Valida:
  ```php
  $request->validate([
      'quantidade_recebida' => 'required|integer|min:0',
      'foto'                => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
      'resultado'           => 'required|in:ok,divergente',
      'observacao_conferencia' => 'required_if:resultado,divergente|nullable|string|max:500',
      'acao'                => 'required|in:salvar,avancar_mesmo_assim',
  ], [
      'quantidade_recebida.required' => 'Informe a quantidade recebida.',
      'foto.required'                => 'A foto é obrigatória.',
      'foto.image'                   => 'O arquivo precisa ser uma imagem.',
      'foto.mimes'                   => 'Formatos aceitos: jpg, jpeg, png, webp.',
      'foto.max'                     => 'A foto deve ter no máximo 5MB.',
      'resultado.required'           => 'Selecione o resultado da conferência.',
      'observacao_conferencia.required_if' => 'A observação é obrigatória quando divergente.',
  ]);
  ```
- Rejeita com 403 se `$request->acao === 'avancar_mesmo_assim'` mas
  `!($request->resultado === 'divergente' && $purchaseRequest->tipo_entrega === 'entrega_direta')`
  — o botão só deve ter efeito no cenário exato em que aparece; isso impede alguém de forjar
  o campo `acao` via POST direto pra pular a trava de estoque divergente.
- Calcula `status_conferencia`:
  ```php
  if ($request->resultado === 'ok') {
      $statusConferencia = 'conferido_ok';
  } elseif ($request->acao === 'avancar_mesmo_assim') {
      $statusConferencia = 'avancado_mesmo_assim';
  } else {
      $statusConferencia = 'divergente';
  }
  ```
- Atualiza a requisição:
  ```php
  $purchaseRequest->update([
      'quantidade_recebida'     => $request->quantidade_recebida,
      'status_conferencia'      => $statusConferencia,
      'observacao_conferencia'  => $request->observacao_conferencia,
      'conferente_id'           => auth()->id(),
  ]);
  ```
- Salva a foto:
  ```php
  $path = $request->file('foto')->store('conferencia', 'public');
  $purchaseRequest->fotosConferencia()->create([
      'caminho_arquivo' => $path,
      'nome_original'   => $request->file('foto')->getClientOriginalName(),
  ]);
  ```
- Redireciona de volta com mensagem de sucesso (`with('success', ...)`), mesmo padrão do
  `AdminController::update`.

## 6. Views

### `resources/views/conferencia/index.blade.php`
- Cabeçalho + tabela (desktop) / cards (mobile), mesmo estilo visual de
  `admin/index.blade.php` (cores, tipografia, paginação centralizada).
- Colunas: Vendedor, Produto, Fornecedor, Qtd Solicitada, Tipo de Entrega (badge — só
  destaca visualmente quando `entrega_direta`), Data, Ação ("Conferir").
- Modal por item (`id="modal-conferir-{{ $req->id }}"`), form `POST` com `@method('PATCH')`
  pra `conferencia.conferir`, `enctype="multipart/form-data"` (por causa do upload):
  - Campo Quantidade Recebida, pré-preenchido com `$req->quantity`.
  - Campo de arquivo Foto.
  - Select/radio Resultado (OK / Divergente).
  - Textarea Observação — visível/obrigatória só quando Divergente (JS simples, mesmo
    padrão de mostrar/esconder campo condicional já usado em outros formulários do
    projeto se houver, senão só um toggle JS direto).
  - Botão "Salvar".
  - Botão "Avançar Mesmo Assim" — só renderizado no HTML quando
    `$req->tipo_entrega === 'entrega_direta'`, e mostrado via JS somente quando o
    conferente seleciona "Divergente" (senão fica escondido).
- Mensagem "Nenhuma requisição aguardando conferência" quando a lista estiver vazia.

## 7. Fora de escopo

- Tela de Pendências (decisão do comprador sobre itens divergentes com `tipo_entrega =
  estoque`).
- Ajustes na Tela de Entrada e na Tela do Vendedor (colunas Entrada/Foto).
- Timestamps de SLA e dashboard de métricas.
- Qualquer mudança no campo `status` (pendente/aprovado/rejeitado) da requisição.

## 8. Plano de teste (local apenas)

1. Rodar `php artisan storage:link` localmente (necessário pra servir as fotos).
2. Feature tests em `ConferenciaControllerTest` (arquivo novo):
   - `index()` só lista requisições `aprovado` com `status_conferencia` nulo — confirma
     que uma requisição já conferida (`status_conferencia` preenchido) não aparece.
   - Acesso negado (403) pra usuário sem `role='conferente'` e sem `is_admin`; acesso
     permitido pra `role='conferente'` e pra admin.
   - `conferir()` com resultado OK grava `status_conferencia = 'conferido_ok'`,
     `quantidade_recebida`, `conferente_id`, e cria um registro em `conferencia_fotos`
     (usar `Storage::fake('public')` do Laravel pro teste de upload).
   - `conferir()` com resultado Divergente + `tipo_entrega = estoque` grava
     `status_conferencia = 'divergente'`.
   - `conferir()` com resultado Divergente + `tipo_entrega = entrega_direta` +
     `acao = avancar_mesmo_assim` grava `status_conferencia = 'avancado_mesmo_assim'`.
   - `conferir()` rejeita (403) uma tentativa de `acao = avancar_mesmo_assim` quando
     `tipo_entrega = estoque` (POST forjado, ignorando a UI).
   - `conferir()` rejeita (validação) quando `observacao_conferencia` está vazia e
     resultado é Divergente.
   - `conferir()` rejeita (validação) quando a foto está ausente.
   - `status` da requisição permanece `'aprovado'` depois de qualquer uma dessas ações.
3. Confirmar visualmente em local (`php artisan serve`) que a listagem, o modal e o
   upload de foto funcionam de ponta a ponta com um usuário `role='conferente'`.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
