<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Arquivo anexado que é removido ou trocado não é apagado de vez: vai para `lixeira/AAAA-MM-DD/` no mesmo disco
 * e fica guardado por 30 dias (o nome mantém a pasta de origem: `pedidos-compra__abc.pdf`). Quem descarta um arquivo
 * também apaga o que já passou de 30 dias, então não precisa de agendamento.
 */
class LixeiraDeArquivos
{
    public const PASTA = 'lixeira';
    public const DIAS = 30;

    /** Move o arquivo para a lixeira do dia. Devolve false se não havia arquivo. */
    public static function descartar(string $disco, ?string $caminho): bool
    {
        if (!$caminho) {
            return false;
        }

        $storage = Storage::disk($disco);

        if (!$storage->exists($caminho)) {
            return false;
        }

        $dia = now('America/Sao_Paulo')->format('Y-m-d');
        $nome = str_replace('/', '__', $caminho);
        $destino = self::PASTA . '/' . $dia . '/' . $nome;

        if ($storage->exists($destino)) {
            $destino = self::PASTA . '/' . $dia . '/' . uniqid() . '__' . $nome;
        }

        $storage->move($caminho, $destino);
        self::limparAntigos($disco);

        return true;
    }

    /** Apaga de vez as pastas de dia mais velhas que o prazo. Devolve quantas apagou. */
    public static function limparAntigos(string $disco, int $dias = self::DIAS): int
    {
        $storage = Storage::disk($disco);
        $limite = now('America/Sao_Paulo')->subDays($dias)->format('Y-m-d');
        $apagadas = 0;

        foreach ($storage->directories(self::PASTA) as $pasta) {
            $dia = basename($pasta);

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dia) && $dia < $limite) {
                $storage->deleteDirectory($pasta);
                $apagadas++;
            }
        }

        return $apagadas;
    }
}
