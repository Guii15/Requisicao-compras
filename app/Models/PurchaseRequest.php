<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseRequest extends Model
{
    use HasFactory;

    /**
     * Registros importados do histórico de compras (tipo_registro != 'requisicao')
     * ficam de fora das telas do fluxo ativo por padrão. Use scopeHistorico() para
     * consultá-los explicitamente.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('apenasFluxoAtivo', function ($query) {
            $query->where('tipo_registro', 'requisicao');
        });
    }

    public function scopeHistorico($query)
    {
        return $query->withoutGlobalScope('apenasFluxoAtivo')
            ->whereIn('tipo_registro', ['compra_historica', 'cotacao_historica']);
    }

    protected $fillable = [
        'user_id',
        'grupo_id',
        'requester_name',
        'product_name',
        'product_code',
        'product_url',
        'anexo_path',
        'anexo_nome',
        'supplier',
        'fornecedor_id',
        'supplier_original',
        'quantity',
        'reason',
        'urgency',
        'justification',
        'status',
        'approved_at',
        'admin_note',
        'valor',
        'tipo_entrega',
        'status_conferencia',
        'quantidade_recebida',
        'observacao_conferencia',
        'obs',
        'conferente_id',
        'vendedor_destino',
        'quantidade_entrada',
        'entrada_concluida_em',
        'tipo_registro',
        'data_compra',
        'origem_id',
        'aba_origem',
        'mes_origem',
        'dados_importacao',
        'preco_unitario',
        'preco_caixa',
        'codigo_fornecedor',
        'atraso',
        'pedido_compra_path',
        'pedido_compra_nome',
        'status_coleta',
        'data_coleta',
        'coletado_por',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'entrada_concluida_em' => 'datetime',
        'data_compra' => 'date',
        'preco_unitario' => 'decimal:2',
        'preco_caixa' => 'decimal:2',
        'atraso' => 'boolean',
        'dados_importacao' => 'array',
        'data_coleta' => 'datetime',
    ];

    /**
     * O comprador ja registrou a compra quando tem pelo menos data e preco unitario.
     */
    public function temDadosDaCompra(): bool
    {
        return $this->data_compra !== null && $this->preco_unitario !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class);
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