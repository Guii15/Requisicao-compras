<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Condição negociada com o fornecedor: a_vista | parcelado (em N parcelas), com o 1º vencimento.
            $table->string('condicao_pagamento', 20)->nullable()->after('data_compra');
            $table->unsignedTinyInteger('parcelas')->nullable()->after('condicao_pagamento');
            $table->date('primeiro_vencimento')->nullable()->after('parcelas');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['condicao_pagamento', 'parcelas', 'primeiro_vencimento']);
        });
    }
};
