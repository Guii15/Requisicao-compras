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
        Schema::create('itens_mais_solicitados', function (Blueprint $table) {
            $table->id();
            $table->string('nome_canonico');
            $table->string('capacidade')->nullable();
            $table->unsignedInteger('total_pedidos');
            $table->json('variacoes_agrupadas');
            $table->timestamp('atualizado_em');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens_mais_solicitados');
    }
};
