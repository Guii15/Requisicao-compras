<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColetaTest extends TestCase
{
    use RefreshDatabase;

    private function conferente(): User
    {
        return User::factory()->create(['role' => 'conferente', 'name' => 'Maria Conferente']);
    }

    private function item(array $attrs = []): PurchaseRequest
    {
        return PurchaseRequest::factory()->create(array_merge(['status' => 'aprovado'], $attrs));
    }

    public function test_filtro_aguardando_lista_apenas_aprovados_aguardando(): void
    {
        $this->item(['product_name' => 'Item Aguardando', 'status_coleta' => 'aguardando']);
        $this->item(['product_name' => 'Item Coletado', 'status_coleta' => 'coletado']);
        $this->item(['product_name' => 'Item Atrasado', 'status_coleta' => 'atraso']);
        $this->item(['product_name' => 'Item Nao Aprovado', 'status' => 'pendente', 'status_coleta' => 'aguardando']);

        $response = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'coleta', 'resultado' => 'aguardando']));

        $response->assertOk();
        $response->assertSee('Item Aguardando');
        $response->assertDontSee('Item Coletado');
        $response->assertDontSee('Item Atrasado');
        $response->assertDontSee('Item Nao Aprovado');
    }

    public function test_filtro_coletado_lista_apenas_coletados(): void
    {
        $this->item(['product_name' => 'Item Aguardando', 'status_coleta' => 'aguardando']);
        $this->item(['product_name' => 'Item Coletado', 'status_coleta' => 'coletado']);

        $response = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'coleta', 'resultado' => 'coletado']));

        $response->assertSee('Item Coletado');
        $response->assertDontSee('Item Aguardando');
    }

    public function test_filtro_atraso_lista_apenas_atrasados(): void
    {
        $this->item(['product_name' => 'Item Aguardando', 'status_coleta' => 'aguardando']);
        $this->item(['product_name' => 'Item Atrasado', 'status_coleta' => 'atraso']);

        $response = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'coleta', 'resultado' => 'atraso']));

        $response->assertSee('Item Atrasado');
        $response->assertDontSee('Item Aguardando');
    }

    public function test_aba_coleta_sem_filtro_assume_aguardando(): void
    {
        $this->item(['product_name' => 'Item Aguardando', 'status_coleta' => 'aguardando']);
        $this->item(['product_name' => 'Item Coletado', 'status_coleta' => 'coletado']);

        $response = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'coleta']));

        $response->assertSee('Item Aguardando');
        $response->assertDontSee('Item Coletado');
    }

    public function test_aba_coleta_tem_uma_unica_barra_de_filtros_com_links_validos(): void
    {
        $response = $this->actingAs($this->conferente())
            ->get(route('conferencia.index', ['aba' => 'coleta']));

        $response->assertDontSee('resultado=coletados', false);
        $this->assertSame(1, substr_count($response->getContent(), 'resultado=coletado"'));
    }

    public function test_registrar_coleta_marca_como_coletado(): void
    {
        $item = $this->item(['status_coleta' => 'aguardando']);

        $response = $this->actingAs($this->conferente())->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T10:30',
        ]);

        $response->assertRedirect(route('conferencia.index', ['aba' => 'coleta']));
        $item->refresh();
        $this->assertSame('coletado', $item->status_coleta);
        $this->assertSame('2026-09-30 10:30', $item->data_coleta->format('Y-m-d H:i'));
        $this->assertSame('Maria Conferente', $item->coletado_por);
    }

    public function test_registrar_coleta_marca_como_atraso(): void
    {
        $item = $this->item(['status_coleta' => 'aguardando']);

        $this->actingAs($this->conferente())->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'atraso',
            'data_coleta' => '2026-09-30T10:30',
        ]);

        $item->refresh();
        $this->assertSame('atraso', $item->status_coleta);
        $this->assertNull($item->coletado_por);
    }

    public function test_registrar_coleta_rejeita_status_invalido(): void
    {
        $item = $this->item(['status_coleta' => 'aguardando']);

        $response = $this->actingAs($this->conferente())->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'qualquer',
            'data_coleta' => '2026-09-30T10:30',
        ]);

        $response->assertSessionHasErrors('status_coleta');
        $this->assertSame('aguardando', $item->fresh()->status_coleta);
    }

    public function test_registrar_coleta_exige_data(): void
    {
        $item = $this->item(['status_coleta' => 'aguardando']);

        $response = $this->actingAs($this->conferente())->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'coletado',
        ]);

        $response->assertSessionHasErrors('data_coleta');
        $this->assertSame('aguardando', $item->fresh()->status_coleta);
    }

    public function test_registrar_coleta_ignora_clique_duplicado(): void
    {
        $item = $this->item(['status_coleta' => 'coletado', 'coletado_por' => 'Outra Pessoa']);

        $response = $this->actingAs($this->conferente())->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T10:30',
        ]);

        $response->assertSessionHas('aviso');
        $this->assertSame('Outra Pessoa', $item->fresh()->coletado_por);
    }

    public function test_registrar_coleta_rejeita_item_nao_aprovado(): void
    {
        $item = $this->item(['status' => 'pendente', 'status_coleta' => 'aguardando']);

        $response = $this->actingAs($this->conferente())->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T10:30',
        ]);

        $response->assertSessionHas('aviso');
        $this->assertSame('aguardando', $item->fresh()->status_coleta);
    }

    public function test_entrada_nao_pode_registrar_coleta(): void
    {
        $entrada = User::factory()->create(['role' => 'entrada']);
        $item = $this->item(['status_coleta' => 'aguardando']);

        $response = $this->actingAs($entrada)->patch(route('conferencia.coleta', $item), [
            'status_coleta' => 'coletado',
            'data_coleta' => '2026-09-30T10:30',
        ]);

        $response->assertForbidden();
        $this->assertSame('aguardando', $item->fresh()->status_coleta);
    }
}
