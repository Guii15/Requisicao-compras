<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dados da compra que o comprador (Admin) registra DEPOIS de aprovar a requisicao.
     * data_compra, supplier e valor (preco total) ja existem; aqui entram so' os que faltavam.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->decimal('preco_unitario', 12, 2)->nullable()->after('valor');
            $table->string('codigo_fornecedor')->nullable()->after('supplier');
            $table->date('data_coleta')->nullable()->after('data_compra');
            $table->string('pedido_compra_path')->nullable()->after('data_coleta');
            $table->string('pedido_compra_nome')->nullable()->after('pedido_compra_path');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['preco_unitario', 'codigo_fornecedor', 'data_coleta', 'pedido_compra_path', 'pedido_compra_nome']);
        });
    }
};
