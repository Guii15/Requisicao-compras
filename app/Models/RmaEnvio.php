<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Item com defeito mandado ao RMA para devolver ao fornecedor. */
class RmaEnvio extends Model
{
    protected $table = 'rma_envios';

    /** Status na ordem do processo. Os de FINALIZADOS encerram o envio. */
    public const STATUS = [
        'Aguardando envio', 'Enviado ao fornecedor', 'Em análise',
        'Trocado', 'Consertado', 'Crédito', 'Recusado',
    ];

    public const FINALIZADOS = ['Trocado', 'Consertado', 'Crédito', 'Recusado'];

    protected $fillable = [
        'purchase_request_id', 'quantidade', 'defeito', 'foto_path', 'enviado_por_id',
        'status', 'numero_serie', 'protocolo_fornecedor', 'data_envio', 'codigo_rastreio',
        'observacao', 'atualizado_por_id',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'data_envio' => 'date',
    ];

    protected $attributes = [
        'status' => 'Aguardando envio',
    ];

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por_id')->withTrashed();
    }

    public function atualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atualizado_por_id')->withTrashed();
    }

    public function finalizado(): bool
    {
        return in_array($this->status, self::FINALIZADOS, true);
    }
}
