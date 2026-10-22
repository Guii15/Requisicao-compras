<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagamentos_compra', function (Blueprint $table) {
            // Liga o pagamento às folhas de cheque dele (e, no futuro, junta os pagamentos feitos de uma vez).
            $table->string('lote', 36)->nullable()->after('id')->index();
        });

        Schema::create('cheques', function (Blueprint $table) {
            $table->id();
            $table->string('lote', 36)->index();
            $table->string('banco', 100);
            $table->unsignedBigInteger('numero');
            $table->decimal('valor', 12, 2);
            $table->date('bom_para');
            // emitido | cancelado (o pagamento foi estornado; a folha continua usada)
            $table->string('situacao', 20)->default('emitido');
            $table->dateTime('cancelado_em')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            // A folha física só existe uma vez: o mesmo número não se repete no mesmo banco.
            $table->unique(['banco', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');

        Schema::table('pagamentos_compra', function (Blueprint $table) {
            $table->dropIndex(['lote']);
            $table->dropColumn('lote');
        });
    }
};
