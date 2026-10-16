<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Banco de onde sai um pagamento do Financeiro (a lista do campo "Banco"). */
class Banco extends Model
{
    protected $fillable = ['nome'];

    protected static function booted(): void
    {
        static::saving(function (Banco $banco) {
            $banco->nome = trim(preg_replace('/\s+/u', ' ', $banco->nome));
        });
    }

    /** "Itaú", "ITAU" e " itau " são o mesmo banco. */
    public static function chave(?string $nome): string
    {
        return mb_strtolower(Str::ascii(trim(preg_replace('/\s+/u', ' ', (string) $nome))));
    }

    /** @return list<string> nomes em ordem alfabética */
    public static function nomes(): array
    {
        return static::query()->pluck('nome')->sortBy(fn ($n) => static::chave($n))->values()->all();
    }

    /** Devolve o banco já cadastrado com esse nome (em qualquer grafia) ou cadastra um novo. */
    public static function cadastrar(string $nome): self
    {
        $chave = static::chave($nome);

        return static::query()->get()->first(fn (Banco $b) => static::chave($b->nome) === $chave)
            ?? static::create(['nome' => $nome]);
    }
}
