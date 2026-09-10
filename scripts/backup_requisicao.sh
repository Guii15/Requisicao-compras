#!/bin/bash
# =============================================================
# BACKUP DO REQUISIÇÃO DE COMPRAS
#
# Copia o banco SQLite e a pasta de arquivos enviados, verifica se a
# cópia presta, e apaga o que já passou do prazo de retenção.
#
# Roda duas vezes por dia via cron (ver instruções no fim do arquivo).
#
# Por que existe, se a Hostinger já faz snapshot:
#   - o snapshot é SEMANAL: até 7 dias de trabalho perdido
#   - restaurar leva 33 min e devolve o servidor INTEIRO ao estado antigo,
#     incluindo os outros sites que rodam na mesma máquina
#   - não dá para voltar só o banco, nem escolher "como estava ontem"
# Este script resolve os três: cópia pequena, restauração em segundos,
# e histórico para escolher o ponto de volta.
# =============================================================

set -uo pipefail

# ---------- AJUSTE ESTES CAMINHOS ----------
APP_DIR="/home/requisicao/htdocs/requisicao.binariotecnologia.com"
DESTINO="/home/requisicao/backups"
# -------------------------------------------

BANCO="$APP_DIR/database/database.sqlite"
UPLOADS="$APP_DIR/storage/app"
LOG="$DESTINO/backup.log"

# Retenção escalonada: mantém granularidade recente sem acumular para sempre.
MANTER_RECENTES=7      # todas as cópias dos últimos N dias
MANTER_DIARIOS=30      # uma por dia, últimos N dias
MANTER_MENSAIS=6       # uma por mês, últimos N meses

registrar() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $*" | tee -a "$LOG"
}

falhar() {
    registrar "ERRO: $*"
    # O arquivo de alerta fica visível para quem entrar no servidor.
    echo "$(date '+%Y-%m-%d %H:%M:%S') $*" > "$DESTINO/ULTIMA_FALHA.txt"
    exit 1
}

mkdir -p "$DESTINO/recentes" "$DESTINO/diarios" "$DESTINO/mensais" || {
    echo "Não foi possível criar $DESTINO"; exit 1;
}

CARIMBO=$(date '+%Y-%m-%d_%H%M')
PASTA_TMP="$DESTINO/.tmp_$CARIMBO"
mkdir -p "$PASTA_TMP"

registrar "--- Início do backup $CARIMBO ---"

# ---------------------------------------------------------------
# 1. Banco de dados
# ---------------------------------------------------------------
# Não é 'cp'. Copiar um SQLite com o sistema no ar pode pegar o arquivo no
# meio de uma escrita e gerar um banco corrompido — que só se descobre no
# dia de restaurar. O comando .backup do sqlite3 faz a cópia de forma
# consistente, respeitando as transações em andamento.
if [ ! -f "$BANCO" ]; then
    falhar "Banco não encontrado em $BANCO"
fi

if ! command -v sqlite3 >/dev/null 2>&1; then
    falhar "sqlite3 não instalado. Rode: apt install sqlite3"
fi

if ! sqlite3 "$BANCO" ".backup '$PASTA_TMP/database.sqlite'"; then
    falhar "Falha ao copiar o banco"
fi

# ---------------------------------------------------------------
# 2. Verificação — a parte que faz este backup diferente do atual
# ---------------------------------------------------------------
# Um backup que ninguém testou é uma hipótese, não uma garantia. Aqui a
# cópia é aberta e checada logo depois de criada: se saiu corrompida, o
# alerta aparece HOJE, e não no dia em que você precisar dela.
INTEGRIDADE=$(sqlite3 "$PASTA_TMP/database.sqlite" "PRAGMA integrity_check;" 2>&1)
if [ "$INTEGRIDADE" != "ok" ]; then
    falhar "Cópia do banco corrompida: $INTEGRIDADE"
fi

TABELAS=$(sqlite3 "$PASTA_TMP/database.sqlite" \
    "SELECT COUNT(*) FROM sqlite_master WHERE type='table';" 2>/dev/null)
if [ -z "$TABELAS" ] || [ "$TABELAS" -lt 5 ]; then
    falhar "Cópia com apenas $TABELAS tabela(s) — algo está errado"
fi

# Conta as requisições. Serve de sinal de vida: se um dia esse número cair
# muito de uma execução para outra, alguém apagou dados.
REQUISICOES=$(sqlite3 "$PASTA_TMP/database.sqlite" \
    "SELECT COUNT(*) FROM purchase_requests;" 2>/dev/null || echo "?")

