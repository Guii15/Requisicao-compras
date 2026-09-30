<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FornecedorMesclagem extends Model
{
    protected $table = 'fornecedor_mesclagens';

    protected $fillable = ['origem_nome', 'origem_normalizado', 'destino_id', 'destino_nome', 'compras_afetadas', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
