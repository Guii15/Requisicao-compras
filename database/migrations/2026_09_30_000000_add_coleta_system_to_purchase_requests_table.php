<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->enum('status_coleta', ['aguardando', 'coletado', 'atraso'])->default('aguardando')->after('atraso');
            $table->timestamp('data_coleta')->nullable()->after('status_coleta');
            $table->string('coletado_por')->nullable()->after('data_coleta');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn(['status_coleta', 'data_coleta', 'coletado_por']);
        });
    }
};
