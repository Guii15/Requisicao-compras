# Módulo de Conferência — Sub-projeto 1: Base de dados + Roles

> Referência geral do módulo completo: `PLANO-CONFERENCIA.md` (fornecido pelo Guilherme).
> Este spec cobre **apenas** a primeira fatia: fundação de dados e controle de acesso.
> Sem telas novas nesta etapa.

## 1. Objetivo desta fatia

Criar a estrutura de dados e de acesso (roles) necessária para o módulo de Conferência,
sem ainda construir nenhuma tela. Isso destrava as próximas fatias (Tela do Conferente,
Tela de Pendências, ajustes em Entrada e Vendedor) sem exigir migration nova em cada uma.

## 2. Contexto relevante do sistema atual

- `purchase_requests` já representa **um produto por linha** — não existe conceito de
  "header + itens". Por isso os campos de conferência (que no documento original estavam
  desenhados para uma tabela `requisicao_itens`) vão direto em `purchase_requests`.
- Controle de acesso hoje é só `users.is_admin` (boolean), checado pelo
  `App\Http\Middleware\AdminMiddleware`. Não existe sistema de roles.
- Fluxo de status atual: `pendente` → `aprovado` / `rejeitado` (controlado pelo admin/comprador
  em `AdminController::update`).

## 3. Decisões tomadas no brainstorming

1. **Ambiente**: desenvolvimento e testes só localmente (SQLite local), sem deploy em staging
   por enquanto. Produção não é tocada nesta fatia.
2. **`tipo_entrega` fica direto em `purchase_requests`** (não em tabela de itens separada),
   já que cada linha já é um item único.
3. **Quem define `tipo_entrega`**: o vendedor, ao criar a requisição. *(O campo no formulário
   de criação é fora de escopo desta fatia — aqui só criamos a coluna com default `'estoque'`
   para não quebrar nada; o formulário vem numa fatia futura.)*
4. **Modelo de roles**: coluna `role` (string, nullable) em `users`, coexistindo com
   `is_admin` (que continua controlando o painel admin exatamente como hoje). `role = null`
   significa vendedor comum (comportamento atual, ninguém é afetado). Não haverá enum/check
   constraint no banco (SQLite) — validação de valores permitidos fica na camada de aplicação.
5. **Admin tem acesso implícito** à área de conferente (super usuário), sem precisar de
   `role = 'conferente'`.
6. **SLA / timestamps de transição de status** (`aprovado_em`, `conferencia_iniciada_em`, etc.)
   ficam **fora de escopo** desta fatia — entram só na fase de métricas/dashboard, para não
   criar colunas que ficam sem uso por enquanto.

## 4. Migrations

### 4.1 `add_conferencia_fields_to_purchase_requests_table`
Adiciona em `purchase_requests`:

| Coluna | Tipo | Nullable | Default | Observação |
|---|---|---|---|---|
| `tipo_entrega` | string | não | `'estoque'` | valores de app: `estoque`, `entrega_direta` |
| `status_conferencia` | string | sim | `null` | valores de app: `aguardando_conferencia`, `conferido_ok`, `divergente`, `avancado_mesmo_assim`. `null` = ainda não aplicável (antes de aprovado) |
| `quantidade_recebida` | integer | sim | `null` | preenchido pelo conferente |
| `observacao_conferencia` | text | sim | `null` | obrigatório em app quando `divergente` (validação de camada superior, não de banco) |
| `conferente_id` | foreignId → `users.id` | sim | `null` | `nullOnDelete()` |

### 4.2 `create_conferencia_fotos_table`

```
id
purchase_request_id  FK -> purchase_requests, cascade on delete
caminho_arquivo       string
nome_original         string nullable
timestamps()          (created_at / updated_at padrão do Laravel)
```

### 4.3 `add_role_to_users_table`
Adiciona `role` (string, nullable, default `null`) em `users`.

## 5. Models

### `PurchaseRequest`
- `$fillable` ganha: `tipo_entrega`, `status_conferencia`, `quantidade_recebida`,
  `observacao_conferencia`, `conferente_id`
- Nova relação `conferente(): BelongsTo` → `User`
- Nova relação `fotosConferencia(): HasMany` → `ConferenciaFoto`

### `ConferenciaFoto` (novo model)
- `$fillable`: `purchase_request_id`, `caminho_arquivo`, `nome_original`
- Relação `purchaseRequest(): BelongsTo` → `PurchaseRequest`

### `User`
- `$fillable` ganha `role`
- Novo método `isConferente(): bool` → `true` se `role === 'conferente'` **ou** `isAdmin()`

## 6. Controle de acesso

Novo `App\Http\Middleware\ConferenteMiddleware`, no mesmo padrão do `AdminMiddleware`
existente: libera se `auth()->user()->isConferente()`, senão bloqueia (redirect com erro,
igual ao comportamento atual do `AdminMiddleware`).

Nesta fatia o middleware é criado e testado isoladamente, mas **não é aplicado a nenhuma
rota ainda** (não há tela de conferente para proteger). Fica pronto pra ser usado no
sub-projeto da Tela do Conferente.

## 7. Fora de escopo (fica para próximas fatias)

- Campo `tipo_entrega` no formulário de criação de requisição (tela do vendedor)
- Tela do Conferente, Tela de Pendências
- Ajustes na Tela de Entrada (remover foto) e na Tela do Vendedor (colunas Entrada/Foto)
- Upload real de arquivo de foto (storage/S3) — vem junto da Tela do Conferente
- Timestamps de SLA e dashboard de métricas

## 8. Plano de teste (local apenas)

1. Rodar as 3 migrations no SQLite local (`php artisan migrate`).
2. Via `tinker`:
   - Criar usuário fake com `role = 'conferente'`.
   - Setar `tipo_entrega`, `status_conferencia`, `quantidade_recebida`,
     `observacao_conferencia`, `conferente_id` numa requisição `aprovado` já existente.
   - Confirmar `$req->fotosConferencia` e `$req->conferente` resolvem corretamente após
     criar uma `ConferenciaFoto` de teste.
   - Confirmar `User::isConferente()` retorna `true` pro usuário `role=conferente` e pro
     admin, e `false` pro vendedor comum.
3. Testar o `ConferenteMiddleware` isoladamente (request fake) confirmando bloqueio/liberação
   conforme o usuário autenticado.
4. Confirmar que nenhuma tela existente (admin, vendedor) quebrou — reload manual das telas
   principais localmente.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
