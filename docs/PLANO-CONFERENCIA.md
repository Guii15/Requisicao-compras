# Módulo de Conferência - Sistema de Requisições

> Documento de referência para desenvolvimento. Contexto: sistema Laravel de requisições de compra já existente (requisicao.catalogobinariotec.com.br), rodando em produção com fluxo Solicitante → Comprador (aprovação). Este documento especifica a extensão do fluxo com etapa de Conferência.

## 1. Objetivo

Adicionar uma etapa de **conferência física dos produtos** entre a aprovação do comprador e a entrada no estoque, com controle por foto, liberação parcial por item, tratamento diferenciado para entrega direta (dropship) e visibilidade total para o vendedor solicitante.

## 2. Fluxo completo

```
Solicitante cria requisição
        |
        v
Comprador aprova / rejeita
        |
        v
Conferente confere (foto obrigatória em CADA item, OK ou divergente)
        |
        +-- Item OK -------------------------------------> Entrada
        |
        +-- Item divergente + tipo_entrega = entrega_direta -> Avança mesmo assim (botão "Avançar mesmo assim")
        |
        +-- Item divergente + tipo_entrega = estoque -------> Pendência (aguarda decisão do comprador)
        |
        v
Entrada dá entrada (sem foto, usa a foto tirada pelo conferente) + data + vendedor destino + quantidade
        |
        v
Vendedor acompanha tudo na tela de requisições já existente (colunas novas: Entrada / Foto)
```

## 3. Conceitos-chave

- **Conferência é por item**, não por requisição inteira → permite liberação parcial (ex: 8 de 10 itens OK avançam, 2 ficam retidos).
- **Foto é sempre tirada pelo conferente**, nunca pela entrada. É obrigatória em todo item conferido (OK ou divergente), não só em caso de problema — serve como controle interno.
- **`tipo_entrega`** distingue dois cenários de negócio:
  - `estoque`: produto chega no CD, se divergente TRAVA o item em Pendência.
  - `entrega_direta` (dropship): fornecedor manda direto pro cliente. Divergência é registrada mas NÃO trava — conferente tem botão "Avançar mesmo assim" (fica registrado pra cobrança futura do fornecedor).
- **Tela do vendedor não muda de tela** — os campos novos entram como colunas na tabela que já existe hoje (a ideia de criar telas novas pro solicitante e pra colocar foto na entrada foi descartada — mantém simples e rápido pro vendedor bater o olho).

## 4. Telas

### 4.1 Tela do Conferente (NOVA)
- Lista requisições com status `Aprovado`.
- Por item:
  - Quantidade solicitada vs quantidade recebida (campo editável)
  - Upload de foto (obrigatório)
  - Status: OK / Divergente
  - Campo de observação (obrigatório se Divergente)
  - Indicador visual se `tipo_entrega = entrega_direta`
  - Botão "Avançar mesmo assim" (habilitado só se divergente + entrega_direta)
- Ação por item libera individualmente (não trava a requisição inteira esperando todos os itens).

### 4.2 Tela de Pendências (NOVA)
- Lista só itens `divergente` com `tipo_entrega = estoque`.
- Comprador decide: recontatar fornecedor / cancelar item / aceitar mesmo assim / outra ação.

### 4.3 Tela de Entrada (AJUSTAR existente)

**Regra de entrada na lista:** só aparece aqui item com `status_conferencia = conferido_ok` OU `avancado_mesmo_assim`. Item `aguardando_conferencia` ou `divergente` (estoque, retido em pendência) **não aparece** pro pessoal da entrada — eles só veem o que já passou pelo conferente.

- Lista requisições/itens liberados pela conferência, aguardando entrada.
- Por item, o pessoal da entrada visualiza (somente leitura, vem da conferência):
  - Produto, quantidade solicitada, quantidade recebida (preenchida pelo conferente)
  - Foto do produto (a mesma tirada pelo conferente — clicável/ampliável, mas não é possível trocar aqui)
  - Se veio como "avançado mesmo assim" (divergência dropship), mostrar um aviso/tag visível pra quem for dar entrada já saber do histórico
