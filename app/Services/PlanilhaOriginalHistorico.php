<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * Le a copia da planilha Excel original guardada em storage/app/private
 * (fora da pasta publica, nunca commitada no git). O arquivo e' atualizado
 * manualmente direto no servidor (fora do app) sempre que a planilha de
 * verdade mudar — isso aqui so' serve pra consulta/visualizacao/download,
 * separado da tabela ja importada em purchase_requests.
 */
class PlanilhaOriginalHistorico
{
    private const PASTA = 'planilhas-historico';
    private const ARQUIVO = self::PASTA . '/planilha-atual.xlsx';

    public function existe(): bool
    {
        return Storage::disk('local')->exists(self::ARQUIVO);
    }

    public function caminhoCompleto(): string
    {
        return Storage::disk('local')->path(self::ARQUIVO);
    }

    public function atualizadaEm(): ?Carbon
    {
        if (!$this->existe()) {
            return null;
        }

        return Carbon::createFromTimestamp(Storage::disk('local')->lastModified(self::ARQUIVO));
    }

    /**
     * @return string[]
     */
    public function nomesDasAbas(): array
    {
        if (!$this->existe()) {
            return [];
        }

        // listWorksheetNames le so' o indice das abas; IOFactory::load carregava a planilha inteira (com imagens) a cada acesso.
        $caminho = $this->caminhoCompleto();

        return IOFactory::createReaderForFile($caminho)->listWorksheetNames($caminho);
    }

    /**
     * Le os valores de uma aba como array de linhas, pra exibir numa tabela
     * na propria tela (sem precisar baixar o arquivo).
     *
     * @return array<int, array<int, mixed>>
     */
    public function lerLinhasDaAba(string $nomeAba): array
    {
        $planilha = IOFactory::load($this->caminhoCompleto());

        if (!$planilha->sheetNameExists($nomeAba)) {
            throw new RuntimeException("Aba \"{$nomeAba}\" não encontrada na planilha.");
        }

        return $planilha->getSheetByName($nomeAba)->toArray(null, true, true, false);
    }

    /**
     * Extrai uma unica aba pra um arquivo .xlsx temporario e devolve o caminho.
     * Quem chama e' responsavel por apagar o arquivo depois de usar.
     */
    public function extrairAba(string $nomeAba): string
    {
        $planilhaCompleta = IOFactory::load($this->caminhoCompleto());

        if (!$planilhaCompleta->sheetNameExists($nomeAba)) {
            throw new RuntimeException("Aba \"{$nomeAba}\" não encontrada na planilha.");
        }

        $novaPlanilha = new Spreadsheet();
        $novaPlanilha->removeSheetByIndex(0);
        $novaPlanilha->addSheet($planilhaCompleta->getSheetByName($nomeAba)->copy());

        $caminhoTemp = tempnam(sys_get_temp_dir(), 'aba_') . '.xlsx';
        (new Xlsx($novaPlanilha))->save($caminhoTemp);

        return $caminhoTemp;
    }
}
