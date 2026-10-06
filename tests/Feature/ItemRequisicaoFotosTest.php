<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * A miniatura da foto no cartão do item abria um modal "foto-{id}" que só existia na tela do vendedor;
 * no admin o clique não fazia nada. O cartão agora leva a própria janela com todas as fotos.
 */
class ItemRequisicaoFotosTest extends TestCase
{
    use RefreshDatabase;

    /** Grupo com um item pendente (para aparecer no admin) e um aprovado e conferido com fotos. */
    private function grupoComFotos(User $dono, int $qtdFotos = 3): PurchaseRequest
    {
        $grupo = (string) Str::uuid();
        PurchaseRequest::factory()->create(['user_id' => $dono->id, 'grupo_id' => $grupo]);
        $conferido = PurchaseRequest::factory()->aprovado()->create([
            'user_id'            => $dono->id,
            'grupo_id'           => $grupo,
            'status_conferencia' => 'conferido_ok',
        ]);
        for ($i = 1; $i <= $qtdFotos; $i++) {
            ConferenciaFoto::create([
                'purchase_request_id' => $conferido->id,
                'caminho_arquivo'     => "conferencia/foto-$i.jpg",
                'nome_original'       => "foto-$i.jpg",
            ]);
        }

        return $conferido;
    }

    /** Devolve o html do cartão do item e confere que o clique da miniatura aponta para algo que existe. */
    private function assertMiniaturaAbreUmaJanelaQueExiste(string $html, int $qtdFotos): void
    {
        $this->assertSame(1, preg_match("/onclick=\"document\.getElementById\('([^']+)'\)\.style\.display='flex'\" title=\"Ver foto da conferência\"/", $html, $m), 'miniatura sem onclick de abrir');
        $idAlvo = $m[1];
        $this->assertSame(1, substr_count($html, 'id="' . $idAlvo . '"'), "a janela #$idAlvo precisa existir exatamente uma vez");

        $this->assertSame(1, preg_match('/id="' . preg_quote($idAlvo, '/') . '".*?<\/section>/s', $html, $janela), 'janela sem fechamento');
        $this->assertSame($qtdFotos, preg_match_all('/<img [^>]*foto-\d\.jpg/', $janela[0]), 'a janela deve ter todas as fotos');
    }

    public function test_admin_clica_na_miniatura_e_a_janela_existe_com_todas_as_fotos(): void
    {
        $dono = User::factory()->create();
        $this->grupoComFotos($dono, 3);

        $html = $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.index'))->assertOk()->getContent();

        $this->assertMiniaturaAbreUmaJanelaQueExiste($html, 3);
    }

    public function test_vendedor_tambem_continua_abrindo_a_janela_com_todas_as_fotos(): void
    {
        $dono = User::factory()->create();
        $this->grupoComFotos($dono, 2);

        $html = $this->actingAs($dono)->get(route('requests.index'))->assertOk()->getContent();

        $this->assertStringContainsString('foto-1.jpg', $html);
        $this->assertStringContainsString('foto-2.jpg', $html);
    }

    public function test_item_sem_foto_nao_mostra_miniatura_nem_janela(): void
    {
        $dono = User::factory()->create();
        PurchaseRequest::factory()->create(['user_id' => $dono->id]);

        $html = $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString("Ver foto da conferência", $html);
        $this->assertStringNotContainsString('id="foto-item-', $html);
    }
}
