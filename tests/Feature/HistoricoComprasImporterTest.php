<?php

namespace Tests\Feature;

use App\Services\HistoricoComprasImporter;
use Tests\TestCase;

class HistoricoComprasImporterTest extends TestCase
{
    private function linhaBase(array $sobrescreve = []): array
    {
        return array_merge([
            'origem_id' => 'JAN._FEV._L8',
            'aba_origem' => 'JAN.  FEV.',
            'mes_origem' => 'Jan-Fev',
            'linha_excel_original' => '8',
            'data' => '2026-01-26 00:00:00',
            'pedido' => '202348',
            'quantidade' => '100.0',
            'preco_unitario' => '18.4022',
            'preco_total' => '1840.22',
            'descricao' => 'Suporte P/ Gabinte',
            'codigo' => '',
            'requisitante' => 'Yhan',
            'modalidade_compra' => 'Casada',
            'observacao' => '',
            'fornecedor_ou_marca' => 'C3 Tech',
            'filial' => '1.0',
            'data_coleta' => '2026-02-04 00:00:00',
            'data_entrada' => '',
            'data_pagamento' => '',
            'vencimento_1' => '',
            'vencimento_2' => '',
            'vencimento_3' => '',
            'conferencia' => '',
            'forma_pagamento' => '',
            'data_reposicao' => '',
            'vendedor' => '',
            'data_retirada' => '',
            'entrada_showroom' => '',
            'quantidade_varejo_cotada' => '',
            'preco_unitario_varejo_cotado' => '',
            'subtotal_varejo_cotado' => '',
            'quantidade_caixa_cotada' => '',
            'preco_unitario_caixa_cotado' => '',
            'valor_total_caixa_cotado' => '',
            'flags_qualidade' => '',
        ], $sobrescreve);
    }

    public function test_mapeia_linha_de_compra_normal(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase());

        $this->assertSame('JAN._FEV._L8', $mapeada['origem_id']);
        $this->assertSame('compra_historica', $mapeada['tipo_registro']);
        $this->assertSame('aprovado', $mapeada['status']);
        $this->assertSame('JAN.  FEV.', $mapeada['aba_origem']);
        $this->assertSame('Jan-Fev', $mapeada['mes_origem']);
        $this->assertSame('2026-01-26', $mapeada['data_compra']);
        $this->assertSame('Suporte P/ Gabinte', $mapeada['product_name']);
        $this->assertSame('C3 Tech', $mapeada['supplier']);
        $this->assertSame(100, $mapeada['quantity']);
        $this->assertSame(1840.22, $mapeada['valor']);
        $this->assertSame('Yhan', $mapeada['requester_name']);
        $this->assertSame('JAN.  FEV._PEDIDO_202348', $mapeada['grupo_id']);
        $this->assertSame('Importação histórica', $mapeada['reason']);
        $this->assertSame('baixa', $mapeada['urgency']);
        $this->assertNull($mapeada['admin_note']);
        $this->assertSame('1.0', $mapeada['dados_importacao']['filial']);
    }

    public function test_linha_com_alertas_de_qualidade_usa_fallbacks_seguros(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase([
            'quantidade' => '',
            'preco_unitario' => '',
            'preco_total' => '',
            'data' => '',
            'descricao' => '',
            'codigo' => 'AI08',
            'flags_qualidade' => 'quantidade_ausente;preco_ausente;data_ausente;descricao_ausente;',
        ]));

        $this->assertSame(0, $mapeada['quantity']);
        $this->assertNull($mapeada['valor']);
        $this->assertNull($mapeada['data_compra']);
        $this->assertSame('AI08', $mapeada['product_name']);
        $this->assertSame(
            'quantidade_ausente;preco_ausente;data_ausente;descricao_ausente;',
            $mapeada['dados_importacao']['flags_qualidade']
        );
    }

    public function test_linha_sem_descricao_nem_codigo_usa_texto_padrao(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase([
            'descricao' => '',
            'codigo' => '',
        ]));

        $this->assertSame('Item sem descrição (importado)', $mapeada['product_name']);
    }

    public function test_linha_showroom_usa_vendedor_quando_nao_ha_requisitante(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase([
            'aba_origem' => 'SHOWROOM',
            'requisitante' => '',
            'vendedor' => 'Gley',
        ]));

        $this->assertSame('Gley', $mapeada['requester_name']);
    }

    public function test_linha_sem_pedido_usa_origem_id_como_grupo(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase(['pedido' => '']));

        $this->assertSame('JAN._FEV._L8', $mapeada['grupo_id']);
    }

    public function test_linha_compra_pati_vira_cotacao_historica_sem_valor_numerico(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase([
            'aba_origem' => 'Compra Pati',
            'mes_origem' => 'Cotação Pati',
            'pedido' => '',
            'quantidade' => '',
            'preco_unitario' => '',
            'preco_total' => '',
            'descricao' => 'TOMATE MTR-1090 MICROFONE GAMER RGB',
            'codigo' => 'MTR-1090',
            'observacao' => 'Sem saldo no Fornecedor',
            'quantidade_varejo_cotada' => '1.0',
            'preco_unitario_varejo_cotado' => 'R$ 114,90',
            'subtotal_varejo_cotado' => 'R$ 114,90',
            'quantidade_caixa_cotada' => '50',
            'preco_unitario_caixa_cotado' => '79.9',
            'valor_total_caixa_cotado' => '3995.0',
        ]));

        $this->assertSame('cotacao_historica', $mapeada['tipo_registro']);
        $this->assertSame('pendente', $mapeada['status']);
        $this->assertNull($mapeada['valor']);
        $this->assertSame('Sem saldo no Fornecedor', $mapeada['admin_note']);
        $this->assertSame('R$ 114,90', $mapeada['dados_importacao']['preco_unitario_varejo_cotado']);
        $this->assertSame('79.9', $mapeada['dados_importacao']['preco_unitario_caixa_cotado']);
    }

    public function test_traco_isolado_e_tratado_como_campo_vazio(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase([
            'pedido' => '-',
            'data_reposicao' => '-',
            'requisitante' => '-',
        ]));

        $this->assertArrayNotHasKey('pedido', $mapeada['dados_importacao'] ?? []);
        $this->assertArrayNotHasKey('data_reposicao', $mapeada['dados_importacao'] ?? []);
        $this->assertNull($mapeada['requester_name']);
        $this->assertSame('JAN._FEV._L8', $mapeada['grupo_id']);
    }

    public function test_calcula_valor_a_partir_de_quantidade_x_preco_unitario_quando_falta_preco_total(): void
    {
        $mapeada = (new HistoricoComprasImporter())->mapear($this->linhaBase([
            'preco_total' => '',
            'quantidade' => '10',
            'preco_unitario' => '5.5',
        ]));

        $this->assertSame(55.0, $mapeada['valor']);
    }
}
