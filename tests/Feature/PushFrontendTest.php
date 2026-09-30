<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_logada_tem_manifest_botao_e_chave_publica_quando_configurada(): void
    {
        config(['services.webpush.public_key' => 'CHAVE_PUBLICA_DE_TESTE']);
        $user = User::factory()->create(['role' => 'conferente']);

        $response = $this->actingAs($user)->get(route('conferencia.index'));

        $response->assertOk();
        $response->assertSee('rel="manifest"', false);
        $response->assertSee('name="vapid-public-key" content="CHAVE_PUBLICA_DE_TESTE"', false);
        $response->assertSee('data-push-toggle', false);
        $response->assertSee('data-subscribe-url="' . route('push.subscribe') . '"', false);
    }

    public function test_sem_chave_configurada_nao_expoe_a_meta_da_chave(): void
    {
        config(['services.webpush.public_key' => null]);
        $user = User::factory()->create(['role' => 'conferente']);

        $this->actingAs($user)->get(route('conferencia.index'))
            ->assertOk()
            ->assertDontSee('vapid-public-key', false);
    }

    public function test_tela_de_login_tambem_linka_o_manifest(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('rel="manifest"', false);
    }

    public function test_manifest_e_valido_e_aponta_para_icones_que_existem(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertNotNull($manifest, 'manifest.webmanifest não é um JSON válido');
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);
        $tamanhos = array_column($manifest['icons'], 'sizes');
        $this->assertContains('192x192', $tamanhos);
        $this->assertContains('512x512', $tamanhos);
        foreach ($manifest['icons'] as $icone) {
            $this->assertFileExists(public_path(ltrim($icone['src'], '/')));
        }
    }

    public function test_service_worker_trata_push_e_clique(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("addEventListener('push'", $sw);
        $this->assertStringContainsString("addEventListener('notificationclick'", $sw);
        $this->assertStringContainsString('showNotification', $sw);
    }
}
