"""
Protótipo de agrupamento de "itens mais solicitados".

Junta variações de texto do mesmo item (maiuscula/minuscula, plural,
espacamento) mantendo capacidades diferentes (GB/TB) do mesmo produto
base como itens SEPARADOS.

Fase atual: script isolado para validar o agrupamento contra uma
amostra antes de integrar na tela do Admin. Nao grava nada no banco
do Laravel — so le (product_name) e imprime/expora o resultado.
"""

import argparse
import json
import re
import sqlite3
import unicodedata
from collections import Counter, defaultdict
from pathlib import Path

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

CAPACIDADE_RE = re.compile(r"(\d+(?:[.,]\d+)?)\s*(gb|tb|mb)\b", re.IGNORECASE)


def remover_acentos(texto: str) -> str:
    nfkd = unicodedata.normalize("NFKD", texto)
    return "".join(c for c in nfkd if not unicodedata.combining(c))


def remover_plural_simples(palavra: str) -> str:
    """Regra basica: tira 's' final, protegendo palavras curtas/siglas."""
    if palavra.isalpha() and len(palavra) >= 4 and palavra.endswith("s") and not palavra.endswith("ss"):
        return palavra[:-1]
    return palavra


def normalizar(texto: str) -> str:
    texto = texto.lower()
    texto = remover_acentos(texto)
    texto = re.sub(r"\s+", " ", texto).strip()
    palavras = [remover_plural_simples(p) for p in texto.split(" ")]
    return " ".join(palavras)


def extrair_capacidade(texto_normalizado: str):
    """Retorna (capacidade_normalizada_ou_None, texto_sem_capacidade)."""
    m = CAPACIDADE_RE.search(texto_normalizado)
    if not m:
        return None, texto_normalizado

    valor, unidade = m.group(1), m.group(2).lower()
    capacidade = f"{valor}{unidade}"

    sem_capacidade = texto_normalizado[: m.start()] + texto_normalizado[m.end():]
    sem_capacidade = re.sub(r"\s+", " ", sem_capacidade).strip()
    return capacidade, sem_capacidade


class UniaoConjuntos:
    """Union-Find simples para montar componentes conexos (grupos)."""

    def __init__(self, itens):
        self.pai = {item: item for item in itens}

    def encontrar(self, item):
        while self.pai[item] != item:
            self.pai[item] = self.pai[self.pai[item]]
            item = self.pai[item]
        return item

    def unir(self, a, b):
        ra, rb = self.encontrar(a), self.encontrar(b)
        if ra != rb:
            self.pai[ra] = rb


def agrupar_bucket(textos_base_unicos, limiar):
    """Agrupa textos-base (mesma capacidade) por similaridade TF-IDF."""
    if len(textos_base_unicos) <= 1:
        return [set(textos_base_unicos)] if textos_base_unicos else []

    vetor = TfidfVectorizer(analyzer="char_wb", ngram_range=(2, 4))
    matriz = vetor.fit_transform(textos_base_unicos)
    similaridade = cosine_similarity(matriz)

    uf = UniaoConjuntos(textos_base_unicos)
    n = len(textos_base_unicos)
    for i in range(n):
        for j in range(i + 1, n):
            if similaridade[i][j] >= limiar:
                uf.unir(textos_base_unicos[i], textos_base_unicos[j])

    grupos = defaultdict(set)
    for texto in textos_base_unicos:
        grupos[uf.encontrar(texto)].add(texto)
    return list(grupos.values())


def montar_ranking(textos_originais, limiar=0.85):
    """
    textos_originais: lista de strings (product_name) tal como digitado.
    Retorna lista de grupos ordenada por total_pedidos desc, cada um com:
      nome_canonico, capacidade, total_pedidos, variacoes: [{texto, qtd}]
    """
    contagem_original = Counter(t.strip() for t in textos_originais if t and t.strip())

    # info por texto original: (capacidade, texto_base_normalizado)
    info_por_original = {}
    for original in contagem_original:
        normalizado = normalizar(original)
        capacidade, base = extrair_capacidade(normalizado)
        info_por_original[original] = (capacidade, base)

    # bucket por capacidade (None = "sem capacidade")
    buckets = defaultdict(set)  # capacidade -> {textos_base}
    base_para_originais = defaultdict(list)  # (capacidade, base) -> [originais]
    for original, (capacidade, base) in info_por_original.items():
        buckets[capacidade].add(base)
        base_para_originais[(capacidade, base)].append(original)

    ranking = []
    for capacidade, textos_base_unicos in buckets.items():
        for grupo_bases in agrupar_bucket(sorted(textos_base_unicos), limiar):
            originais_do_grupo = []
            for base in grupo_bases:
                originais_do_grupo.extend(base_para_originais[(capacidade, base)])

            variacoes = sorted(
                (
                    {"texto": original, "qtd": contagem_original[original]}
                    for original in originais_do_grupo
                ),
                key=lambda v: (-v["qtd"], v["texto"]),
            )
            total = sum(v["qtd"] for v in variacoes)
            nome_canonico = variacoes[0]["texto"]

            ranking.append(
                {
                    "nome_canonico": nome_canonico,
                    "capacidade": capacidade,
                    "total_pedidos": total,
                    "variacoes": variacoes,
                }
            )

    ranking.sort(key=lambda g: (-g["total_pedidos"], g["nome_canonico"].lower()))
    return ranking


