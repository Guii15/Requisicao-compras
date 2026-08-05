<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'requester_name',
        'product_name',
        'product_code',
        'product_url',
        'supplier',
        'quantity',
        'reason',
        'urgency',
        'justification',
        'status',
        'aprovado_em',
        'admin_note',
        'valor',
        'tipo_entrega',
        'status_conferencia',
        'quantidade_recebida',
        'observacao_conferencia',
        'conferente_id',
        'conferencia_concluida_em',
        'vendedor_destino',
        'quantidade_entrada',
        'entrada_concluida_em',
    ];

    protected $casts = [
        'entrada_concluida_em' => 'datetime',
        'aprovado_em' => 'datetime',
        'conferencia_concluida_em' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conferente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conferente_id');
    }

    public function fotosConferencia(): HasMany
    {
        return $this->hasMany(ConferenciaFoto::class);
    }
}