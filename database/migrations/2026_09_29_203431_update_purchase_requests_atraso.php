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
            $table->dropColumn(['coletado_por', 'data_coleta']);
            $table->boolean('atraso')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->string('coletado_por')->nullable();
            $table->date('data_coleta')->nullable();
            $table->dropColumn('atraso');
        });
    }
};
