<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A coluna "Atraso" das listas mostra se a coleta foi marcada como atraso
 * (status_coleta), e não o campo antigo `atraso` que nada mais grava.
 */
class ColunaAtrasoTest extends TestCase
{
    use RefreshDatabase;

    private const SIM = '>Sim</span>';
    private const NAO = '>Não</span>';

    public static function telas(): array
    {
        return ['vendedor' => ['vendedor'], 'admin' => ['admin'], 'entrada' => ['entrada']];
    }

    private function abrir(string $tela, string $statusColeta, bool $atrasoAntigo = false)
    {
        $vendedor = User::factory()->create(['role' => null, 'is_admin' => false]);
        $item = PurchaseRequest::factory()->create([
            'user_id' => $vendedor->id,
            'status' => 'aprovado',
            'status_conferencia' => 'conferido_ok',
            'entrada_concluida_em' => null,
            'status_coleta' => $statusColeta,
            'atraso' => $atrasoAntigo,
        ]);

        if ($tela === 'admin') {
            // O painel do admin só lista requisições que ainda têm algum item pendente.
            PurchaseRequest::factory()->create([
                'user_id' => $vendedor->id,
                'grupo_id' => $item->grupo_id,
                'status' => 'pendente',
                'status_coleta' => 'aguardando',
            ]);
        }

        return match ($tela) {
            'vendedor' => $this->actingAs($vendedor)->get(route('requests.index')),
            'admin' => $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.index', ['status' => 'aprovado'])),
            'entrada' => $this->actingAs(User::factory()->create(['role' => 'entrada']))->get(route('entrada.index')),
        };
    }

    #[DataProvider('telas')]
    public function test_coleta_em_atraso_mostra_sim(string $tela): void
    {
        $this->abrir($tela, 'atraso')->assertOk()->assertSee(self::SIM, false);
    }

    #[DataProvider('telas')]
    public function test_coleta_aguardando_ou_coletada_mostra_nao_mesmo_com_campo_antigo_marcado(string $tela): void
    {
        $this->abrir($tela, 'coletado', true)
            ->assertOk()
            ->assertSee(self::NAO, false)
            ->assertDontSee(self::SIM, false);
    }
}
