<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * O que o botão do item, em Compras Feitas, entrega para a janela "Dados da compra" (atributo data-compra).
     * O JSON vem escapado no HTML; aqui ele volta a ser um array.
     */
    protected function dadosDaJanelaDeCompra(string $html, int $id): array
    {
        $achou = preg_match('/data-compra-id="' . $id . '" data-compra="([^"]*)"/', $html, $m);
        $this->assertSame(1, $achou, "O botão do item {$id} (data-compra-id) não está na página.");

        $dados = json_decode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), true);
        $this->assertIsArray($dados, "O data-compra do item {$id} não é um JSON válido.");

        return $dados;
    }
}
