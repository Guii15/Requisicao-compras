<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('status');
        });

        // Backfill: para quem ja' esta' aprovado e nunca teve status_conferencia
        // mexido, updated_at ainda e' a data da aprovacao (nada tocou a linha
        // depois). Pra quem ja' foi conferido, e' so' uma aproximacao — nao
        // temos a data real de aprovacao de antes desta coluna existir.
        DB::table('purchase_requests')
            ->where('status', 'aprovado')
            ->whereNull('approved_at')
            ->update(['approved_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
