<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagamentos_compra', function (Blueprint $table) {
            // Pagamento errado não é apagado: fica guardado como estornado, com quem, quando e por quê.
            $table->dateTime('estornado_em')->nullable()->after('user_id');
            $table->foreignId('estornado_por')->nullable()->after('estornado_em')->constrained('users')->nullOnDelete();
            $table->string('motivo_estorno', 500)->nullable()->after('estornado_por');
        });
    }

    public function down(): void
    {
        Schema::table('pagamentos_compra', function (Blueprint $table) {
            $table->dropConstrainedForeignId('estornado_por');
            $table->dropColumn(['estornado_em', 'motivo_estorno']);
        });
    }
};
