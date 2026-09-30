<?php

namespace App\Services;

use App\Models\Fornecedor;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Decide a qual fornecedor um texto digitado se refere, sem deixar nascer duplicado.
 */
class FornecedorResolver
{
    public function __construct(private readonly UnificadorFornecedores $unificador)
    {
    }

    /**
     * Admin: usa o escolhido no autocomplete; senão reaproveita pelo nome normalizado;
     * se só existir um PARECIDO, pergunta "Você quis dizer...?" (a menos que confirme);
     * senão cria. Texto vazio = sem fornecedor.
     *
     * @throws ValidationException
     */
    public function paraAdmin(?string $nome, mixed $fornecedorId, bool $confirmarNovo, User $admin, string $campo = 'supplier'): ?Fornecedor
    {
        $nome = trim(preg_replace('/\s+/u', ' ', (string) $nome));

        if ($fornecedorId && ($escolhido = Fornecedor::find($fornecedorId))) {
            return $escolhido;
        }

        if ($nome === '' || Fornecedor::normalizar($nome) === '') {
            return null;
        }

        if ($existente = $this->exato($nome)) {
            return $existente;
        }

        $parecidos = $this->parecidos($nome);
        if ($parecidos->isNotEmpty() && !$confirmarNovo) {
            session()->flash('fornecedor_sugestoes', $parecidos->map->only(['id', 'nome'])->all());

            throw ValidationException::withMessages([
                $campo => 'Você quis dizer ' . $parecidos->first()->nome . '?',
            ]);
        }

        return Fornecedor::create([
            'nome' => mb_convert_case(mb_strtolower($nome), MB_CASE_TITLE, 'UTF-8'),
            'criado_por' => $admin->id,
        ]);
    }

    /** Vendedor: só liga quando bate exato depois de normalizar. Nunca cria. */
    public function paraVendedor(?string $nome): ?Fornecedor
    {
        return trim((string) $nome) === '' ? null : $this->exato($nome);
    }

    public function exato(string $nome): ?Fornecedor
    {
        $chave = Fornecedor::normalizar($nome);

        return $chave === '' ? null : Fornecedor::where('nome_normalizado', $chave)->first();
    }

    /** @return Collection<int, Fornecedor> */
    public function parecidos(string $nome, int $limite = 5): Collection
    {
        $chave = Fornecedor::normalizar($nome);
        if ($chave === '') {
            return collect();
        }

        return Fornecedor::orderBy('nome')->get()
            ->filter(fn (Fornecedor $f) => $f->nome_normalizado !== $chave
                && $this->unificador->motivoParecido($chave, $f->nome_normalizado) !== null)
            ->take($limite)
            ->values();
    }

    /** @return Collection<int, Fornecedor> */
    public function buscar(string $termo, int $limite = 10): Collection
    {
        $chave = Fornecedor::normalizar($termo);
        if ($chave === '') {
            return collect();
        }

        return Fornecedor::where('nome_normalizado', 'like', '%' . $chave . '%')
            ->orderByRaw('CASE WHEN nome_normalizado LIKE ? THEN 0 ELSE 1 END', [$chave . '%'])
            ->orderBy('nome')
            ->limit($limite)
            ->get();
    }
}
