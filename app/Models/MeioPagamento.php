<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Once;

/** Forma de pagamento cadastrada pelo Financeiro além das fixas de PagamentoCompra::MEIOS (ex.: Permuta). */
class MeioPagamento extends Model
{
    protected $table = 'meios_pagamento';

    protected $fillable = ['nome'];

    protected static function booted(): void
    {
        static::saving(function (MeioPagamento $meio) {
            $meio->nome = trim(preg_replace('/\s+/u', ' ', $meio->nome));
        });

        // A lista de formas fica em memória durante a requisição (PagamentoCompra::meios); a nova precisa aparecer.
        static::saved(fn () => Once::flush());
    }

    /** Código guardado no pagamento. */
    public function codigo(): string
    {
        return 'm' . $this->id;
    }

    /**
     * Devolve o código da forma com esse nome (fixa ou cadastrada, em qualquer grafia) ou cadastra uma nova.
     * "pix" vira a forma fixa PIX; "PERMUTA" usa a "Permuta" já cadastrada.
     */
    public static function cadastrar(string $nome): string
    {
        $chave = Banco::chave($nome);

        foreach (PagamentoCompra::MEIOS as $codigo => $rotulo) {
            if ($chave === Banco::chave($rotulo) || $chave === $codigo) {
                return $codigo;
            }
        }

        $existente = static::query()->get()->first(fn (MeioPagamento $m) => Banco::chave($m->nome) === $chave);

        return ($existente ?? static::create(['nome' => $nome]))->codigo();
    }
}
