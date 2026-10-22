<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\FornecedorMesclagem;

/**
 * Liga um texto digitado a um fornecedor já cadastrado, mas SÓ quando o nome é idêntico depois
 * de normalizar (caixa, acento, pontuação, LTDA...). Nunca cria fornecedor nem tenta adivinhar
 * parecidos: isso é decidido na unificação (php artisan fornecedores:unificar) ou quando o
 * Financeiro junta dois fornecedores na tela (aí o nome juntado segue para o que ficou).
 */
class FornecedorResolver
{
    public function exato(?string $nome): ?Fornecedor
    {
        $chave = Fornecedor::normalizar($nome);
        if ($chave === '') {
            return null;
        }

        // Nome que o Financeiro juntou a outro ("Joyce" -> "Joyce Informática") vai para o que ficou.
        for ($passos = 0; $passos < 10 && ($juncao = FornecedorMesclagem::where('origem_normalizado', $chave)->latest('id')->first()); $passos++) {
            $chave = Fornecedor::normalizar($juncao->destino_nome);
        }

        return Fornecedor::where('nome_normalizado', $chave)->first();
    }
}
