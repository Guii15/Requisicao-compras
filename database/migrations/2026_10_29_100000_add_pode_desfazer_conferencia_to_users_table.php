<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Permissão avulsa (marcada pelo super admin em Usuários): desfazer uma conferência feita errado.
            $table->boolean('pode_desfazer_conferencia')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pode_desfazer_conferencia');
        });
    }
};