- Campos que o pessoal da entrada preenche para dar entrada de fato:
  - **Vendedor destino** (pra quem vai o produto — puxa da requisição original, mas pode ter campo pra confirmar/ajustar)
  - **Quantidade dada entrada** (pode divergir da quantidade recebida na conferência, se por algum motivo enviar menos pro vendedor)
  - Botão "Dar entrada" → muda status para `entrada_realizada`, preenche `entrada_concluida_em`
- Ao abrir o item pela primeira vez nessa tela, preencher `entrada_iniciada_em` (pra métrica de SLA de fila até alguém pegar pra dar entrada).
- **Sem campo de foto próprio** — reaproveita a foto já registrada na conferência, não duplica upload.

**Resumo do que preenche cada timestamp nessa tela:**
| Ação do usuário | Timestamp preenchido |
|---|---|
| Item aparece na lista (liberado pelo conferente) | `entrada_iniciada_em` (quando alguém abre pra tratar) |
| Clica em "Dar entrada" | `entrada_concluida_em` |

### 4.4 Tela do Vendedor (AJUSTAR existente — tabela de acompanhamento)
Colunas novas na tabela atual (Vendedor | Produto | Fornecedor | Qtd | Urgência | Status | Data | Ação):
- **Entrada**: badge de status — 🟡 Aguardando conferência / 🔵 Conferido / 🟢 Entrada realizada (considerar alerta visual se estourar SLA)
- **Foto**: ícone clicável (abre modal com a foto tirada pelo conferente); só aparece após a conferência

## 5. Modelo de dados (rascunho — ajustar aos nomes reais das tabelas)

### Campos novos em `requisicao_itens` (ou tabela equivalente)
```
tipo_entrega            ENUM('estoque', 'entrega_direta')   -- pode ser no header da requisição ou por item, a definir
status_conferencia      ENUM('aguardando_conferencia', 'conferido_ok', 'divergente', 'avancado_mesmo_assim')
quantidade_recebida     INT NULL
observacao_conferencia  TEXT NULL
conferente_id           FK -> users NULL

-- timestamps de SLA
aprovado_em                  TIMESTAMP NULL
conferencia_iniciada_em      TIMESTAMP NULL
conferencia_concluida_em     TIMESTAMP NULL
entrada_iniciada_em          TIMESTAMP NULL
entrada_concluida_em         TIMESTAMP NULL
```

### Nova tabela `conferencia_fotos`
```
id
requisicao_item_id   FK
caminho_arquivo
nome_original
criado_em
```

### Roles / Controle de acesso
- Nova role `conferente` (Gate/Policy separado de `comprador` e `entrada`)

## 6. Métricas (fase futura, dashboard de SLA)

Com os timestamps acima, calcular:
- Tempo médio de conferência = `conferencia_concluida_em - aprovado_em`
- Tempo médio de entrada = `entrada_concluida_em - conferencia_concluida_em`
- Tempo total do ciclo = `entrada_concluida_em - aprovado_em`
- Requisições "estouradas" (acima de X horas sem avançar de etapa)

## 7. Ordem sugerida de implementação

1. Migrations: campos novos em itens + tabela `conferencia_fotos` + role `conferente`
2. Model + Policy do conferente
3. Tela do Conferente (CRUD de conferência por item + upload de foto)
4. Lógica de roteamento pós-conferência (OK → entrada / divergente+estoque → pendência / divergente+dropship → avança)
5. Tela de Pendências
6. Ajuste da tela de Entrada (remover foto, receber só itens conferidos)
7. Ajuste da tela do Vendedor (colunas Entrada + Foto)
8. Timestamps de SLA em cada transição de status
9. (Futuro) Dashboard de métricas

## 8. Pendências de decisão (perguntar ao Guilherme se aparecer dúvida no código)

- `tipo_entrega` fica no header da requisição inteira ou por item individual? (requisição pode ter itens mistos?)
- Regra exata de quem pode reverter um "avançado mesmo assim" caso o fornecedor confirme divergência real depois
