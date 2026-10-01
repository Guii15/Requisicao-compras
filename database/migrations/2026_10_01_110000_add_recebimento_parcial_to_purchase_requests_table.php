<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Total pedido originalmente quando a compra chegou em partes (ex: 100); null = não foi parcial.
            $table->unsignedInteger('quantidade_original')->nullable()->after('quantity');
            // Item que representa o restante de outro (a parte que ainda não chegou).
            $table->foreignId('restante_de_id')->nullable()->after('quantidade_original')
                ->constrained('purchase_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('restante_de_id');
            $table->dropColumn('quantidade_original');
        });
    }
};
