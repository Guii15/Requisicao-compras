<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Custo a mais de uma compra além do pago ao fornecedor (impostos, frete...). Não entra na dívida com o fornecedor. */
class AcrescimoCompra extends Model
{
    protected $table = 'acrescimos_compra';

    protected $fillable = ['purchase_request_id', 'valor', 'motivo', 'user_id'];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
