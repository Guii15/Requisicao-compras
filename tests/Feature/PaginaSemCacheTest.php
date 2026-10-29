<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Um conferente via um item que outro já tinha conferido: o navegador (sobretudo
 * no celular, ao voltar para a aba ou usar o "voltar") mostrava a cópia guardada
 * da página. Páginas de quem está logado agora vão com Cache-Control: no-store,
 * então o navegador sempre busca a lista atual.
 */
class PaginaSemCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_da_conferencia_nao_fica_guardada_no_navegador(): void
    {
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $resposta = $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index'))
            ->assertOk();

        $this->assertStringContainsString('no-store', $resposta->headers->get('Cache-Control'));
    }

    public function test_pagina_da_entrada_nao_fica_guardada_no_navegador(): void
    {
        $resposta = $this->actingAs(User::factory()->create(['role' => 'entrada']))
            ->get(route('entrada.index'))
            ->assertOk();

        $this->assertStringContainsString('no-store', $resposta->headers->get('Cache-Control'));
    }

    public function test_visitante_nao_logado_continua_sem_no_store(): void
    {
        $resposta = $this->get('/login')->assertOk();

        $this->assertStringNotContainsString('no-store', (string) $resposta->headers->get('Cache-Control'));
    }
}