def imprimir_ranking(ranking, titulo):
    print(f"\n=== {titulo} (limiar de similaridade aplicado) ===")
    if not ranking:
        print("(sem itens)")
        return
    for grupo in ranking:
        cap = f" [{grupo['capacidade']}]" if grupo["capacidade"] else ""
        print(f"\n- {grupo['nome_canonico']}{cap}  -> total: {grupo['total_pedidos']}")
        for v in grupo["variacoes"]:
            print(f"    {v['qtd']:>3}x  \"{v['texto']}\"")


AMOSTRA_SINTETICA = (
    ["SSD"] * 3 + ["ssd"] * 2 + ["Ssds"] * 2 + [" SSD "] * 1
    + ["SSD 240GB"] * 4 + ["ssd240gb"] * 1 + ["Ssd 240 Gb"] * 2
    + ["SSD 1TB"] * 3 + ["ssd 1 tb"] * 1
    + ["Filtro de Oleo"] * 3 + ["filtro de óleo"] * 2 + ["Filtros de Oleo"] * 1
    + ["Correia Dentada"] * 2 + ["correia dentadas"] * 1 + ["CORREIA DENTADA"] * 1
    + ["Pneu Aro 15"] * 2 + ["pneu aro 15"] * 1
    + ["HD 1TB"] * 2 + ["hd 500gb"] * 1
)


def carregar_do_sqlite(caminho_db, dias=None):
    con = sqlite3.connect(caminho_db)
    cur = con.cursor()
    if dias:
        cur.execute(
            "SELECT product_name FROM purchase_requests "
            "WHERE created_at >= datetime('now', ?)",
            (f"-{int(dias)} days",),
        )
    else:
        cur.execute("SELECT product_name FROM purchase_requests")
    valores = [row[0] for row in cur.fetchall() if row[0]]
    con.close()
    return valores


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--db", default=str(Path(__file__).resolve().parents[2] / "database" / "database.sqlite"))
    parser.add_argument("--dias", type=int, default=None, help="Filtra requisicoes dos ultimos N dias")
    parser.add_argument("--limiar", type=float, default=0.85)
    parser.add_argument("--pular-demo", action="store_true", help="Nao roda a amostra sintetica de validacao")
    parser.add_argument("--saida", default=str(Path(__file__).resolve().parent / "output" / "preview.json"))
    args = parser.parse_args()

    resultado_json = {}

    if not args.pular_demo:
        ranking_demo = montar_ranking(AMOSTRA_SINTETICA, limiar=args.limiar)
        imprimir_ranking(ranking_demo, "AMOSTRA SINTETICA (casos propositalmente ambiguos)")
        resultado_json["amostra_sintetica"] = ranking_demo

    if Path(args.db).exists():
        textos_reais = carregar_do_sqlite(args.db, args.dias)
        ranking_real = montar_ranking(textos_reais, limiar=args.limiar)
        imprimir_ranking(ranking_real, f"BANCO REAL ({len(textos_reais)} requisicoes lidas de {args.db})")
        resultado_json["banco_real"] = ranking_real
    else:
        print(f"\n(aviso: banco não encontrado em {args.db}, pulei essa parte)")

    Path(args.saida).parent.mkdir(parents=True, exist_ok=True)
    with open(args.saida, "w", encoding="utf-8") as f:
        json.dump(resultado_json, f, ensure_ascii=False, indent=2)
    print(f"\nResultado completo salvo em: {args.saida}")


if __name__ == "__main__":
    main()
