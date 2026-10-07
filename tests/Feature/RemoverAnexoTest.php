<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Remover o anexo da requisição (vendedor e admin) e o pedido de compra (admin): antes só dava para trocar por outro arquivo.
 */
class RemoverAnexoTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);
        $this->admin = User::factory()->create(['is_admin' => true]);
    }

    private function requisicao(array $attrs = [], bool $criarArquivo = true): PurchaseRequest
    {
        $item = PurchaseRequest::factory()->create(array_merge([
            'user_id' => $this->vendedor->id, 'status' => 'pendente',
            'anexo_path' => 'anexos-requisicao/orcamento.pdf', 'anexo_nome' => 'orcamento.pdf',
        ], $attrs));

        if ($criarArquivo) {
            foreach (array_filter([$item->anexo_path, $item->pedido_compra_path]) as $caminho) {
                Storage::disk('local')->put($caminho, 'conteudo');
            }
        }

        return $item;
    }

    // ---------- vendedor ----------

    public function test_vendedor_remove_o_proprio_anexo(): void
    {
        $item = $this->requisicao();

        $this->actingAs($this->vendedor)->from(route('requests.edit', $item))
            ->delete(route('requests.anexo.remover', $item))
            ->assertRedirect(route('requests.edit', $item))
            ->assertSessionHas('success');

        $item->refresh();
        $this->assertNull($item->anexo_path);
        $this->assertNull($item->anexo_nome);
        Storage::disk('local')->assertMissing('anexos-requisicao/orcamento.pdf');
    }

    public function test_vendedor_nao_remove_anexo_de_outro_vendedor(): void
    {
        $item = $this->requisicao();

        $this->actingAs(User::factory()->create(['role' => null, 'is_admin' => false]))
            ->delete(route('requests.anexo.remover', $item))->assertForbidden();

        $this->assertNotNull($item->fresh()->anexo_path);
        Storage::disk('local')->assertExists('anexos-requisicao/orcamento.pdf');
    }

    public function test_vendedor_so_remove_enquanto_a_requisicao_esta_pendente(): void
    {
        $item = $this->requisicao(['status' => 'aprovado']);

        $this->actingAs($this->vendedor)->delete(route('requests.anexo.remover', $item))->assertSessionHas('error');

        $this->assertNotNull($item->fresh()->anexo_path);
        Storage::disk('local')->assertExists('anexos-requisicao/orcamento.pdf');
    }

    public function test_arquivo_usado_por_outra_requisicao_nao_e_apagado(): void
    {
        $item = $this->requisicao();
        PurchaseRequest::factory()->create(['user_id' => $this->vendedor->id, 'anexo_path' => 'anexos-requisicao/orcamento.pdf', 'anexo_nome' => 'orcamento.pdf']);

        $this->actingAs($this->vendedor)->delete(route('requests.anexo.remover', $item))->assertSessionHas('success');

        $this->assertNull($item->fresh()->anexo_path);
        Storage::disk('local')->assertExists('anexos-requisicao/orcamento.pdf');
    }

    public function test_referencia_a_arquivo_que_nao_existe_mais_tambem_e_limpa(): void
    {
        $item = $this->requisicao([], criarArquivo: false);

        $this->actingAs($this->vendedor)->delete(route('requests.anexo.remover', $item))->assertSessionHas('success');

        $this->assertNull($item->fresh()->anexo_path);
    }

    public function test_sem_anexo_avisa_e_nao_quebra(): void
    {
        $item = $this->requisicao(['anexo_path' => null, 'anexo_nome' => null]);

        $this->actingAs($this->vendedor)->delete(route('requests.anexo.remover', $item))->assertSessionHas('aviso');
    }

    public function test_tela_de_editar_mostra_o_botao_so_quando_ha_anexo(): void
    {
        $com = $this->requisicao();
        $sem = $this->requisicao(['anexo_path' => null, 'anexo_nome' => null]);

        $this->actingAs($this->vendedor)->get(route('requests.edit', $com))->assertSee('Remover anexo');
        $this->actingAs($this->vendedor)->get(route('requests.edit', $sem))->assertDontSee('Remover anexo');
    }

    // ---------- admin ----------

    public function test_admin_remove_o_anexo_da_requisicao(): void
    {
        $item = $this->requisicao();

        $this->actingAs($this->admin)->delete(route('admin.requests.anexo.remover', $item))->assertSessionHas('success');

        $this->assertNull($item->fresh()->anexo_path);
        Storage::disk('local')->assertMissing('anexos-requisicao/orcamento.pdf');
    }

    public function test_admin_remove_o_anexo_mesmo_com_a_requisicao_ja_aprovada(): void
    {
        $item = $this->requisicao(['status' => 'aprovado']);

        $this->actingAs($this->admin)->delete(route('admin.requests.anexo.remover', $item))->assertSessionHas('success');

        $this->assertNull($item->fresh()->anexo_path);
    }

    public function test_admin_remove_o_pedido_de_compra(): void
    {
        $item = $this->requisicao(['status' => 'aprovado', 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);

        $this->actingAs($this->admin)->delete(route('admin.compras.pedido.remover', $item))->assertSessionHas('success');

        $item->refresh();
        $this->assertNull($item->pedido_compra_path);
        $this->assertNull($item->pedido_compra_nome);
        $this->assertNotNull($item->anexo_path); // o anexo do vendedor não é mexido
        Storage::disk('local')->assertMissing('pedidos-compra/p.pdf');
    }

    public function test_remove_pedido_de_compra_cujo_arquivo_ja_sumiu(): void
    {
        $item = $this->requisicao(['status' => 'aprovado', 'pedido_compra_path' => 'pedidos-compra/sumiu.pdf', 'pedido_compra_nome' => 'sumiu.pdf'], criarArquivo: false);

        $this->actingAs($this->admin)->delete(route('admin.compras.pedido.remover', $item))->assertSessionHas('success');

        $this->assertNull($item->fresh()->pedido_compra_path);
    }

    public function test_pedido_de_compra_usado_por_outro_item_nao_e_apagado(): void
    {
        $item = $this->requisicao(['status' => 'aprovado', 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);
        PurchaseRequest::factory()->create(['pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);

        $this->actingAs($this->admin)->delete(route('admin.compras.pedido.remover', $item));

        $this->assertNull($item->fresh()->pedido_compra_path);
        Storage::disk('local')->assertExists('pedidos-compra/p.pdf');
    }

    public function test_rotas_do_admin_nao_servem_para_vendedor_nem_visitante(): void
    {
        $item = $this->requisicao(['pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);

        $this->delete(route('admin.requests.anexo.remover', $item))->assertRedirect(route('login'));
        $this->delete(route('admin.compras.pedido.remover', $item))->assertRedirect(route('login'));

        $this->actingAs($this->vendedor)->delete(route('admin.requests.anexo.remover', $item))->assertForbidden();
        $this->actingAs($this->vendedor)->delete(route('admin.compras.pedido.remover', $item))->assertForbidden();

        $this->assertNotNull($item->fresh()->anexo_path);
        $this->assertNotNull($item->fresh()->pedido_compra_path);
    }

    public function test_dados_da_compra_mostra_o_botao_de_remover_o_pedido(): void
    {
        $com = $this->requisicao(['status' => 'aprovado', 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);
        $sem = $this->requisicao(['status' => 'aprovado']);

        // a janela "Dados da compra" de Compras Feitas é preenchida pelo botão de cada item
        $htmlCom = $this->actingAs($this->admin)->get(route('admin.compras.feitas', ['abrir' => $com->id]))->assertOk()->getContent();
        $htmlSem = $this->actingAs($this->admin)->get(route('admin.compras.feitas', ['abrir' => $sem->id]))->assertOk()->getContent();

        $dadosCom = $this->dadosDaJanelaDeCompra($htmlCom, $com->id);
        $this->assertSame(route('admin.compras.pedido.remover', $com), $dadosCom['removerUrl']);
        $this->assertSame(route('admin.compras.pedido', $com), $dadosCom['pedidoUrl']);
        $this->assertSame('p.pdf', $dadosCom['pedidoNome']);

        $dadosSem = $this->dadosDaJanelaDeCompra($htmlSem, $sem->id);
        $this->assertNull($dadosSem['removerUrl']);
        $this->assertNull($dadosSem['pedidoUrl']);

        // o botão Remover envia o formulário próprio (DELETE), que recebe o endereço do item ao abrir a janela
        $this->assertStringContainsString('<form id="jc-remover-pedido" method="POST"', $htmlCom);
        $this->assertStringContainsString('<button type="submit" form="jc-remover-pedido"', $htmlCom);
        $this->assertStringContainsString("document.getElementById('jc-remover-pedido').action = d.removerUrl;", $htmlCom);
        $this->assertStringContainsString("atual.style.display = d.pedidoUrl ? 'flex' : 'none';", $htmlCom); // sem pedido, o botão fica escondido
    }

    public function test_quadro_de_atualizar_requisicao_mostra_os_dois_botoes(): void
    {
        // o painel de Requisições lista as que têm algo pendente
        $this->requisicao(['status' => 'pendente', 'pedido_compra_path' => 'pedidos-compra/p.pdf', 'pedido_compra_nome' => 'p.pdf']);

        $html = $this->actingAs($this->admin)->get(route('admin.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Remover anexo', $html);
        $this->assertStringContainsString('Remover pedido de compra', $html);
    }
}
