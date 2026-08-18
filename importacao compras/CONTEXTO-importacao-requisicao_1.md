# Contexto: Importação do histórico de compras para o sistema Requisição

> Handoff de uma conversa no Claude.ai (chat) para continuar no VS Code / Claude Code.
> Data desta análise: 18/08/2026.

## 1. Objetivo

Guilherme mantinha o controle de compras da Binário Tecnologia em uma planilha Excel
(`Requisição_de_Compras__6_.xlsx`) porque "às vezes a gente se perdia" usando só a
planilha. Agora ele quer alimentar o **sistema Requisição** (`requisicao.binariotecnologia.com`,
Laravel) com o histórico completo de compras já feitas — todas as abas da planilha,
sem exceção — para que esse histórico passe a viver no sistema em vez de numa planilha solta.

Ele foi explícito sobre dois pontos:
- **É um dado de produção real e importante** — "super perigoso acontecer alguma coisa
  com ele". Qualquer script de importação precisa ser testado antes de rodar de verdade
  (dry-run, backup do banco, conferência de totais).
- **Quer rastreabilidade por linha**: cada registro importado deve guardar de qual aba/mês
  da planilha ele veio (ex.: "Esmerilhadeira Angular — Agosto"), não só o dado da compra em si.

## 2. Decisões já tomadas com o Guilherme

| Pergunta | Resposta |
|---|---|
| Quais abas entram? | **Todas as 9 abas**, sem exceção |
| Precisa de rastreabilidade de origem por linha? | **Sim** — aba, mês e (idealmente) referência tipo "Produto — Mês" |
| Schema da tabela de requisições | Ele **tem** o schema, mas **não colou no chat** — vai continuar isso no VS Code, onde tem acesso direto às migrations/repositório |
| Formato do deliverable final | **Script SQL / seeder** para ele rodar manualmente (não é para eu — Claude no chat — rodar nada em produção) |

**O que falta decidir (ainda não perguntei/ele não respondeu):**
- Nome real das tabelas/colunas de destino (precisa da migration).
- Se existe (ou se ele quer criar) uma coluna específica tipo `origem_planilha`,
  `origem_referencia` ou `observacao_importacao` para guardar a rastreabilidade.
- O que fazer com os **434 registros (13,1%) com alerta de qualidade** (seção 5) —
  importar mesmo assim com o campo em branco, tentar inferir, ou excluir da primeira leva.
- Se a aba **"Compra Pati"** (cotação, não compra fechada — ver seção 4) deve virar
  requisição de verdade no sistema ou ficar de fora / marcada como "cotação".

## 3. Arquivo original

- **Nome:** `Requisição_de_Compras__6_.xlsx`
- **9 abas**, cabeçalhos em linhas diferentes, nomes de coluna que mudam sutilmente mês a mês
  (ex.: "Marca" em Jan-Fev vira "Fornecedor" a partir de Março; "Qtd" vira "Quant"; "Conferência"
  e "Forma de Pag. (venda)" só aparecem a partir de Maio).
- **Nada nesse arquivo original foi alterado** — só li e processei uma cópia.

### Estrutura por aba

| Aba | Linha do cabeçalho | Linhas de dado (após limpar linhas em branco) | Estrutura |
|---|---|---|---|
| JAN. FEV. | 7 | 318 | Compra padrão |
| MARÇO ABRIL | 7 | 765 | Compra padrão |
| MAIO | 7 | 475 | Compra padrão + colunas extras (Conferência, Forma de Pag.) |
| JUNHO | 7 | 598 | Compra padrão + colunas extras |
| JULHO | 7 | 709 | Compra padrão + colunas extras |
| AGOSTO | 6 (uma linha acima das demais) | 114 | Compra padrão + colunas extras — mês corrente, ainda incompleto (hoje é 18/08) |
| SHOWROOM | 7 | 113 | **Estrutura diferente**: sem Requisitante/Modalidade; tem Vendedor e Data da Reposição |
| ShowRoom1 | 7 | 201 | Igual à SHOWROOM |
| Compra Pati | 1 | 23 | **Estrutura totalmente diferente** — ver seção 4 |

**Total consolidado: 3.316 linhas de dado reais** (depois de descartar 507 linhas que
pareciam preenchidas só por causa de uma checkbox do Excel — "Conferência" — que o
openpyxl lê como `False` mesmo em linha totalmente vazia; a maior parte dessas linhas
"fantasma" estava na aba AGOSTO).

## 4. A aba "Compra Pati" é um caso à parte

Diferente das outras 8 abas, "Compra Pati" **não é um registro de compra fechada**:
tem colunas de status como "Sem saldo no Fornecedor" e "Pedido feito 04/03", sem data de
compra real, e compara **preço no varejo** (`Preço Unitário` / `Montante`) com **preço na
caixa/lote** (`Quant. Caixa` / `Valor Unitário` / `Valor Total`) — é uma cotação/negociação
com fornecedor, não uma compra confirmada.