# ---------------------------------------------------------------
# 3. Arquivos enviados
# ---------------------------------------------------------------
# O banco guarda o CAMINHO da foto de conferência, não a foto. Sem esta
# parte, restaurar devolveria registros apontando para o vazio.
if [ -d "$UPLOADS" ]; then
    if ! tar -czf "$PASTA_TMP/storage.tar.gz" -C "$APP_DIR/storage" app 2>/dev/null; then
        registrar "AVISO: falha ao compactar storage (banco foi salvo mesmo assim)"
    fi
else
    registrar "AVISO: pasta $UPLOADS não existe"
fi

# ---------------------------------------------------------------
# 4. Fecha o pacote
# ---------------------------------------------------------------
ARQUIVO="$DESTINO/recentes/backup_$CARIMBO.tar.gz"
if ! tar -czf "$ARQUIVO" -C "$PASTA_TMP" . ; then
    falhar "Falha ao empacotar o backup"
fi
rm -rf "$PASTA_TMP"

TAMANHO=$(du -h "$ARQUIVO" | cut -f1)
registrar "OK: $ARQUIVO ($TAMANHO, $TABELAS tabelas, $REQUISICOES requisicoes)"

# Deu certo: some com o alerta da falha anterior, se houver.
rm -f "$DESTINO/ULTIMA_FALHA.txt"

# ---------------------------------------------------------------
# 5. Promove cópias para diário e mensal
# ---------------------------------------------------------------
# A primeira cópia de cada dia vira o "diário" daquele dia; a primeira de
# cada mês vira o "mensal". Assim dá para voltar tanto para "ontem de manhã"
# quanto para "como estava em julho", sem guardar tudo para sempre.
HOJE=$(date '+%Y-%m-%d')
MES=$(date '+%Y-%m')

[ -f "$DESTINO/diarios/backup_$HOJE.tar.gz" ] || cp "$ARQUIVO" "$DESTINO/diarios/backup_$HOJE.tar.gz"
[ -f "$DESTINO/mensais/backup_$MES.tar.gz" ]  || cp "$ARQUIVO" "$DESTINO/mensais/backup_$MES.tar.gz"

# ---------------------------------------------------------------
# 6. Limpeza
# ---------------------------------------------------------------
find "$DESTINO/recentes" -name 'backup_*.tar.gz' -mtime "+$MANTER_RECENTES" -delete
find "$DESTINO/diarios"  -name 'backup_*.tar.gz' -mtime "+$MANTER_DIARIOS"  -delete
find "$DESTINO/mensais"  -name 'backup_*.tar.gz' -mtime "+$((MANTER_MENSAIS * 31))" -delete

TOTAL=$(du -sh "$DESTINO" 2>/dev/null | cut -f1)
registrar "--- Fim. Espaço usado por todos os backups: $TOTAL ---"

# =============================================================
# COMO INSTALAR
#
#   1. Copie este arquivo para o servidor, por exemplo em
#      /home/requisicao/backup_requisicao.sh
#
#   2. Ajuste APP_DIR e DESTINO no topo do arquivo
#
#   3. Dê permissão de execução:
#        chmod +x /home/requisicao/backup_requisicao.sh
#
#   4. Rode uma vez na mão para conferir:
#        /home/requisicao/backup_requisicao.sh
#
#   5. Agende com o cron (crontab -e) — 8h e 18h todo dia:
#        0 8  * * * /home/requisicao/backup_requisicao.sh
#        0 18 * * * /home/requisicao/backup_requisicao.sh
#
# COMO RESTAURAR
#
#   cd /home/requisicao/backups/recentes
#   ls -lh                                   # escolha a cópia
#   mkdir -p /tmp/restaura && tar -xzf backup_ANO-MES-DIA_HHMM.tar.gz -C /tmp/restaura
#
#   # confira ANTES de sobrescrever:
#   sqlite3 /tmp/restaura/database.sqlite "SELECT COUNT(*) FROM purchase_requests;"
#
#   # guarde o banco atual antes de trocar, por precaução:
#   cp .../database/database.sqlite .../database/database.sqlite.antes-de-restaurar
#
#   cp /tmp/restaura/database.sqlite .../database/database.sqlite
#   tar -xzf /tmp/restaura/storage.tar.gz -C .../storage
#   chown -R requisicao:requisicao .../database .../storage
# =============================================================
