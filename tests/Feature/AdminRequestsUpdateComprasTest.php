<?php

namespace Tests\Feature;

use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRequestsUpdateComprasTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_modal_de_atualizar_so_mexe_em_status_e_observacao(): void
    {
        $item = PurchaseRequest::factory()->create(['supplier' => 'Fornecedor Original']);

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status'     => 'aprovado',
            'admin_note' => 'Aprovado, aguardando compra',
        ])->assertSessionDoesntHaveErrors();

        $item->refresh();
        $this->assertSame('aprovado', $item->status);
        $this->assertSame('Aprovado, aguardando compra', $item->admin_note);
        $this->assertSame('Fornecedor Original', $item->supplier, 'Fornecedor pertence a tela de Compras, o modal nao deve mexer nele.');
        $this->assertNull($item->preco_unitario);
        $this->assertNull($item->data_compra);
    }

    public function test_admin_anexa_orcamento_do_vendedor_quando_ele_esqueceu(): void
    {
        Storage::fake('local');
        $item = PurchaseRequest::factory()->create();

        $this->actingAs($this->admin())->patch(route('admin.requests.update', $item), [
            'status' => 'aprovado',
            'anexo'  => UploadedFile::fake()->create('orcamento.pdf', 200, 'application/pdf'),
        ]);

        $item->refresh();
        $this->assertSame('orcamento.pdf', $item->anexo_nome);
        Storage::disk('local')->assertExists($item->anexo_path);

        $this->actingAs($this->admin())
            ->get(route('requests.anexo', $item))
            ->assertOk()
            ->assertDownload('orcamento.pdf');
    }
}
