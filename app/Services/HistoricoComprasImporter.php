<?php

namespace App\Services;

use Carbon\Carbon;

class HistoricoComprasImporter
{
    private const CAMPOS_EXTRAS = [
        'pedido',
        'preco_unitario',
        'filial',
        'modalidade_compra',
        'data_coleta',
        'data_entrada',
        'data_pagamento',
        'vencimento_1',
        'vencimento_2',
        'vencimento_3',
        'conferencia',
        'forma_pagamento',
        'data_reposicao',
        'vendedor',
        'data_retirada',
        'entrada_showroom',
        'quantidade_varejo_cotada',
        'preco_unitario_varejo_cotado',
        'subtotal_varejo_cotado',
        'quantidade_caixa_cotada',
        'preco_unitario_caixa_cotado',
        'valor_total_caixa_cotado',
        'linha_excel_original',
        'flags_qualidade',
    ];

    public function mapear(array $linha): array
    {
        $abaOrigem = trim((string) ($linha['aba_origem'] ?? ''));
        $ehCotacaoPati = $abaOrigem === 'Compra Pati';

        $quantidade = $this->paraInteiro($linha['quantidade'] ?? null);
        $precoUnitario = $this->paraFloat($linha['preco_unitario'] ?? null);
        $precoTotal = $this->paraFloat($linha['preco_total'] ?? null);

        $valor = $precoTotal;
        if ($valor === null && $quantidade !== null && $precoUnitario !== null) {
            $valor = round($quantidade * $precoUnitario, 2);
        }
        if ($ehCotacaoPati) {
            // Campos de cotação da Pati vêm em formatos mistos (string "R$ x,xx" e float);
            // não confiáveis para virar valor numérico da compra. Ficam só em dados_importacao.
            $valor = null;
        }

        $descricao = trim((string) ($linha['descricao'] ?? ''));
        $codigo = $this->valorOuNull($linha['codigo'] ?? null);
        $productName = $descricao !== '' ? $descricao : ($codigo ?? 'Item sem descrição (importado)');

        $requesterName = $this->valorOuNull($linha['requisitante'] ?? null) ?? $this->valorOuNull($linha['vendedor'] ?? null);

        $pedido = $this->valorOuNull($linha['pedido'] ?? null);
        $origemId = $linha['origem_id'];
        $grupoId = $pedido ? $abaOrigem . '_PEDIDO_' . $pedido : $origemId;

        $dadosExtras = [];
        foreach (self::CAMPOS_EXTRAS as $campo) {
            $valorCampo = $this->valorOuNull($linha[$campo] ?? null);
            if ($valorCampo !== null) {
                $dadosExtras[$campo] = $valorCampo;
            }
        }

        return [
            'origem_id' => $origemId,
            'tipo_registro' => $ehCotacaoPati ? 'cotacao_historica' : 'compra_historica',
            'aba_origem' => $abaOrigem,
            'mes_origem' => $this->valorOuNull($linha['mes_origem'] ?? null),
            'data_compra' => $this->paraData($linha['data'] ?? null),
            'product_name' => $productName,
            'product_code' => $codigo,
            'supplier' => $this->valorOuNull($linha['fornecedor_ou_marca'] ?? null),
            'quantity' => $quantidade ?? 0,
            'valor' => $valor,
            'requester_name' => $requesterName,
            'status' => $ehCotacaoPati ? 'pendente' : 'aprovado',
            'reason' => 'Importação histórica',
            'urgency' => 'baixa',
            'justification' => 'Registro histórico importado da planilha de compras.',
            'admin_note' => $this->valorOuNull($linha['observacao'] ?? null),
            'dados_importacao' => $dadosExtras ?: null,
            'grupo_id' => $grupoId,
        ];
    }

    private function paraInteiro(mixed $valor): ?int
    {
        $valor = $this->valorOuNull($valor);
        if ($valor === null) {
            return null;
        }
        return (int) round((float) str_replace(',', '.', $valor));
    }

    private function paraFloat(mixed $valor): ?float
    {
        $valor = $this->valorOuNull($valor);
        if ($valor === null || !is_numeric(str_replace(',', '.', $valor))) {
            return null;
        }
        return (float) str_replace(',', '.', $valor);
    }

    private function paraData(mixed $valor): ?string
    {
        $valor = $this->valorOuNull($valor);
        if ($valor === null) {
            return null;
        }
        try {
            return Carbon::parse($valor)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function valorOuNull(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $valor = trim((string) $valor);
        if ($valor === '' || preg_match('/^-+$/', $valor) === 1) {
            // A planilha usa "-" como marcador de campo vazio em varias colunas.
            return null;
        }
        return $valor;
    }
}
