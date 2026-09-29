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
            // Preco da caixa fechada, digitado manualmente pelo admin — as vezes o
            // fornecedor vende no unitario, as vezes so' fecha caixa. Os dois podem
            // ser preenchidos juntos e o valor total soma os dois.
            $table->decimal('preco_caixa', 10, 2)->nullable()->after('preco_unitario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('preco_caixa');
        });
    }
};
