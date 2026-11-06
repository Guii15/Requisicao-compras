<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Item com defeito mandado ao setor RMA para devolver ao fornecedor (um item pode ter mais de um envio).
        Schema::create('rma_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained();
            $table->unsignedInteger('quantidade');
            $table->text('defeito');
            $table->string('foto_path')->nullable();
            $table->foreignId('enviado_por_id')->nullable()->constrained('users')->nullOnDelete();
            // Preenchido pelo RMA
            $table->string('status', 40)->default('Aguardando envio');
            $table->string('numero_serie', 120)->nullable();
            $table->string('protocolo_fornecedor', 120)->nullable();
            $table->date('data_envio')->nullable();
            $table->string('codigo_rastreio', 120)->nullable();
            $table->text('observacao')->nullable();
            $table->foreignId('atualizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rma_envios');
    }
};
