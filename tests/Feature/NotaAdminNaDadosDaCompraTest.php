<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A nota do comprador (admin_note) e a obs do vendedor (justification) podem ser corrigidas na janela "Dados da compra" de Compras Feitas,
 * mesmo com o item já conferido ou com entrada (a aba Requisições só lista o que ainda tem item pendente).
 */
class NotaAdminNaDadosDaCompraTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function salvar(PurchaseRequest $item, array $extra = [])
    {
        return $this->actingAs($this->admin)->patch(route('admin.compras.update', $item), array_merge([
            'data_compra' => '2026-10-19', 'preco_unitario' => '80,00', 'supplier' => 'Joice',
            'condicao_pagamento' => 'a_vista', 'origem' => 'lista',
        ], $extra));
    }

    public function test_corrige_a_nota_do_comprador_num_item_ja_conferido(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create([
            'admin_note' => 'SOMENTE 15 UNIDADES DO OSMAR', 'status_conferencia' => 'conferido_ok',
        ]);

        $this->salvar($item, ['admin_note' => 'SOMENTE 10 UNIDADES DO OSMAR'])->assertSessionHasNoErrors();

        $this->assertSame('SOMENTE 10 UNIDADES DO OSMAR', $item->fresh()->admin_note);
    }

    public function test_sem_o_campo_a_nota_fica_como_estava(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['admin_note' => 'Nota antiga']);

        $this->salvar($item)->assertSessionHasNoErrors();

        $this->assertSame('Nota antiga', $item->fresh()->admin_note);
    }

    public function test_nota_tem_limite_de_tamanho(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create();

        $this->salvar($item, ['admin_note' => str_repeat('a', 2001)])->assertSessionHasErrors('admin_note');
    }

    public function test_corrige_a_obs_do_vendedor_na_mesma_janela(): void
    {
        $item = PurchaseRequest::factory()->aprovado()->create(['justification' => 'Filial 13', 'status_conferencia' => 'conferido_ok']);

        $this->salvar($item, ['justification' => 'Filial 31'])->assertSessionHasNoErrors();
        $this->assertSame('Filial 31', $item->fresh()->justification);

        $this->salvar($item)->assertSessionHasNoErrors();
        $this->assertSame('Filial 31', $item->fresh()->justification);

        $this->salvar($item, ['justification' => str_repeat('a', 501)])->assertSessionHasErrors('justification');
    }

    public function test_janela_dados_da_compra_tem_o_campo_da_nota(): void
    {
        $this->actingAs($this->admin)->get(route('admin.compras.feitas'))
            ->assertOk()
            ->assertSee('<textarea name="admin_note"', false)
            ->assertSee('Nota do comprador')
            ->assertSee('<textarea name="justification"', false)
            ->assertSee('Obs do vendedor');
    }
}
