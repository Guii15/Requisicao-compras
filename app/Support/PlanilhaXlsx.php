<?php

namespace App\Support;

use DateTimeInterface;
use RuntimeException;
use ZipArchive;

/**
 * Planilha Excel (.xlsx) simples, sem biblioteca externa: uma aba por tabela, com título, cabeçalho em negrito
 * e fixo, filtro, colunas de dinheiro em R$, datas em dd/mm/aaaa e uma linha de total opcional.
 *
 * Coluna: ['titulo' => 'Valor', 'tipo' => 'texto|dinheiro|data|numero', 'largura' => 14].
 * Uma célula pode trocar o tipo da coluna: ['v' => 3, 't' => 'numero'].
 */
class PlanilhaXlsx
{
    private const ESTILO = ['texto' => 0, 'dinheiro' => 2, 'data' => 3, 'numero' => 4];
    private const ESTILO_TOTAL = ['texto' => 5, 'dinheiro' => 6, 'data' => 5, 'numero' => 8];
    private const CABECALHO = 1;
    private const TITULO = 7;
    private const SUBTITULO = 9;

    /** @var list<array{nome: string, xml: string}> */
    private array $abas = [];

    public function aba(string $nome, string $titulo, string $subtitulo, array $colunas, array $linhas, ?array $total = null): self
    {
        $nome = mb_substr(str_replace(['[', ']', ':', '*', '?', '/', '\\'], ' ', $nome), 0, 31);
        $tipos = array_map(fn ($c) => $c['tipo'] ?? 'texto', $colunas);
        $ultimaColuna = $this->letra(count($colunas) - 1);
        $cabecalho = 4;

        $xml = [];
        $xml[] = $this->linha(1, [$this->celula('A1', $titulo, 'texto', self::TITULO)]);
        $xml[] = $this->linha(2, [$this->celula('A2', $subtitulo, 'texto', self::SUBTITULO)]);
        $xml[] = $this->linha($cabecalho, array_map(
            fn ($c, $i) => $this->celula($this->letra($i) . $cabecalho, $c['titulo'], 'texto', self::CABECALHO),
            $colunas,
            array_keys($colunas),
        ));

        $n = $cabecalho;
        foreach ($linhas as $linha) {
            $n++;
            $xml[] = $this->linha($n, $this->celulas(array_values($linha), $tipos, $n, self::ESTILO));
        }

        if ($total !== null) {
            $n++;
            $xml[] = $this->linha($n, $this->celulas(array_values($total), $tipos, $n, self::ESTILO_TOTAL));
        }

        $larguras = implode('', array_map(
            fn ($c, $i) => '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . ($c['largura'] ?? 14) . '" customWidth="1"/>',
            $colunas,
            array_keys($colunas),
        ));

        $filtro = count($linhas) > 0 ? '<autoFilter ref="A' . $cabecalho . ':' . $ultimaColuna . ($cabecalho + count($linhas)) . '"/>' : '';

        $this->abas[] = [
            'nome' => $nome,
            'xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
                . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
                . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="' . $cabecalho . '" topLeftCell="A' . ($cabecalho + 1) . '" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
                . '<cols>' . $larguras . '</cols>'
                . '<sheetData>' . implode('', $xml) . '</sheetData>'
                . $filtro
                . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
                . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
                . '</worksheet>',
        ];

        return $this;
    }

    /** Grava a planilha num arquivo temporário e devolve o caminho. */
    public function salvar(): string
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('A extensão zip do PHP não está instalada no servidor.');
        }

        $caminho = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($caminho, ZipArchive::OVERWRITE);

        $tiposConteudo = '';
        $planilhas = '';
        $relacoes = '';
        foreach ($this->abas as $i => $aba) {
            $k = $i + 1;
            $zip->addFromString("xl/worksheets/sheet{$k}.xml", $aba['xml']);
            $tiposConteudo .= '<Override PartName="/xl/worksheets/sheet' . $k . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $planilhas .= '<sheet name="' . $this->escapar($aba['nome']) . '" sheetId="' . $k . '" r:id="rId' . $k . '"/>';
            $relacoes .= '<Relationship Id="rId' . $k . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $k . '.xml"/>';
        }
        $idEstilos = count($this->abas) + 1;

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $tiposConteudo
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $planilhas . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relacoes
            . '<Relationship Id="rId' . $idEstilos . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->estilos());
        $zip->close();

        return $caminho;
    }

    private function celulas(array $valores, array $tipos, int $linha, array $estilos): array
    {
        $celulas = [];
        foreach ($valores as $i => $valor) {
            $tipo = $tipos[$i] ?? 'texto';
            if (is_array($valor)) {
                $tipo = $valor['t'] ?? $tipo;
                $valor = $valor['v'] ?? null;
            }
            $celulas[] = $this->celula($this->letra($i) . $linha, $valor, $tipo, $estilos[$tipo] ?? 0);
        }

        return $celulas;
    }

    private function linha(int $n, array $celulas): string
    {
        return '<row r="' . $n . '">' . implode('', array_filter($celulas)) . '</row>';
    }

    private function celula(string $ref, mixed $valor, string $tipo, int $estilo): string
    {
        if ($valor === null || $valor === '') {
            return $estilo ? '<c r="' . $ref . '" s="' . $estilo . '"/>' : '';
        }

        if ($tipo === 'data' && $valor instanceof DateTimeInterface) {
            // Data do Excel: dias desde 30/12/1899.
            $dias = intdiv((int) (new \DateTimeImmutable($valor->format('Y-m-d'), new \DateTimeZone('UTC')))->getTimestamp() + 2209161600, 86400);

            return '<c r="' . $ref . '" s="' . $estilo . '"><v>' . $dias . '</v></c>';
        }

        if (in_array($tipo, ['dinheiro', 'numero'], true) && is_numeric($valor)) {
            return '<c r="' . $ref . '" s="' . $estilo . '"><v>' . (0 + $valor) . '</v></c>';
        }

        return '<c r="' . $ref . '" t="inlineStr" s="' . $estilo . '"><is><t xml:space="preserve">' . $this->escapar((string) $valor) . '</t></is></c>';
    }

    private function letra(int $indice): string
    {
        $letra = '';
        for ($i = $indice + 1; $i > 0; $i = intdiv($i - 1, 26)) {
            $letra = chr(65 + ($i - 1) % 26) . $letra;
        }

        return $letra;
    }

    private function escapar(string $texto): string
    {
        // Caracteres de controle quebram o XML do Excel.
        return htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto), ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** 0 normal · 1 cabeçalho · 2 R$ · 3 data · 4 número · 5 total texto · 6 total R$ · 7 título · 8 total número · 9 subtítulo */
    private function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="&quot;R$&quot; #,##0.00"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy"/></numFmts>'
            . '<fonts count="5">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><color rgb="FF05018D"/><name val="Calibri"/></font>'
            . '<font><sz val="10"/><color rgb="FF6B7280"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF05018D"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFEEF0FA"/></patternFill></fill></fills>'
            . '<borders count="2"><border/><border><top style="thin"><color rgb="FF05018D"/></top></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="10">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="1" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="164" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="1" fontId="2" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="4" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
