<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Bancos de onde sai o pagamento, escolhidos numa lista. O pagamento continua guardando o nome em texto.
        Schema::create('bancos', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->timestamps();
        });

        // Os bancos já digitados nos pagamentos entram na lista ("Itaú" e " itau " viram um só, na grafia mais usada).
        $grafias = DB::table('pagamentos_compra')->whereNotNull('banco')->pluck('banco')
            ->map(fn ($b) => trim(preg_replace('/\s+/u', ' ', (string) $b)))
            ->filter()
            ->groupBy(fn ($b) => mb_strtolower(Str::ascii($b)));

        foreach ($grafias as $grupo) {
            $contagem = $grupo->countBy()->all();
            uksort($contagem, fn ($x, $y) => [$contagem[$y], $x] <=> [$contagem[$x], $y]);
            DB::table('bancos')->insert(['nome' => array_key_first($contagem), 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bancos');
    }
};
