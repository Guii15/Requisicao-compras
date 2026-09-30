<?php

namespace Tests\Feature;

use App\Models\Fornecedor;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FornecedorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_salvar_preenche_o_nome_normalizado(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Joyce Informática Ltda']);

        $this->assertSame('JOYCE INFORMATICA', $fornecedor->nome_normalizado);
    }

    public function test_banco_bloqueia_dois_fornecedores_com_o_mesmo_nome_normalizado(): void
    {
        Fornecedor::create(['nome' => 'Joyce Informática']);

        $this->expectException(QueryException::class);
        Fornecedor::create(['nome' => 'JOYCE INFORMATICA LTDA']);
    }

    public function test_compra_aponta_para_o_fornecedor_e_guarda_o_texto_original(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $fornecedor = Fornecedor::create(['nome' => 'Joyce Informática', 'criado_por' => $admin->id]);
        $item = PurchaseRequest::factory()->create([
            'fornecedor_id' => $fornecedor->id,
            'supplier' => 'Joyce Informática',
            'supplier_original' => 'joyce info ltda',
        ]);

        $this->assertTrue($item->fornecedor->is($fornecedor));
        $this->assertSame('joyce info ltda', $item->supplier_original);
        $this->assertSame(1, $fornecedor->compras()->count());
        $this->assertTrue($fornecedor->criador->is($admin));
    }

    public function test_apagar_fornecedor_nao_apaga_a_compra(): void
    {
        $fornecedor = Fornecedor::create(['nome' => 'Joyce']);
        $item = PurchaseRequest::factory()->create(['fornecedor_id' => $fornecedor->id]);

        $fornecedor->delete();

        $this->assertNull($item->fresh()->fornecedor_id);
    }
}
