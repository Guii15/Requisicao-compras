<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Um pagamento feito a um fornecedor por uma compra (à vista ou uma parcela). */
class PagamentoCompra extends Model
{
    protected $table = 'pagamentos_compra';

    protected $fillable = ['purchase_request_id', 'valor', 'forma', 'data_pagamento', 'obs', 'user_id'];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_pagamento' => 'date',
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
