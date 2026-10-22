<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Uma folha de cheque emitida num pagamento. Nunca é apagada: o estorno do pagamento a deixa cancelada. */
class Cheque extends Model
{
    public const SITUACOES = [
        'emitido' => 'Emitido',
        'cancelado' => 'Cancelado',
    ];

    protected $fillable = ['lote', 'banco', 'numero', 'valor', 'bom_para', 'situacao', 'user_id'];

    protected $casts = [
        'numero' => 'integer',
        'valor' => 'decimal:2',
        'bom_para' => 'date',
        'cancelado_em' => 'datetime',
    ];

    /** Os pagamentos que esta folha pagou (hoje um só; pagamento de várias compras de uma vez virá depois). */
    public function pagamentos(): HasMany
    {
        return $this->hasMany(PagamentoCompra::class, 'lote', 'lote');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Próxima folha de cada banco (a maior já usada + 1, contando as canceladas), para o número já vir preenchido.
     *
     * @return array<string, int> banco => próximo número
     */
    public static function proximasFolhas(): array
    {
        return static::query()->selectRaw('banco, MAX(numero) as ultima')->groupBy('banco')->pluck('ultima', 'banco')
            ->map(fn ($ultima) => (int) $ultima + 1)->all();
    }

    /** 101 -> "000101", como vem impresso na folha. */
    public function numeroFormatado(): string
    {
        return str_pad((string) $this->numero, 6, '0', STR_PAD_LEFT);
    }
}
