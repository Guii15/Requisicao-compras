<?php

namespace Tests\Feature;

use App\Models\ConferenciaFoto;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Chegou só uma parte (ex.: 500 de 1000) e o item foi conferido; quando o resto chega depois,
 * a Conferência precisa conseguir fotografar essa segunda chegada no mesmo item.
 * Cada foto mostra a data e a hora em que foi enviada.
 */
class ConferenciaFotosDepoisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function conferente(): User
    {
        return User::factory()->create(['role' => 'conferente']);
    }

    private function divergente(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'quantity' => 1000,
            'quantidade_recebida' => 500, 'observacao_conferencia' => 'Chegaram 500 de 1000', 'product_name' => 'Cabo HDMI',
        ], $attrs));
    }

    public function test_conferente_adiciona_fotos_a_item_ja_conferido(): void
    {
        $item = $this->divergente();
        ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => 'conferencia/primeira.jpg', 'nome_original' => 'primeira.jpg']);

        $this->actingAs($this->conferente())
            ->post(route('conferencia.fotos', $item), [
                'fotos' => [UploadedFile::fake()->image('resto1.jpg'), UploadedFile::fake()->image('resto2.jpg')],
            ])
            ->assertRedirect(route('conferencia.index', ['aba' => 'conferidos']))
            ->assertSessionHas('success');

        $this->assertSame(3, $item->fotosConferencia()->count());
        $nova = $item->fotosConferencia()->where('nome_original', 'resto1.jpg')->first();
        Storage::disk('public')->assertExists($nova->caminho_arquivo);

        // Só a foto entra: a conferência registrada continua igual.
        $item->refresh();
        $this->assertSame('divergente', $item->status_conferencia);
        $this->assertSame(500, (int) $item->quantidade_recebida);
        $this->assertSame('Chegaram 500 de 1000', $item->observacao_conferencia);
    }

    public function test_item_ainda_nao_conferido_usa_o_conferir_e_nao_recebe_foto_avulsa(): void
    {
        $item = $this->divergente(['status_conferencia' => null, 'quantidade_recebida' => null]);

        $this->actingAs($this->conferente())
            ->post(route('conferencia.fotos', $item), ['fotos' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertSessionHas('aviso');

        $this->assertSame(0, $item->fotosConferencia()->count());
    }

    public function test_precisa_de_pelo_menos_uma_imagem(): void
    {
        $item = $this->divergente();

        $this->actingAs($this->conferente())
            ->post(route('conferencia.fotos', $item), ['fotos' => [UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')]])
            ->assertSessionHasErrors('fotos.0');

        $this->actingAs($this->conferente())
            ->post(route('conferencia.fotos', $item), [])
            ->assertSessionHasErrors('fotos');

        $this->assertSame(0, $item->fotosConferencia()->count());
    }

    public function test_vendedor_nao_adiciona_foto(): void
    {
        $item = $this->divergente();

        $this->actingAs(User::factory()->create(['role' => null]))
            ->post(route('conferencia.fotos', $item), ['fotos' => [UploadedFile::fake()->image('a.jpg')]])
            ->assertForbidden();

        $this->assertSame(0, $item->fotosConferencia()->count());
    }

    public function test_conferidos_mostra_botao_de_adicionar_fotos(): void
    {
        $item = $this->divergente();

        $html = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'conferidos']))->assertOk()->getContent();

        $this->assertStringContainsString(route('conferencia.fotos', $item), $html);
        $this->assertStringContainsString('Adicionar fotos', $html);
    }

    public function test_cada_foto_mostra_data_e_hora_do_envio(): void
    {
        $item = $this->divergente();
        $foto = ConferenciaFoto::create(['purchase_request_id' => $item->id, 'caminho_arquivo' => 'conferencia/resto.jpg', 'nome_original' => 'resto.jpg']);
        $foto->forceFill(['created_at' => Carbon::parse('2026-10-23 18:10:00', 'UTC')])->save();

        $html = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'conferidos']))->assertOk()->getContent();

        $this->assertStringContainsString('23/10/2026 15:10', $html); // horário de Brasília
    }
}
