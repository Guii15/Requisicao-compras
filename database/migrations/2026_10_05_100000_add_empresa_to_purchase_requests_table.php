<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Empresa (do grupo) que fez a compra. Opcional; as compras antigas ficam sem empresa.
            $table->string('empresa', 100)->nullable()->after('supplier');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('empresa');
        });
    }
};
