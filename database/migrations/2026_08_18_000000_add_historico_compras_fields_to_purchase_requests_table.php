<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('tipo_registro')->default('requisicao')->after('grupo_id');
            $table->date('data_compra')->nullable()->after('tipo_registro');
            $table->string('origem_id')->nullable()->unique()->after('data_compra');
            $table->string('aba_origem')->nullable()->after('origem_id');
            $table->string('mes_origem')->nullable()->after('aba_origem');
            $table->json('dados_importacao')->nullable()->after('mes_origem');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'tipo_registro',
                'data_compra',
                'origem_id',
                'aba_origem',
                'mes_origem',
                'dados_importacao',
            ]);
        });
    }
};
