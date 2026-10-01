<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagamentos_compra', function (Blueprint $table) {
            // Como foi pago (pix, boleto...) e de qual banco saiu o dinheiro. Nulos nos pagamentos antigos.
            $table->string('meio', 20)->nullable()->after('forma');
            $table->string('banco', 100)->nullable()->after('meio');
        });
    }

    public function down(): void
    {
        Schema::table('pagamentos_compra', function (Blueprint $table) {
            $table->dropColumn(['meio', 'banco']);
        });
    }
};
