# Módulo de Conferência — Sub-projeto 5: Layout mobile (cards) na Tela do Conferente

> Referência do sub-projeto anterior: `docs/superpowers/specs/2026-08-03-ajustes-tela-conferente-design.md`
> (abas Aguardando/Conferidos + câmera).

## 1. Objetivo desta fatia

A Tela do Conferente hoje só tem layout de tabela, que fica cortada/com rolagem
horizontal em celular — testado ao vivo pelo Guilherme no celular dele. Como o
conferente provavelmente usa o celular no depósito, isso precisa virar cards
empilhados no mobile, igual o Admin já faz.

## 2. Contexto relevante do sistema atual

- `resources/views/admin/index.blade.php` já resolve exatamente esse problema:
  - CSS (linhas ~6-9): `.adm-mobile-cards { display: none; }` e, dentro de
    `@media (max-width: 768px)`, `.adm-desktop-table { display: none; }` +
    `.adm-mobile-cards { display: block; }`.
  - Tabela desktop envolvida em `<div class="adm-desktop-table">` (linha 319).
  - Cards mobile em `<div class="adm-mobile-cards">` (linha 454), um `@forelse`
    **duplicado** (não reaproveita o mesmo loop da tabela), um card por
    requisição com header (produto + badge de status), grade de dados, e
    footer com badge de urgência + botões.
  - **O modal de ação também é duplicado** (`modal-{id}` pra desktop,
    `modal-m-{id}` pra mobile) — necessário porque o modal vive dentro da
    `<div class="adm-desktop-table">`, que fica com `display:none` inteira no
    mobile (esconde tudo dentro, inclusive o modal `position:fixed`).
- `resources/views/conferencia/index.blade.php` (sub-projetos 3 e 4) hoje só
  tem a tabela, envolvida por `<div style="overflow-x:auto;">` sem nenhuma
  classe/breakpoint — daí o corte no celular.

## 3. Decisões tomadas no brainstorming

1. Replicar o mesmo padrão visual/técnico do Admin: cards empilhados no
   mobile, mesmo breakpoint (`max-width: 768px`).
2. Card mostra: nome do produto + badge "Tipo de Entrega" no topo; Vendedor,
   Fornecedor, Qtd Solicitada, Data numa grade; e no rodapé — badge de
   Resultado (aba Conferidos) ou botão "Conferir" (aba Aguardando), mesma
   lógica condicional por aba que a tabela já tem.
3. Modal de conferência duplicado pro mobile (`modal-conferir-m-{id}`), mesmo
   motivo técnico do Admin — conteúdo idêntico ao modal desktop (mesmos
   campos, câmera, lógica de "Avançar Mesmo Assim").

## 4. Mudanças

### 4.1 CSS (topo do arquivo, dentro de `@section('content')`, antes do primeiro `<div>`)
```html
<style>
.conf-mobile-cards { display: none; }
@media (max-width: 768px) {
    .conf-desktop-table { display: none; }
    .conf-mobile-cards  { display: block; }
}
</style>
```

### 4.2 Envolver a tabela existente
A `<div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">`
que já envolve a tabela ganha a classe `conf-desktop-table` (mantendo o
`style` como está).

### 4.3 Novo bloco de cards mobile
Adicionado logo depois do fechamento da `conf-desktop-table` (depois do
`@endif` da paginação, antes do `</div>` final da página), com:
- `@forelse($requests as $req)` duplicado (mesma coleção `$requests`, mesmo
  `@empty` com a mensagem já condicional por `$aba`).
- Card com header (produto + badge Tipo de Entrega), grade (Vendedor,
  Fornecedor, Qtd, Data), footer condicional por `$aba` (botão Conferir /
  badge de Resultado).
- Paginação repetida embaixo dos cards (mesmo `$requests->links()`).

### 4.4 Modal mobile duplicado
Dentro do `@if($aba === 'aguardando')` do card mobile, um modal
`modal-conferir-m-{{ $req->id }}` com o mesmo conteúdo do modal desktop
(campos, form, JS de toggle) — nomes de `id` únicos (`form-conferir-m-`,
`campo-observacao-m-`, `campo-acao-m-`, `btn-avancar-m-`) e função JS
`atualizaResultadoMobile{{ $req->id }}` (nome diferente pra não colidir com
a função do modal desktop).

## 5. Fora de escopo

- Qualquer mudança de comportamento/lógica do `conferir()` — só layout.
- Tela de Pendências, Entrada, Vendedor, SLA, divisão de quantidade
  estoque/entrega direta (ainda pendente, fatia maior separada).

## 6. Plano de teste (local apenas)

1. Feature tests confirmando que os elementos mobile (classe `conf-mobile-cards`,
   os dois modais com sufixo `-m-`) aparecem no HTML renderizado tanto na aba
   Aguardando quanto na Conferidos.
2. Confirmar visualmente que a página renderiza sem erro (mesmo teste de
   render real via controller, não grep) com pelo menos um item em cada aba.
3. Testar de verdade no celular do Guilherme (fora do escopo automatizado,
   mas é o critério de aceite real desta fatia).

Nenhum teste em produção ou em ambiente de staging nesta fatia.
