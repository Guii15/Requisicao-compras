import unittest

from agrupar_itens import extrair_capacidade, montar_ranking, normalizar, remover_plural_simples


class TestNormalizar(unittest.TestCase):
    def test_minusculas_e_espacos(self):
        self.assertEqual(normalizar("  SSD  "), "ssd")

    def test_remove_acentos(self):
        self.assertEqual(normalizar("Filtro de Óleo"), "filtro de oleo")

    def test_remove_plural_simples(self):
        self.assertEqual(normalizar("Ssds"), "ssd")
        self.assertEqual(normalizar("Correia Dentadas"), "correia dentada")

    def test_nao_quebra_sigla_curta(self):
        # "ssd" tem 3 letras -> remover_plural_simples nao mexe (regra exige len >= 4)
        self.assertEqual(remover_plural_simples("ssd"), "ssd")

    def test_nao_remove_s_de_palavra_terminada_em_ss(self):
        self.assertEqual(remover_plural_simples("compass"), "compass")


class TestExtrairCapacidade(unittest.TestCase):
    def test_extrai_gb(self):
        capacidade, base = extrair_capacidade("ssd 240gb")
        self.assertEqual(capacidade, "240gb")
        self.assertEqual(base, "ssd")

    def test_extrai_tb_com_espaco(self):
        capacidade, base = extrair_capacidade("ssd 1 tb")
        self.assertEqual(capacidade, "1tb")
        self.assertEqual(base, "ssd")

    def test_sem_capacidade_retorna_none(self):
        capacidade, base = extrair_capacidade("filtro de oleo")
        self.assertIsNone(capacidade)
        self.assertEqual(base, "filtro de oleo")


class TestMontarRanking(unittest.TestCase):
    def test_agrupa_variacoes_do_mesmo_item(self):
        textos = ["SSD", "ssd", "Ssds", " SSD "]
        ranking = montar_ranking(textos, limiar=0.85)
        self.assertEqual(len(ranking), 1)
        self.assertEqual(ranking[0]["total_pedidos"], 4)
        self.assertIsNone(ranking[0]["capacidade"])

    def test_nao_mistura_capacidades_diferentes(self):
        textos = ["SSD 240GB", "Ssd 240 Gb", "SSD 1TB", "ssd 1 tb"]
        ranking = montar_ranking(textos, limiar=0.85)
        capacidades = sorted(g["capacidade"] for g in ranking)
        self.assertEqual(capacidades, ["1tb", "240gb"])
        self.assertEqual(len(ranking), 2)

    def test_ssd_puro_nao_mistura_com_ssd_com_capacidade(self):
        textos = ["SSD", "SSD 240GB"]
        ranking = montar_ranking(textos, limiar=0.85)
        self.assertEqual(len(ranking), 2)

    def test_bases_diferentes_com_mesma_capacidade_ficam_separadas(self):
        textos = ["HD 1TB", "SSD 1TB"]
        ranking = montar_ranking(textos, limiar=0.85)
        self.assertEqual(len(ranking), 2)

    def test_ranking_ordenado_por_total_desc(self):
        textos = ["A"] * 2 + ["B"] * 5
        ranking = montar_ranking(textos, limiar=0.85)
        self.assertEqual([g["total_pedidos"] for g in ranking], [5, 2])

    def test_variacoes_contem_texto_original_e_quantidade(self):
        textos = ["Correia Dentada", "Correia Dentada", "correia dentadas"]
        ranking = montar_ranking(textos, limiar=0.85)
        self.assertEqual(len(ranking), 1)
        variacoes = {v["texto"]: v["qtd"] for v in ranking[0]["variacoes"]}
        self.assertEqual(variacoes["Correia Dentada"], 2)
        self.assertEqual(variacoes["correia dentadas"], 1)

    def test_ignora_textos_vazios(self):
        ranking = montar_ranking(["", "  ", None, "Pneu"], limiar=0.85)
        self.assertEqual(len(ranking), 1)
        self.assertEqual(ranking[0]["nome_canonico"], "Pneu")


if __name__ == "__main__":
    unittest.main()
