# Módulo de Conferência — Sub-projeto 2: Campo `tipo_entrega` no formulário do vendedor

> Referência geral do módulo completo: `PLANO-CONFERENCIA.md` (fornecido pelo Guilherme).
> Referência do sub-projeto anterior: `docs/superpowers/specs/2026-07-31-modulo-conferencia-base-design.md`
> (criou a coluna `tipo_entrega` em `purchase_requests`, default `'estoque'`, ainda sem
> campo no formulário).

## 1. Objetivo desta fatia

Deixar o vendedor escolher o `tipo_entrega` (`estoque` ou `entrega_direta`) ao criar ou
editar uma requisição, em vez de depender só do valor padrão do banco.

## 2. Contexto relevante do sistema atual

- `resources/views/requests/create.blade.php` tem uma seção de campos **compartilhados**
  (Vendedor, Fornecedor, Urgência, Motivo, Obs) e uma lista de **produtos** adicionados via
  JS — cada produto vira uma linha separada em `purchase_requests`, mas com os campos
  compartilhados iguais em todas as linhas geradas por aquele envio.
  `PurchaseRequestController::store()` recebe `products[]` e cria uma `PurchaseRequest` por
  item, repetindo os campos compartilhados em cada `create()`.
- `resources/views/requests/edit.blade.php` edita uma única `PurchaseRequest` já existente
  (só permitido quando `status === 'pendente'`), com formulário de produto único (sem lista).
  `PurchaseRequestController::update()` faz o mesmo tipo de validação e `update()`.
- Coluna `tipo_entrega` já existe (string, default `'estoque'`, valores de app `estoque` /
  `entrega_direta` — ver spec do sub-projeto 1).

## 3. Decisões tomadas no brainstorming

1. **`tipo_entrega` é um campo único por envio**, igual `urgency`/`supplier`/`reason` —
   não é por produto individual dentro da mesma lista. Todos os produtos criados numa
   mesma submissão do formulário recebem o mesmo valor.
2. **Aparece nas duas telas**: criar e editar. Consistência com os outros campos
   compartilhados, que já são editáveis enquanto a requisição está `pendente`.
3. **Rótulo e opções**: label "Tipo de Entrega", select com opções "Estoque (CD)" (valor
   `estoque`, pré-selecionado por padrão) e "Entrega Direta (Dropship)" (valor
   `entrega_direta`) — mesmo padrão visual do select de Urgência já existente.
4. **Fora de escopo**: painel admin (`admin/index.blade.php`) não muda nesta fatia — a
   coluna já existe desde o sub-projeto 1 e não há pedido de exibi-la lá ainda. Tela do
   Conferente e demais fatias continuam para depois.

## 4. Mudanças

### 4.1 `resources/views/requests/create.blade.php`
Novo `<select name="tipo_entrega">` na grade de campos compartilhados (junto com
Fornecedor/Urgência), mesmo estilo visual (`$inputStyle`/`$labelStyle` já definidos no
arquivo). Opções:
```html
<option value="estoque" selected>Estoque (CD)</option>
<option value="entrega_direta">Entrega Direta (Dropship)</option>
```
Usa `old('tipo_entrega', 'estoque')` para preservar o valor em caso de erro de validação,
com fallback pro padrão.

### 4.2 `resources/views/requests/edit.blade.php`
Mesmo select, na mesma seção "Informações Gerais" dos outros campos compartilhados,
preenchido com `old('tipo_entrega', $purchaseRequest->tipo_entrega)`.

### 4.3 `app/Http/Controllers/PurchaseRequestController.php`
- `store()`: adiciona `'tipo_entrega' => 'required|in:estoque,entrega_direta'` às regras de
  validação (com mensagem customizada, mesmo padrão de `urgency.required`), e
  `'tipo_entrega' => $request->tipo_entrega` dentro do `PurchaseRequest::create([...])`
  dentro do `foreach`.
- `update()`: mesma regra de validação, e `'tipo_entrega' => $request->tipo_entrega` dentro
  do `$purchaseRequest->update([...])`.

## 5. Fora de escopo

- Exibir/filtrar por `tipo_entrega` no painel admin.
- Qualquer lógica da Tela do Conferente, Tela de Pendências, ajustes em Entrada/Vendedor.
- Permitir `tipo_entrega` diferente por produto dentro do mesmo envio.

## 6. Plano de teste (local apenas)

1. Feature tests em `PurchaseRequestControllerTest` (arquivo novo, já que não existe teste
   de feature pra esse controller ainda):
   - Criar requisição com `tipo_entrega=entrega_direta` e múltiplos produtos → todas as
     linhas criadas têm `tipo_entrega === 'entrega_direta'`.
   - Omitir `tipo_entrega` no submit de criação → falha de validação (422 / erro de
     validação), já que o select sempre envia um valor mas a validação deve rejeitar
     ausência/valor fora da lista permitida.
   - Editar uma requisição pendente trocando `tipo_entrega` de `estoque` para
     `entrega_direta` → persiste corretamente.
   - Valor inválido (fora de `estoque`/`entrega_direta`) é rejeitado pela validação.
2. Confirmar visualmente em local (`php artisan serve`) que os dois formulários renderizam
   o novo campo corretamente e que o valor persiste depois de salvar.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
