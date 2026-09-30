<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fornecedores', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('nome_normalizado')->unique();
            $table->foreignId('criado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->foreignId('fornecedor_id')->nullable()->after('supplier')->constrained('fornecedores')->nullOnDelete();
            $table->string('supplier_original')->nullable()->after('fornecedor_id');
        });

        Schema::create('fornecedor_mesclagens', function (Blueprint $table) {
            $table->id();
            $table->string('origem_nome');
            $table->string('origem_normalizado');
            $table->foreignId('destino_id')->nullable()->constrained('fornecedores')->nullOnDelete();
            $table->string('destino_nome');
            $table->unsignedInteger('compras_afetadas');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fornecedor_mesclagens');

        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fornecedor_id');
            $table->dropColumn('supplier_original');
        });

        Schema::dropIfExists('fornecedores');
    }
};
