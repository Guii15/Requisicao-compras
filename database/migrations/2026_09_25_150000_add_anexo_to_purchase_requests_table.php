<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anexo que o vendedor pode subir junto com a requisicao (orcamento, print,
     * cotacao etc), complementando o campo product_url que ja existia.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('anexo_path')->nullable()->after('product_url');
            $table->string('anexo_nome')->nullable()->after('anexo_path');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['anexo_path', 'anexo_nome']);
        });
    }
};
