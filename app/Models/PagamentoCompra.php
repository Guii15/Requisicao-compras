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

    /** Formas que saem de um banco: nelas o banco é obrigatório. Dinheiro e as formas cadastradas não exigem. */
    public const MEIOS_COM_BANCO = ['pix', 'boleto', 'transferencia', 'cartao', 'cheque'];

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
        return self::meios()[$this->meio] ?? null;
    }

    /** Todas as formas: as fixas e depois as cadastradas pelo Financeiro (código => rótulo). */
    public static function meios(): array
    {
        // Lida uma vez por requisição (o histórico chama isto para cada pagamento); cadastrar uma forma limpa a memória.
        return self::MEIOS + once(fn () => MeioPagamento::query()->orderBy('nome')->get()
            ->mapWithKeys(fn (MeioPagamento $m) => [$m->codigo() => $m->nome])->all());
    }
}
