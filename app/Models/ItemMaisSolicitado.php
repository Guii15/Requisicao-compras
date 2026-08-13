<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemMaisSolicitado extends Model
{
    protected $table = 'itens_mais_solicitados';

    protected $fillable = [
        'nome_canonico',
        'capacidade',
        'total_pedidos',
        'variacoes_agrupadas',
        'atualizado_em',
    ];

    protected $casts = [
        'variacoes_agrupadas' => 'array',
        'atualizado_em'       => 'datetime',
    ];
}
