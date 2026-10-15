<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Formas de pagamento cadastradas pelo Financeiro além das fixas (PIX, boleto...). O pagamento guarda "m{id}".
        Schema::create('meios_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 60)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meios_pagamento');
    }
};
