<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('vendedor_destino')->nullable()->after('quantidade_recebida');
            $table->integer('quantidade_entrada')->nullable()->after('vendedor_destino');
            $table->timestamp('entrada_concluida_em')->nullable()->after('quantidade_entrada');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['vendedor_destino', 'quantidade_entrada', 'entrada_concluida_em']);
        });
    }
};
