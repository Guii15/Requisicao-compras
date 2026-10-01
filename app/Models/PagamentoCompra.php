<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Um pagamento feito a um fornecedor por uma compra (à vista ou uma parcela). */
class PagamentoCompra extends Model
{
    protected $table = 'pagamentos_compra';

    /** Como o pagamento foi feito. */
    public const MEIOS = [
        'pix' => 'PIX',
        'boleto' => 'Boleto',
        'transferencia' => 'Transferência (TED)',
        'cartao' => 'Cartão de crédito',
        'dinheiro' => 'Dinheiro',
        'cheque' => 'Cheque',
    ];

    protected $fillable = ['purchase_request_id', 'valor', 'forma', 'meio', 'banco', 'data_pagamento', 'obs', 'user_id'];

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

    public function meioRotulo(): ?string
    {
        return self::MEIOS[$this->meio] ?? null;
    }

    /** Bancos já usados antes, para sugerir no campo (o banco é digitado livremente). */
    public static function bancosUsados(): array
    {
        return static::query()->whereNotNull('banco')->distinct()->orderBy('banco')->pluck('banco')->all();
    }
}
