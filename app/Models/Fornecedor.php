<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Fornecedor extends Model
{
    protected $table = 'fornecedores';

    protected $fillable = ['nome', 'nome_normalizado', 'criado_por'];

    /** Sufixos de razão social que não diferenciam um fornecedor de outro (só removidos do fim). */
    private const SUFIXOS = ['LTDA', 'ME', 'EIRELI', 'EPP', 'SA'];

    protected static function booted(): void
    {
        static::saving(function (Fornecedor $fornecedor) {
            $fornecedor->nome = trim(preg_replace('/\s+/u', ' ', $fornecedor->nome));
            $fornecedor->nome_normalizado = self::normalizar($fornecedor->nome);
        });
    }

    /**
     * Chave de comparação: "Joyce Informática Ltda." e " JOYCE  INFORMATICA " viram "JOYCE INFORMATICA".
     */
    public static function normalizar(?string $nome): string
    {
        $texto = mb_strtoupper(Str::ascii(trim((string) $nome)));
        $texto = trim(preg_replace('/[^A-Z0-9]+/', ' ', $texto));

        if ($texto === '') {
            return '';
        }

        $palavras = explode(' ', $texto);

        while (count($palavras) > 1) {
            $ultima = end($palavras);

            if (in_array($ultima, self::SUFIXOS, true)) {
                array_pop($palavras);
                continue;
            }

            // "S/A" e "S.A." viram "S A" depois de tirar a pontuação.
            if ($ultima === 'A' && count($palavras) > 2 && $palavras[count($palavras) - 2] === 'S') {
                array_splice($palavras, -2);
                continue;
            }

            break;
        }

        return implode(' ', $palavras);
    }

    public function compras(): HasMany
    {
        return $this->hasMany(PurchaseRequest::class)->withoutGlobalScopes();
    }

    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