No CSV consolidado, os campos dessa aba foram mapeados para colunas **próprias**
(prefixo `_cotado`/`_cotada`) para não se misturar com os campos de compra real:
`quantidade_varejo_cotada`, `preco_unitario_varejo_cotado`, `subtotal_varejo_cotado`,
`quantidade_caixa_cotada`, `preco_unitario_caixa_cotado`, `valor_total_caixa_cotado`.

**Decisão pendente:** perguntar ao Guilherme se isso deve virar uma requisição normal
no sistema (com algum status "cotação"/"pendente") ou ficar fora da primeira importação.

## 5. Mapeamento de colunas (planilha → nome canônico no CSV)

| Nome(s) na planilha | Coluna no CSV consolidado |
|---|---|
| Data | `data` |
| Pedido / Pedido/NF | `pedido` |
| Qtd / Quant | `quantidade` |
| Preço Unitário | `preco_unitario` |
| Preço Total | `preco_total` |
| Descrição do Produto | `descricao` |
| Código / código | `codigo` |
| Requisitante | `requisitante` |
| Modalidade Compra | `modalidade_compra` |
| Observação | `observacao` |
| Marca (Jan-Fev) / Fornecedor (demais) | `fornecedor_ou_marca` *(confirmar com o Guilherme se é sempre o mesmo conceito)* |
| Filial Compra | `filial` |
| Data da Coleta | `data_coleta` |
| Data da Entrada | `data_entrada` |
| Data de PGTO | `data_pagamento` |
| Vencimentos (3 colunas repetidas) | `vencimento_1`, `vencimento_2`, `vencimento_3` |
| Conferência | `conferencia` |
| Forma de Pag. (venda) | `forma_pagamento` |
| Data da Reposição (Showroom) | `data_reposicao` |
| Vendedor (Showroom) | `vendedor` |
| Data Retirada Mercadoria (Showroom) | `data_retirada` |
| Entrada (Showroom) | `entrada_showroom` |

Colunas de rastreabilidade adicionadas por mim (não existem na planilha original):
`origem_id` (id estável tipo `AGOSTO_L121`), `aba_origem`, `mes_origem`,
`linha_excel_original`.

## 6. Qualidade dos dados — resumo

De 3.316 linhas, **434 (13,1%)** têm pelo menos um alerta:

| Alerta | Quantidade | O que significa |
|---|---|---|
| `data_ausente` | 250 | Sem data de compra preenchida |
| `quantidade_ausente` | 178 | Sem quantidade |
| `descricao_ausente` | 150 | Sem descrição do produto (mas tem código/preço) |
| `preco_ausente` | 106 | Sem preço unitário nem total |
| `linha_duplicada_suspeita` | 92 | Mesma aba + pedido + código/descrição + qtd + preço unitário aparecem 2x ou mais — **pode ser lote dividido de propósito, ou digitação duplicada**, precisa de olho humano |
| `total_nao_bate_qtd_x_unitario` | 4 | `quantidade × preço_unitário` diverge de `preço_total` em mais de 2% — provável erro de digitação em um dos três campos |

O arquivo `compras_com_alertas.csv` traz só essas 434 linhas, com a coluna
`flags_qualidade` dizendo exatamente qual(is) problema(s) cada uma tem, para revisão
rápida antes da importação.

## 7. Arquivos gerados nesta conversa

- **`compras_consolidado.csv`** — as 3.316 linhas, todas as abas unificadas, com colunas
  canônicas + rastreabilidade + coluna `flags_qualidade` (vazia quando não há alerta).
  Este é o arquivo para o script de importação ler.
- **`compras_com_alertas.csv`** — subconjunto (434 linhas) só com as linhas que têm
  algum alerta, para revisão manual antes de decidir o que fazer com elas.

## 8. Próximos passos (para continuar no VS Code / Claude Code)

1. **Pegar o schema real** das tabelas envolvidas (migrations do Laravel, ou
   `sqlite3 database.sqlite .schema` se for SQLite, como no resto do projeto Requisição).
2. **Mapear** as colunas canônicas do CSV para as colunas reais das tabelas — inclusive
   decidir onde a rastreabilidade (`aba_origem`, `mes_origem`, `linha_excel_original`) vai
   morar (coluna nova, ou dentro de `observacao`).
3. **Decidir o tratamento das 434 linhas com alerta** (seção 6) antes de gerar o script —
   importar com campo em branco, pular, ou revisar manualmente linha a linha.
4. **Gerar o script SQL/seeder** com:
   - Transação (tudo ou nada — `BEGIN` / `COMMIT` / `ROLLBACK` em caso de erro).
   - Um **dry-run** primeiro (rodar contra uma cópia do banco, nunca direto em produção).
   - Uma etapa de **conferência pós-import**: contar linhas importadas e comparar soma de
     `preco_total` por mês/aba com os números da seção 3, exatamente como ele pediu
     ("testar tudo e fazer tudo direito").
5. **Backup do banco de produção** imediatamente antes de rodar de verdade.
