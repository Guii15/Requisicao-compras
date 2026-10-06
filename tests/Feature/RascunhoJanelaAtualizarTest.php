<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A janela "Atualizar Requisição" guarda um rascunho no navegador: sair sem querer ou um erro ao salvar
 * não pode fazer o admin perder o que digitou. Aqui testamos a ligação no HTML; o comportamento foi
 * conferido no navegador.
 */
class RascunhoJanelaAtualizarTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_os_dois_formularios_da_janela_levam_o_id_e_a_versao_do_item(): void
    {
        $item = PurchaseRequest::factory()->create();

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()->getContent();

        // desktop e celular: um formulário de cada
        $this->assertSame(2, substr_count($html, 'data-rascunho="' . $item->id . '"'));
        $this->assertSame(2, substr_count($html, 'data-versao="' . $item->updated_at->timestamp . '"'));
    }

    public function test_a_pagina_carrega_o_script_do_rascunho_uma_unica_vez(): void
    {
        PurchaseRequest::factory()->count(3)->create();

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'rascunho-requisicao-'));
        $this->assertStringContainsString('Descartar rascunho', $html);
    }

    public function test_avisa_ao_script_quando_o_salvamento_falhou_para_manter_o_rascunho(): void
    {
        $item = PurchaseRequest::factory()->create();
        $admin = $this->admin();

        // salvar com erro (parcelado sem parcelas): volta para o admin com a janela do item aberta
        $this->actingAs($admin)->from(route('admin.index'))->patch(route('admin.requests.update', $item), [
            'status'             => 'aprovado',
            'condicao_pagamento' => 'parcelado',
        ])->assertSessionHasErrors('parcelas');

        $html = $this->actingAs($admin)->get(route('admin.index'))->getContent();

        $this->assertStringContainsString('var comErro = ' . $item->id . ';', $html);
    }

    public function test_sem_erro_o_script_nao_marca_nenhum_item_com_erro(): void
    {
        PurchaseRequest::factory()->create();

        $html = $this->actingAs($this->admin())->get(route('admin.index'))->getContent();

        $this->assertStringContainsString('var comErro = null;', $html);
    }
}
