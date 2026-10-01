<?php

namespace App\Services;

use App\Models\Fornecedor;

/**
 * Liga um texto digitado a um fornecedor já cadastrado, mas SÓ quando o nome é idêntico depois
 * de normalizar (caixa, acento, pontuação, LTDA...). Nunca cria fornecedor nem tenta adivinhar
 * parecidos: isso é decidido na unificação (php artisan fornecedores:unificar).
 */
class FornecedorResolver
{
    public function exato(?string $nome): ?Fornecedor
    {
        $chave = Fornecedor::normalizar($nome);

        return $chave === '' ? null : Fornecedor::where('nome_normalizado', $chave)->first();
    }
}
