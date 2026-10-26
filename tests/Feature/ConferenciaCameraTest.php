<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * No celular, o campo de foto com accept só por extensão (.jpg,.png...) abria direto a galeria,
 * sem opção de câmera: a Conferência tinha que tirar a foto antes e depois escolher.
 * Agora os campos aceitam image/* (a galeria continua) e cada um tem o botão "Tirar foto",
 * que abre a câmera traseira (capture="environment") e coloca a foto no campo.
 */
class ConferenciaCameraTest extends TestCase
{
    use RefreshDatabase;

    private function pagina(array $query = []): string
    {
        return $this->actingAs(User::factory()->create(['role' => 'conferente']))
            ->get(route('conferencia.index', $query))->assertOk()->getContent();
    }

    public function test_conferir_tem_botao_de_camera_para_foto_e_fotos_extras(): void
    {
        PurchaseRequest::factory()->create(['status' => 'aprovado', 'status_conferencia' => null]);

        $html = $this->pagina();

        $this->assertMatchesRegularExpression('/<input[^>]*name="foto"[^>]*accept="image\/\*"/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="fotos_extras\[\]"[^>]*accept="image\/\*"/', $html);
        $this->assertStringContainsString("tirarFoto('campo-foto')", $html);
        $this->assertStringContainsString("tirarFoto('campo-fotos-extras')", $html);
        $this->assertSame(1, substr_count($html, 'capture="environment"'));
        $this->assertStringNotContainsString('accept=".jpg,.jpeg,.png,.webp"', $html);
    }

    public function test_adicionar_fotos_tem_botao_de_camera(): void
    {
        PurchaseRequest::factory()->create([
            'status' => 'aprovado', 'status_conferencia' => 'divergente', 'quantity' => 10, 'quantidade_recebida' => 5,
        ]);

        $html = $this->pagina(['aba' => 'conferidos']);

        $this->assertMatchesRegularExpression('/<input[^>]*name="fotos\[\]"[^>]*accept="image\/\*"/', $html);
        $this->assertStringContainsString("tirarFoto('campo-fotos-adicionar')", $html);
        $this->assertSame(1, substr_count($html, 'capture="environment"'));
    }
}
