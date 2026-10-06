<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Qual empresa nossa (Binário, Mamuth, Ninja...) fez a compra. Texto livre; o RMA usa na garantia.
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('empresa_compradora')->nullable()->after('codigo_fornecedor');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('empresa_compradora');
        });
    }
};
