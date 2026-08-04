# Módulo de Conferência — Sub-projeto 6: Status de conferência na Tela do Vendedor

> Referência geral do módulo completo: `PLANO-CONFERENCIA.md` (fornecido pelo Guilherme).
> Referência dos sub-projetos anteriores: Tela do Conferente (sub-projetos 3, 4 e 5).

## 1. Objetivo desta fatia

O vendedor hoje não tem nenhuma visibilidade sobre o que acontece com o produto
depois que a requisição é aprovada — não sabe se já chegou no galpão, se já foi
conferido, nem o resultado da conferência. Ele só descobre perguntando pessoalmente.

Esta fatia mostra esse status diretamente na tela "Minhas Requisições"
(`resources/views/requests/index.blade.php`), sem precisar de notificação/mensagem —
só um campo visual ao lado do produto.

## 2. Contexto relevante do sistema atual

- `PurchaseRequestController::index()` (linha 14) já monta a listagem do
  vendedor sem `select()` restritivo — o campo `status_conferencia` do model
  `PurchaseRequest` já está disponível em cada `$req` sem nenhuma mudança de
  controller.
- `resources/views/requests/index.blade.php` já tem um padrão de etiqueta
  pequena embaixo do nome do produto: `Cód: {{ $req->product_code }}` (linha
  190) e `Ver link` (linha 193) na tabela desktop; equivalente no card mobile
  (linha 263).
- Valores possíveis de `status_conferencia` (sub-projeto 1): `null`,
  `conferido_ok`, `divergente`, `avancado_mesmo_assim`.
- O campo "deu entrada" (etapa de estoque pós-conferência) não existe no
  sistema ainda — fica fora do escopo desta fatia, é uma fatia futura separada
  (`PLANO-CONFERENCIA.md` já prevê "ajustes na Tela de Entrada" como etapa
  posterior).
- Hoje o sistema não distingue "ainda não chegou no galpão" de "chegou mas
  ainda não foi conferido" — só existe o dado de já ter sido conferido ou não.
  Por decisão de brainstorming, esta fatia não cria um campo novo para
  "chegada": enquanto não há `status_conferencia`, mostra genericamente
  "Aguardando conferência".

## 3. Decisões tomadas no brainstorming

1. **Escopo**: só chegada/conferência nesta fatia. "Deu entrada" fica de fora,
   fatia futura separada.
2. **Estados**: apenas dois estados possíveis — "Aguardando conferência" ou
   "Conferido" (com o resultado). Não cria campo novo de "chegada".
3. **Posição**: etiqueta pequena embaixo do nome do produto, mesmo padrão
   visual de "Cód: ..." / "Ver link" já existente — tanto na tabela desktop
   quanto no card mobile.
4. **Regra de exibição** (definida após uma rodada extra de ajuste):
   - Se `status_conferencia` estiver preenchido (`conferido_ok`, `divergente`
     ou `avancado_mesmo_assim`) — mostra "Conferido" com o resultado,
     **independente do `status` da requisição** ser `aprovado` ou `rejeitado`.
   - Senão, se `status === 'aprovado'` — mostra "Aguardando conferência".
   - Em qualquer outro caso (`pendente`, ou `rejeitado` sem conferência) —
     não mostra nada.
5. **Somente leitura**: a etiqueta não é link nem tem ação — só informa.

## 4. Mudanças

### 4.1 `resources/views/requests/index.blade.php` — tabela desktop

Dentro da célula de Produto (depois do bloco de `product_code` e antes/depois
do bloco de `product_url`, por volta da linha 188-194), adicionar:

```blade
@if($req->status_conferencia === 'conferido_ok')
    <span style="display:inline-block; margin-top:4px; background:#dcfce7; color:#16a34a; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido ✓ OK</span>
@elseif($req->status_conferencia === 'divergente')
    <span style="display:inline-block; margin-top:4px; background:#fee2e2; color:#dc2626; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Divergente</span>
@elseif($req->status_conferencia === 'avancado_mesmo_assim')
    <span style="display:inline-block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Conferido — Avançado Mesmo Assim</span>
@elseif($req->status === 'aprovado')
    <span style="display:inline-block; margin-top:4px; background:#f3f4f6; color:#6b7280; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">Aguardando conferência</span>
@endif
```

### 4.2 `resources/views/requests/index.blade.php` — card mobile

Mesmo bloco condicional (idêntico ao 4.1), inserido no cabeçalho do card
mobile logo abaixo do nome do produto / `Cód:` (por volta da linha 261-264).

### 4.3 Sem mudança de controller

`PurchaseRequestController::index()` não precisa de nenhuma alteração —
`status_conferencia` já vem carregado no model.

## 5. Fora de escopo

- Campo/etapa de "deu entrada" (estoque pós-conferência) — fatia futura.
- Campo de "chegada no galpão" distinto de conferência — decidido não criar.
- Notificação/mensagem ao vendedor — decidido não fazer, só o campo visual.
- Qualquer mudança na Tela do Conferente ou no `ConferenciaController`.

## 6. Plano de teste (local apenas)

1. Feature tests em `PurchaseRequestControllerTest` (ou arquivo equivalente
   já existente para a listagem do vendedor):
   - Requisição `aprovado` com `status_conferencia = null` → mostra
     "Aguardando conferência".
   - Requisição `aprovado` com `status_conferencia = 'conferido_ok'` →
     mostra "Conferido ✓ OK" e **não** mostra "Aguardando conferência".
   - Requisição `aprovado` com `status_conferencia = 'divergente'` → mostra
     "Conferido — Divergente".
   - Requisição `aprovado` com `status_conferencia = 'avancado_mesmo_assim'`
     → mostra "Conferido — Avançado Mesmo Assim".
   - Requisição `pendente` (sem `status_conferencia`) → não mostra nenhuma
     das etiquetas de conferência.
   - Requisição `rejeitado` sem `status_conferencia` → não mostra nenhuma
     das etiquetas de conferência.
   - Requisição `rejeitado` **com** `status_conferencia` preenchido (caso
     extremo, mas coberto pela regra) → mostra a etiqueta de resultado
     mesmo assim.
2. Confirmar visualmente em local que a etiqueta aparece certinha na tabela
   e no card mobile, sem quebrar o layout existente.

Nenhum teste em produção ou em ambiente de staging nesta fatia.
