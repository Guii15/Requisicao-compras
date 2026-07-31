<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('tipo_entrega')->default('estoque')->after('valor');
            $table->string('status_conferencia')->nullable()->after('tipo_entrega');
            $table->integer('quantidade_recebida')->nullable()->after('status_conferencia');
            $table->text('observacao_conferencia')->nullable()->after('quantidade_recebida');
            $table->foreignId('conferente_id')->nullable()->after('observacao_conferencia')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropForeign(['conferente_id']);
            $table->dropColumn([
                'tipo_entrega',
                'status_conferencia',
                'quantidade_recebida',
                'observacao_conferencia',
                'conferente_id',
            ]);
        });
    }
};
