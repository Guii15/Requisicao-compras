<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A coluna status_coleta nasceu com padrão "aguardando" para TODAS as linhas já
 * existentes. Quem já foi conferido, já teve entrada ou é histórico importado
 * obviamente já foi coletado; sem isso a aba Coleta encheria de itens antigos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('purchase_requests')
            ->where('status_coleta', 'aguardando')
            ->where(function ($q) {
                $q->whereNotNull('status_conferencia')
                    ->orWhereNotNull('entrada_concluida_em')
                    ->orWhere('tipo_registro', '!=', 'requisicao');
            })
            ->update(['status_coleta' => 'coletado']);
    }

    public function down(): void
    {
        // Sem volta: não dá para saber quais linhas eram "aguardando" antes.
    }
};
