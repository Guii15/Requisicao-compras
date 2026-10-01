<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained();
            $table->decimal('valor', 12, 2);
            $table->string('forma'); // a_vista | parcelado
            $table->date('data_pagamento');
            $table->string('obs', 500)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_compra');
    }
};
