<?php

namespace App\Http\Controllers;

use App\Models\AcrescimoCompra;
use App\Models\Banco;
use App\Models\Cheque;
use App\Models\MeioPagamento;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Services\DesdobramentoCheque;
use App\Services\FornecedorResolver;
use App\Services\PainelFinanceiro;
use App\Services\RelatorioFinanceiro;
use App\Services\SaldoFornecedores;
use App\Services\UnificadorFornecedores;
use App\Support\Dinheiro;
use App\Support\PlanilhaXlsx;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Contas a pagar por fornecedor: painel, compras aguardando/pagas, saldo por fornecedor e baixa por pagamento. */
class FinanceiroController extends Controller
{
    private const POR_PAGINA = 25;

    /** Atalhos da aba Aguardando (contas a pagar), pelo próximo vencimento. */
    private const ATALHOS_VENCIMENTO = [
        'vencidas' => 'Vencidas',
        'hoje' => 'Vence hoje',
        'semana' => 'Próximos 7 dias',
        'mes' => 'Até o fim do mês',
        'sem' => 'Sem vencimento',
    ];

    public function index(Request $request, PainelFinanceiro $painel)
    {
        $d = $painel->dados(null, $request->query('empresa'));

        return view('financeiro.index', ['d' => $d, 'empresas' => collect($d['empresas']), 'empresaSel' => $d['empresa']]);
    }

    public function aguardando(Request $request, SaldoFornecedores $saldos)
    {
        // Contas a pagar: primeiro pelo vencimento (a que vence antes em cima); as sem vencimento vão para o fim, das mais novas para as mais antigas.
        $semData = $saldos->somenteEmpresa($saldos->linhas(), $request->query('empresa'))->where('aberto', '>', 0)
            ->sortByDesc(fn ($l) => $l['compra']->data_compra->format('Ymd') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT));
        $linhas = $semData->whereNotNull('proximo_vencimento')
            ->sortBy(fn ($l) => $l['proximo_vencimento']->format('Ymd') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->concat($semData->whereNull('proximo_vencimento'))
            ->values();

        $hoje = CarbonImmutable::now('America/Sao_Paulo')->startOfDay();
        $vence = fn (array $l, string $quando) => match ($quando) {
            'vencidas' => $l['vencida'],
            'hoje' => $l['proximo_vencimento']?->isSameDay($hoje) ?? false,
            'semana' => $l['proximo_vencimento'] !== null && $l['proximo_vencimento'] >= $hoje && $l['proximo_vencimento'] <= $hoje->addDays(6),
            'mes' => $l['proximo_vencimento'] !== null && $l['proximo_vencimento'] >= $hoje && $l['proximo_vencimento'] <= $hoje->endOfMonth(),
            'sem' => $l['proximo_vencimento'] === null,
            default => true,
        };

        // Atalhos com quantas contas e quanto falta em cada um (antes de filtrar).
        $atalhos = collect(self::ATALHOS_VENCIMENTO)->map(function (string $rotulo, string $codigo) use ($linhas, $vence) {
            $grupo = $linhas->filter(fn ($l) => $vence($l, $codigo));

            return ['rotulo' => $rotulo, 'quantidade' => $grupo->count(), 'valor' => round($grupo->sum('aberto'), 2)];
        })->all();

        $quando = array_key_exists((string) $request->query('vence'), self::ATALHOS_VENCIMENTO) ? (string) $request->query('vence') : null;
        if ($quando) {
            $linhas = $linhas->filter(fn ($l) => $vence($l, $quando))->values();
        }

        // Período de vencimento escolhido (de/até).
        $de = $this->dataOuNula($request->query('de'));
        $ate = $this->dataOuNula($request->query('ate'));
        if ($de || $ate) {
            $linhas = $linhas->filter(fn ($l) => $l['proximo_vencimento'] !== null
                && (!$de || $l['proximo_vencimento']->format('Y-m-d') >= $de)
                && (!$ate || $l['proximo_vencimento']->format('Y-m-d') <= $ate))->values();
        }

        return $this->lista($request, $linhas, 'aguardando', ['atalhos' => $atalhos, 'vence' => $quando, 'de' => $de, 'ate' => $ate]);
    }

    private function dataOuNula(mixed $texto): ?string
    {
        return is_string($texto) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $texto) ? $texto : null;
    }

    public function pagos(Request $request, SaldoFornecedores $saldos)
    {
        $linhas = $saldos->somenteEmpresa($saldos->linhas(), $request->query('empresa'))
            ->where('aberto', '<=', 0)
            ->sortByDesc(fn ($l) => ($l['ultimo_pagamento']?->format('Ymd') ?? '0') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        return $this->lista($request, $linhas, 'pagos');
    }

    public function fornecedores(Request $request, SaldoFornecedores $saldos)
    {
        $todas = $saldos->linhas();
        $empresas = $saldos->empresas($todas);
        $empresaSel = $this->empresaEscolhida($request);
        $fornecedores = $saldos->resumo($saldos->somenteEmpresa($todas, $empresaSel));

        $totais = [
            'comprado' => round($fornecedores->sum('comprado'), 2),
            'pago' => round($fornecedores->sum('pago'), 2),
            'saldo' => round($fornecedores->sum('saldo'), 2),
        ];

        $q = trim((string) $request->query('q', ''));
        if ($q !== '') {
            // "57" ou "#57" também acha o fornecedor da requisição 57.
            $daRequisicao = $todas->first(fn ($l) => (string) $l['compra']->id === ltrim($q, '#'))['chave'] ?? null;
            $fornecedores = $fornecedores
                ->filter(fn ($f) => $this->contem($f['nome'], $q) || str_contains($f['chave'], $saldos->chave($q)) || $f['chave'] === $daRequisicao)
                ->values();
        }

        return view('financeiro.fornecedores', compact('fornecedores', 'totais', 'q', 'empresas', 'empresaSel'));
    }

    public function fornecedor(Request $request, string $chave, SaldoFornecedores $saldos)
    {
        $todas = $saldos->linhas();
        $empresas = $saldos->empresas($todas);
        $empresaSel = $this->empresaEscolhida($request);
        $compras = $saldos->comprasDoFornecedor($chave, $empresaSel, $todas);

        if ($compras->isEmpty()) {
            // O fornecedor existe, mas não comprou nessa empresa: volta para a lista em vez de dar erro.
            abort_if($empresaSel === null || $saldos->comprasDoFornecedor($chave, null, $todas)->isEmpty(), 404);

            return redirect()->route('financeiro.fornecedores', ['empresa' => $empresaSel])
                ->with('aviso', 'Este fornecedor não tem compras nessa empresa.');
        }

        $nome = $compras->first()['fornecedor'];
        $totais = [
            'comprado' => round($compras->sum('custo'), 2),
            'pago' => round($compras->sum('pago'), 2),
            'saldo' => round($compras->sum('aberto'), 2),
            'acrescimo' => round($compras->sum('acrescimo'), 2),
        ];

        // Para mostrar que um pagamento foi dividido entre várias compras ("parte de um pagamento de R$ X").
        EloquentCollection::make($compras->pluck('compra')->all())->load(['pagamentos.doLote', 'pagamentosEstornados.doLote']);

        // Ordem em que "Pagar fornecedor" quita as compras (a janela mostra a prévia com ela).
        $ordemPagamento = collect($saldos->distribuir($compras, DesdobramentoCheque::centavos($totais['saldo'])))
            ->map(fn (array $p) => [
                'id' => $p['linha']['compra']->id,
                'produto' => $p['linha']['compra']->product_name,
                'vence' => $p['linha']['proximo_vencimento']?->format('d/m/Y'),
                'centavos' => $p['centavos'],
            ])->all();

        // Para "Juntar com outro fornecedor": os de nome parecido aparecem primeiro.
        $unificador = app(UnificadorFornecedores::class);
        $outros = $saldos->resumo($todas)->where('chave', '!=', $chave)
            ->map(fn ($f) => $f + ['parecido' => $unificador->motivoParecido($chave, $f['chave']) !== null])
            ->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('financeiro.fornecedor', compact('chave', 'nome', 'compras', 'totais', 'empresas', 'empresaSel', 'ordemPagamento', 'outros'));
    }

    /**
     * Junta este fornecedor com outro que é o mesmo ("Joyce" + "Joyce Informática"). A pessoa escolhe
     * qual nome fica; as compras e pagamentos dos dois passam a ser de um fornecedor só.
     */
    public function juntarFornecedor(Request $request, string $chave, SaldoFornecedores $saldos, UnificadorFornecedores $unificador)
    {
        $dados = $request->validate([
            'outro' => ['required', 'string'],
            'fica' => ['required', 'in:este,outro'],
        ], ['outro.required' => 'Escolha o fornecedor para juntar.']);

        $nomes = $saldos->resumo()->pluck('nome', 'chave');
        if (!isset($nomes[$chave], $nomes[$dados['outro']]) || $dados['outro'] === $chave) {
            throw ValidationException::withMessages(['outro' => 'Escolha outro fornecedor da lista para juntar.']);
        }

        [$origem, $destino] = $dados['fica'] === 'este' ? [$dados['outro'], $chave] : [$chave, $dados['outro']];
        $movidas = $unificador->juntar($origem, $nomes[$origem], $nomes[$destino], auth()->id());

        return redirect()->route('financeiro.fornecedor', $saldos->chave($nomes[$destino]))->with('success',
            "\"{$nomes[$origem]}\" foi juntado a \"{$nomes[$destino]}\": {$movidas} " . ($movidas === 1 ? 'compra passou' : 'compras passaram')
            . " para esse nome. Quem digitar \"{$nomes[$origem]}\" daqui para frente cai aqui.");
    }

    public function pagar(Request $request, PurchaseRequest $conta, SaldoFornecedores $saldos, DesdobramentoCheque $desdobramento)
    {
        // Volta para a tela de onde o pagamento foi feito (lista ou fornecedor).
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($conta->supplier))));

        if (!$saldos->conta($conta)) {
            return redirect()->route('financeiro.index')->with('aviso', 'Esta compra não entra no financeiro — nada foi alterado.');
        }

        $situacao = $saldos->situacao($conta->load('pagamentos'));

        if ($situacao['aberto'] <= 0) {
            return $volta->with('aviso', 'Esta compra já está paga — nada foi alterado.');
        }

        $dados = $this->validarPagamento($request, true);

        // À vista quita o que falta; parcelado abate só o que foi informado.
        $valor = $dados['forma'] === 'a_vista' ? $situacao['aberto'] : round((float) $dados['valor'], 2);

        if ($valor > $situacao['aberto'] + 0.004) {
            throw ValidationException::withMessages([
                'valor' => 'O valor é maior do que falta pagar nesta compra (' . Dinheiro::brl($situacao['aberto']) . ').',
            ]);
        }

        $folhas = $this->gravarPagamento($dados, $valor, [
            ['compra' => $conta, 'valor' => $valor, 'forma' => $dados['forma']],
        ], $desdobramento);

        $mensagem = 'Pagamento de ' . Dinheiro::brl($valor) . ' registrado.' . $this->textoFolhas($folhas);

        return $volta->with('success', $mensagem);
    }

    /** Nomes de fornecedor já usados, para a lista do "Lançar conta". */
    public function nomesFornecedores()
    {
        return response()->json(DB::table('purchase_requests')->whereNotNull('supplier')->where('supplier', '!=', '')
            ->distinct()->orderBy('supplier')->pluck('supplier'));
    }

    /**
     * Conta que não veio de uma requisição (saldo antigo, conta do Bling, fornecedor sem compra no sistema).
     * Entra no saldo como uma compra; "já paga" grava junto o pagamento do valor todo.
     */
    public function lancarConta(Request $request, SaldoFornecedores $saldos, FornecedorResolver $fornecedores, DesdobramentoCheque $desdobramento)
    {
        if ($request->filled('valor')) {
            $request->merge(['valor' => Dinheiro::decimal($request->input('valor'))]);
        }

        $conta = $request->validate([
            'fornecedor' => 'required|string|max:255',
            'descricao' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01|max:999999999',
            'data_conta' => 'required|date',
            'condicao' => 'required|in:a_vista,parcelado',
            'parcelas' => 'nullable|required_if:condicao,parcelado|integer|min:2|max:120',
            'vencimento' => 'nullable|date',
            'empresa_conta' => 'nullable|string|max:100',
            'obs_conta' => 'nullable|string|max:500',
            'ja_paga' => 'nullable|boolean',
        ], [
            'fornecedor.required' => 'Informe o fornecedor.',
            'descricao.required' => 'Descreva a conta (ex.: saldo anterior, NF 1234).',
            'valor.required' => 'Informe o valor da conta.',
            'valor.numeric' => 'O valor precisa ser um número.',
            'valor.min' => 'O valor precisa ser maior que zero.',
            'data_conta.required' => 'Informe a data da conta.',
            'data_conta.date' => 'Data da conta inválida.',
            'condicao.required' => 'Informe se é à vista ou parcelado.',
            'parcelas.required_if' => 'Informe em quantas parcelas.',
            'parcelas.min' => 'Parcelado precisa ter pelo menos 2 parcelas.',
            'vencimento.date' => 'Data de vencimento inválida.',
        ]);

        $pagamento = $request->boolean('ja_paga') ? $this->validarPagamento($request, false) : null;

        $digitado = trim($conta['fornecedor']);
        $fornecedor = $fornecedores->exato($digitado);
        $valor = round((float) $conta['valor'], 2);

        $compra = DB::transaction(function () use ($conta, $digitado, $fornecedor, $valor, $pagamento, $desdobramento) {
            $compra = PurchaseRequest::create([
                'tipo_registro' => PurchaseRequest::CONTA_AVULSA,
                'user_id' => auth()->id(),
                'requester_name' => auth()->user()->name,
                'product_name' => trim($conta['descricao']),
                'supplier' => $fornecedor?->nome ?? $digitado,
                'fornecedor_id' => $fornecedor?->id,
                'supplier_original' => $digitado,
                'empresa' => trim((string) ($conta['empresa_conta'] ?? '')) ?: null,
                'quantity' => 1,
                'preco_unitario' => $valor,
                'valor' => $valor,
                'data_compra' => $conta['data_conta'],
                'condicao_pagamento' => $conta['condicao'],
                'parcelas' => $conta['condicao'] === 'parcelado' ? (int) $conta['parcelas'] : null,
                'primeiro_vencimento' => $conta['vencimento'] ?? null,
                'obs' => $conta['obs_conta'] ?? null,
                'status' => 'aprovado',
                'approved_at' => now(),
                'urgency' => 'baixa',
                'reason' => 'Conta lançada no Financeiro',
                'justification' => 'Conta lançada no Financeiro',
            ]);

            if ($pagamento) {
                $this->gravarPagamento($pagamento, $valor, [['compra' => $compra, 'valor' => $valor, 'forma' => 'a_vista']], $desdobramento);
            }

            return $compra;
        });

        return redirect()->route('financeiro.fornecedor', $saldos->chave($compra->supplier))->with('success',
            'Conta de ' . Dinheiro::brl($valor) . ' lançada' . ($pagamento ? ' já paga' : '') . '.');
    }

    /**
     * Pagamento ao fornecedor de uma vez (valor total ou parcial): o valor é distribuído entre as compras em aberto,
     * do vencimento mais antigo para o mais novo. A última compra alcançada pode ficar parcial.
     */
    public function pagarFornecedor(Request $request, string $chave, SaldoFornecedores $saldos, DesdobramentoCheque $desdobramento)
    {
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $chave)));
        $empresa = $this->empresaEscolhida($request);
        $abertas = $saldos->comprasDoFornecedor($chave, $empresa)->where('aberto', '>', 0)->values();

        if ($abertas->isEmpty()) {
            return $volta->with('aviso', 'Este fornecedor não tem nada em aberto — nada foi alterado.');
        }

        $dados = $this->validarPagamento($request, false);
        $saldo = round($abertas->sum('aberto'), 2);
        $valor = round((float) $dados['valor'], 2);

        if ($valor > $saldo + 0.004) {
            throw ValidationException::withMessages([
                'valor' => 'O valor é maior do que o saldo devedor do fornecedor (' . Dinheiro::brl($saldo) . ').',
            ]);
        }

        $partes = collect($saldos->distribuir($abertas, DesdobramentoCheque::centavos($valor)))
            ->map(fn (array $p) => [
                'compra' => $p['linha']['compra'],
                'valor' => $p['centavos'] / 100,
                // Quitou a compra inteira = à vista; só uma parte = parcelado (como no pagamento por compra).
                'forma' => $p['quita'] ? 'a_vista' : 'parcelado',
            ])->all();

        $folhas = $this->gravarPagamento($dados, $valor, $partes, $desdobramento);

        $mensagem = 'Pagamento de ' . Dinheiro::brl($valor) . ' ao fornecedor registrado em '
            . count($partes) . (count($partes) === 1 ? ' compra.' : ' compras.') . $this->textoFolhas($folhas);

        return $volta->with('success', $mensagem);
    }

    /** Valida os campos comuns do pagamento (forma, banco, data, cheque). Com $porCompra, "forma" diz se é à vista ou parcelado. */
    private function validarPagamento(Request $request, bool $porCompra): array
    {
        if ($request->filled('valor')) {
            $request->merge(['valor' => Dinheiro::decimal($request->input('valor'))]);
        }

        // A folha vem impressa com zeros na frente ("000101"): guarda só o número.
        if (preg_match('/^\s*0*(\d+)\s*$/', (string) $request->input('cheque_primeira_folha'), $m)) {
            $request->merge(['cheque_primeira_folha' => $m[1]]);
        }

        return $request->validate([
            'forma' => $porCompra ? 'required|in:a_vista,parcelado' : 'nullable',
            'valor' => $porCompra ? 'required_if:forma,parcelado|nullable|numeric|min:0.01' : 'required|numeric|min:0.01',
            'meio' => ['required', Rule::in([...array_keys(PagamentoCompra::meios()), '__novo'])],
            // "Cadastrar nova forma" na lista: o nome vem neste campo.
            'meio_novo' => ['nullable', 'string', 'max:60', Rule::requiredIf(fn () => $request->input('meio') === '__novo')],
            'banco' => [Rule::requiredIf(fn () => in_array($request->input('meio'), PagamentoCompra::MEIOS_COM_BANCO, true)), 'nullable', 'string', 'max:100'],
            // "Cadastrar novo banco" na lista: o nome vem neste campo.
            'banco_novo' => ['nullable', 'string', 'max:100', Rule::requiredIf(fn () => $request->input('meio') !== 'dinheiro' && $request->input('banco') === '__novo')],
            'data_pagamento' => 'required|date',
            'obs' => 'nullable|string|max:500',
            // Cheque: número da primeira folha (as outras seguem em sequência) e a data "bom para".
            'cheque_primeira_folha' => ['nullable', Rule::requiredIf(fn () => $request->input('meio') === 'cheque'), 'integer', 'min:1', 'max:999999999'],
            'cheque_bom_para' => 'nullable|date',
        ], [
            'forma.required' => 'Informe se o pagamento é à vista ou parcelado.',
            'forma.in' => 'Forma de pagamento inválida.',
            'valor.required_if' => 'Informe o valor pago.',
            'valor.required' => 'Informe o valor pago.',
            'valor.numeric' => 'O valor precisa ser um número.',
            'valor.min' => 'O valor precisa ser maior que zero.',
            'meio.required' => 'Informe a forma de pagamento (PIX, boleto, transferência...).',
            'meio.in' => 'Forma de pagamento inválida.',
            'meio_novo.required' => 'Digite o nome da forma de pagamento nova.',
            'meio_novo.max' => 'O nome da forma de pagamento pode ter no máximo 60 caracteres.',
            'banco.required' => 'Informe de qual banco saiu o pagamento.',
            'banco.max' => 'O nome do banco pode ter no máximo 100 caracteres.',
            'banco_novo.required' => 'Digite o nome do banco novo.',
            'banco_novo.max' => 'O nome do banco pode ter no máximo 100 caracteres.',
            'data_pagamento.required' => 'Informe a data do pagamento.',
            'data_pagamento.date' => 'Data do pagamento inválida.',
            'cheque_primeira_folha.required' => 'Informe o número da primeira folha de cheque.',
            'cheque_primeira_folha.integer' => 'O número da folha de cheque precisa ser só números.',
            'cheque_primeira_folha.min' => 'Número da folha de cheque inválido.',
            'cheque_primeira_folha.max' => 'Número da folha de cheque inválido.',
            'cheque_bom_para.date' => 'Data "bom para" do cheque inválida.',
        ]);
    }

    /**
     * Grava as baixas de um pagamento (uma por compra, todas no mesmo lote) e, se for cheque, as folhas sobre o
     * valor total. Tudo numa transação: se uma parte falhar, nada fica gravado.
     *
     * @param  list<array{compra: PurchaseRequest, valor: float, forma: string}>  $partes
     * @return list<array{numero: int, valor: float}> as folhas de cheque emitidas
     */
    private function gravarPagamento(array $dados, float $valor, array $partes, DesdobramentoCheque $desdobramento): array
    {
        // O banco escolhido (ou digitado como novo) fica cadastrado na lista, sem duplicar grafias.
        // Forma nova digitada na hora: fica cadastrada (ou reaproveita a que já existe com esse nome).
        $meio = $dados['meio'] === '__novo' ? MeioPagamento::cadastrar($dados['meio_novo']) : $dados['meio'];

        $banco = null;
        if ($meio !== 'dinheiro') {
            $nomeBanco = trim((string) (($dados['banco'] ?? '') === '__novo' ? $dados['banco_novo'] : ($dados['banco'] ?? '')));
            $banco = $nomeBanco === '' ? null : Banco::cadastrar($nomeBanco)->nome;
        }

        // Cheque: o valor é desdobrado em folhas iguais abaixo do limite, numeradas a partir da primeira.
        $folhas = [];
        if ($meio === 'cheque') {
            $primeira = (int) $dados['cheque_primeira_folha'];
            foreach ($desdobramento->valores(DesdobramentoCheque::centavos($valor)) as $i => $centavos) {
                $folhas[] = ['numero' => $primeira + $i, 'valor' => $centavos / 100];
            }
            $this->recusarFolhasUsadas($banco, array_column($folhas, 'numero'));
        }

        $lote = (string) Str::uuid();

        try {
            DB::transaction(function () use ($partes, $dados, $meio, $banco, $folhas, $lote) {
                foreach ($partes as $parte) {
                    PagamentoCompra::create([
                        'lote' => $lote,
                        'purchase_request_id' => $parte['compra']->id,
                        'valor' => $parte['valor'],
                        'forma' => $parte['forma'],
                        'meio' => $meio,
                        'banco' => $banco,
                        'data_pagamento' => $dados['data_pagamento'],
                        'obs' => $dados['obs'] ?? null,
                        'user_id' => auth()->id(),
                    ]);
                }

                foreach ($folhas as $folha) {
                    Cheque::create([
                        'lote' => $lote,
                        'banco' => $banco,
                        'numero' => $folha['numero'],
                        'valor' => $folha['valor'],
                        'bom_para' => $dados['cheque_bom_para'] ?? $dados['data_pagamento'],
                        'situacao' => 'emitido',
                        'user_id' => auth()->id(),
                    ]);
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Outra pessoa usou a mesma folha no mesmo instante.
            $this->recusarFolhasUsadas($banco, array_column($folhas, 'numero'));
            throw $this->erroFolha('Uma das folhas de cheque acabou de ser usada. Confira os números e tente de novo.');
        }

        return $folhas;
    }

    /** " 3 folhas de cheque (nº 000101 a 000103)." para a mensagem de sucesso; vazio sem cheque. */
    private function textoFolhas(array $folhas): string
    {
        if ($folhas === []) {
            return '';
        }

        return ' ' . count($folhas) . (count($folhas) === 1 ? ' folha de cheque' : ' folhas de cheque')
            . ' (nº ' . str_pad((string) $folhas[0]['numero'], 6, '0', STR_PAD_LEFT)
            . (count($folhas) > 1 ? ' a ' . str_pad((string) end($folhas)['numero'], 6, '0', STR_PAD_LEFT) : '') . ').';
    }

    /** Estorna um pagamento: ele fica guardado (com quem, quando e o motivo), mas deixa de abater o saldo. */
    public function estornar(Request $request, PagamentoCompra $pagamento, SaldoFornecedores $saldos)
    {
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($pagamento->compra?->supplier))));

        if ($pagamento->estornado()) {
            return $volta->with('aviso', 'Este pagamento já estava estornado — nada foi alterado.');
        }

        $dados = $request->validate([
            'motivo' => 'required|string|max:500',
        ], [
            'motivo.required' => 'Informe o motivo do estorno.',
            'motivo.max' => 'O motivo pode ter no máximo 500 caracteres.',
        ]);

        // Pagamento ao fornecedor dividido em várias compras é um dinheiro só: o estorno vale para todas as partes.
        $partes = $pagamento->lote !== null
            ? PagamentoCompra::where('lote', $pagamento->lote)->whereNull('estornado_em')->get()
            : collect([$pagamento]);

        // As folhas de cheque do pagamento ficam canceladas (o número continua usado: a folha física já foi preenchida).
        DB::transaction(function () use ($pagamento, $partes, $dados) {
            foreach ($partes as $parte) {
                $parte->forceFill([
                    'estornado_em' => now(),
                    'estornado_por' => auth()->id(),
                    'motivo_estorno' => trim($dados['motivo']),
                ])->save();
            }

            if ($pagamento->lote !== null) {
                Cheque::where('lote', $pagamento->lote)->where('situacao', '!=', 'cancelado')
                    ->update(['situacao' => 'cancelado', 'cancelado_em' => now()]);
            }
        });

        $total = round((float) $partes->sum('valor'), 2);

        return $volta->with('success', 'Pagamento de ' . Dinheiro::brl($total) . ' estornado'
            . ($partes->count() > 1 ? ' (' . $partes->count() . ' compras)' : '') . '; o valor voltou para o saldo.');
    }

    /** Lança um acréscimo (impostos, frete...): soma no custo real da compra, sem aumentar a dívida com o fornecedor. */
    public function acrescentar(Request $request, PurchaseRequest $conta, SaldoFornecedores $saldos)
    {
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($conta->supplier))));

        if (!$saldos->conta($conta)) {
            return redirect()->route('financeiro.index')->with('aviso', 'Esta compra não entra no financeiro — nada foi alterado.');
        }

        if ($request->filled('valor')) {
            $request->merge(['valor' => Dinheiro::decimal($request->input('valor'))]);
        }

        $dados = $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'motivo' => 'required|string|max:200',
        ], [
            'valor.required' => 'Informe o valor do acréscimo.',
            'valor.numeric' => 'O valor precisa ser um número.',
            'valor.min' => 'O valor precisa ser maior que zero.',
            'motivo.required' => 'Informe o motivo do acréscimo (ex.: impostos, frete).',
            'motivo.max' => 'O motivo pode ter no máximo 200 caracteres.',
        ]);

        $valor = round((float) $dados['valor'], 2);

        AcrescimoCompra::create([
            'purchase_request_id' => $conta->id,
            'valor' => $valor,
            'motivo' => trim($dados['motivo']),
            'user_id' => auth()->id(),
        ]);

        return $volta->with('success', 'Acréscimo de ' . Dinheiro::brl($valor) . ' lançado na requisição #' . $conta->id . '.');
    }

    public function removerAcrescimo(AcrescimoCompra $acrescimo, SaldoFornecedores $saldos)
    {
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($acrescimo->compra?->supplier))));
        $valor = $acrescimo->valor;

        $acrescimo->delete();

        return $volta->with('success', 'Acréscimo de ' . Dinheiro::brl($valor) . ' removido.');
    }

    /** Relatório de um período (semana, mês ou datas escolhidas), para ver na tela, imprimir ou salvar em PDF. */
    public function relatorio(Request $request, RelatorioFinanceiro $relatorios)
    {
        [$de, $ate, $periodo] = $relatorios->periodo($request->query('periodo'), $request->query('de'), $request->query('ate'));
        $q = trim((string) $request->query('q', ''));
        $r = $relatorios->gerar($de, $ate, null, $q);

        return view('financeiro.relatorio', compact('r', 'periodo', 'q'));
    }

    /** O mesmo relatório em Excel: uma aba para o resumo, os pagamentos, as compras, os fornecedores e o que está em aberto. */
    public function planilha(Request $request, RelatorioFinanceiro $relatorios)
    {
        [$de, $ate] = $relatorios->periodo($request->filled('de') || $request->filled('ate') ? 'personalizado' : $request->query('periodo'), $request->query('de'), $request->query('ate'));
        $q = trim((string) $request->query('q', ''));
        $r = $relatorios->gerar($de, $ate, null, $q);

        if (!class_exists(\ZipArchive::class)) {
            return redirect()->route('financeiro.relatorio', ['periodo' => 'personalizado', 'de' => $de->format('Y-m-d'), 'ate' => $ate->format('Y-m-d')])
                ->with('aviso', 'Não deu para gerar a planilha: falta a extensão zip do PHP no servidor. Use Imprimir / PDF.');
        }

        $titulo = 'Relatório financeiro · ' . $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y') . ($q !== '' ? ' · só: ' . $q : '');
        $gerado = 'Gerado em ' . now('America/Sao_Paulo')->format('d/m/Y H:i') . ' por ' . (auth()->user()->name ?? '—');
        $s = $r['resumo'];

        $planilha = (new PlanilhaXlsx())
            ->aba('Resumo', $titulo, $gerado, [
                ['titulo' => 'Item', 'largura' => 46], ['titulo' => 'Valor', 'tipo' => 'dinheiro', 'largura' => 18],
            ], [
                ['Pago no período', $s['pago']],
                ['Pagamentos feitos no período', ['v' => $s['pagamentos'], 't' => 'numero']],
                ['Comprado no período (valor do pedido)', $s['comprado']],
                ['Compras feitas no período', ['v' => $s['compras'], 't' => 'numero']],
                ['Acréscimos das compras do período (impostos, frete...)', $s['acrescimos']],
                ['Custo real das compras do período', $s['custo_real']],
                ['Saldo devedor hoje (todas as compras)', $s['saldo_hoje']],
                ['Vencido hoje', $s['vencido_hoje']],
                ['', null],
                ['PAGO POR FORMA DE PAGAMENTO', null],
                ...array_map(fn ($m) => [$m['nome'] . ' (' . $m['pagamentos'] . ')', $m['valor']], $r['por_meio']),
                ['', null],
                ['PAGO POR BANCO', null],
                ...array_map(fn ($b) => [$b['nome'] . ' (' . $b['pagamentos'] . ')', $b['valor']], $r['por_banco']),
            ])
            ->aba('Pagamentos', 'Pagamentos · ' . $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y'), $gerado, [
                ['titulo' => 'Data', 'tipo' => 'data', 'largura' => 12], ['titulo' => 'Fornecedor', 'largura' => 28],
                ['titulo' => 'Requisição', 'tipo' => 'numero', 'largura' => 11], ['titulo' => 'Produto', 'largura' => 36],
                ['titulo' => 'Tipo', 'largura' => 11], ['titulo' => 'Forma', 'largura' => 18], ['titulo' => 'Banco', 'largura' => 18],
                ['titulo' => 'Valor', 'tipo' => 'dinheiro', 'largura' => 15], ['titulo' => 'Observação', 'largura' => 30], ['titulo' => 'Lançado por', 'largura' => 20],
            ], array_map(fn ($p) => [$p['data'], $p['fornecedor'], $p['requisicao'], $p['produto'], $p['tipo'], $p['meio'], $p['banco'], $p['valor'], $p['obs'], $p['usuario']], $r['pagamentos']),
                ['Total', null, null, count($r['pagamentos']) . ' pagamentos', null, null, null, $s['pago'], null, null])
            ->aba('Compras', 'Compras · ' . $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y'), $gerado, [
                ['titulo' => 'Data', 'tipo' => 'data', 'largura' => 12], ['titulo' => 'Fornecedor', 'largura' => 28],
                ['titulo' => 'Requisição', 'tipo' => 'numero', 'largura' => 11], ['titulo' => 'Produto', 'largura' => 36],
                ['titulo' => 'Qtd', 'tipo' => 'numero', 'largura' => 7], ['titulo' => 'Comprador', 'largura' => 20], ['titulo' => 'Condição', 'largura' => 16],
                ['titulo' => 'Valor', 'tipo' => 'dinheiro', 'largura' => 15], ['titulo' => 'Acréscimo', 'tipo' => 'dinheiro', 'largura' => 14],
                ['titulo' => 'Motivo do acréscimo', 'largura' => 26], ['titulo' => 'Custo real', 'tipo' => 'dinheiro', 'largura' => 15],
                ['titulo' => 'Pago até hoje', 'tipo' => 'dinheiro', 'largura' => 15], ['titulo' => 'Em aberto', 'tipo' => 'dinheiro', 'largura' => 15], ['titulo' => 'Situação', 'largura' => 11],
            ], array_map(fn ($l) => [
                $l['compra']->data_compra, $l['fornecedor'], $l['compra']->id, $l['compra']->product_name, (int) $l['compra']->quantity,
                $l['compra']->requester_name, $l['condicao'], $l['custo'], $l['acrescimo'] ?: null,
                $l['compra']->acrescimos->pluck('motivo')->implode('; ') ?: null, $l['custo_total'], $l['pago'], $l['aberto'], $l['situacao'],
            ], $r['compras']), ['Total', null, null, count($r['compras']) . ' compras', null, null, null, $s['comprado'], $s['acrescimos'], null, $s['custo_real'],
                round(collect($r['compras'])->sum('pago'), 2), round(collect($r['compras'])->sum('aberto'), 2), null])
            ->aba('Fornecedores', 'Fornecedores · ' . $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y'), $gerado, [
                ['titulo' => 'Fornecedor', 'largura' => 32], ['titulo' => 'Compras', 'tipo' => 'numero', 'largura' => 10],
                ['titulo' => 'Comprado no período', 'tipo' => 'dinheiro', 'largura' => 20], ['titulo' => 'Acréscimos', 'tipo' => 'dinheiro', 'largura' => 14],
                ['titulo' => 'Pago no período', 'tipo' => 'dinheiro', 'largura' => 18], ['titulo' => 'Saldo devedor hoje', 'tipo' => 'dinheiro', 'largura' => 19],
            ], array_map(fn ($f) => [$f['nome'], $f['compras'], $f['comprado'], $f['acrescimos'] ?: null, $f['pago'], $f['saldo']], $r['por_fornecedor']),
                ['Total', count($r['compras']), $s['comprado'], $s['acrescimos'], $s['pago'], round(collect($r['por_fornecedor'])->sum('saldo'), 2)])
            ->aba('Em aberto', 'Em aberto hoje · ' . now('America/Sao_Paulo')->format('d/m/Y'), 'Todas as compras que ainda têm algo a pagar, pelo próximo vencimento', [
                ['titulo' => 'Próximo vencimento', 'tipo' => 'data', 'largura' => 18], ['titulo' => 'Vencido', 'tipo' => 'dinheiro', 'largura' => 14],
                ['titulo' => 'Fornecedor', 'largura' => 28], ['titulo' => 'Requisição', 'tipo' => 'numero', 'largura' => 11], ['titulo' => 'Produto', 'largura' => 36],
                ['titulo' => 'Compra', 'tipo' => 'data', 'largura' => 12], ['titulo' => 'Condição', 'largura' => 16],
                ['titulo' => 'Valor', 'tipo' => 'dinheiro', 'largura' => 15], ['titulo' => 'Pago', 'tipo' => 'dinheiro', 'largura' => 15], ['titulo' => 'Em aberto', 'tipo' => 'dinheiro', 'largura' => 15],
            ], array_map(fn ($l) => [
                $l['proximo_vencimento'], $l['vencido'] ?: null, $l['fornecedor'], $l['compra']->id, $l['compra']->product_name,
                $l['compra']->data_compra, $l['condicao'], $l['custo'], $l['pago'], $l['aberto'],
            ], $r['abertas']), ['Total', $s['vencido_hoje'], null, null, $s['abertas'] . ' compras', null, null, null, null, $s['saldo_hoje']]);

        $nome = 'relatorio-financeiro-' . $de->format('Y-m-d') . '-a-' . $ate->format('Y-m-d') . '.xlsx';

        return response()->download($planilha->salvar(), $nome, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /** Aba Cheques: todas as folhas emitidas, com filtro por banco, situação, período (bom para) e busca por nº ou fornecedor. */
    public function cheques(Request $request)
    {
        $filtros = [
            'q' => trim((string) $request->query('q', '')),
            'banco' => trim((string) $request->query('banco', '')),
            'situacao' => array_key_exists((string) $request->query('situacao'), Cheque::SITUACOES) ? (string) $request->query('situacao') : '',
            'de' => $this->dataValida($request->query('de')),
            'ate' => $this->dataValida($request->query('ate')),
        ];

        $query = Cheque::query()
            ->with(['pagamentos.compra.fornecedor', 'user'])
            ->when($filtros['banco'] !== '', fn ($q) => $q->where('banco', $filtros['banco']))
            ->when($filtros['situacao'] !== '', fn ($q) => $q->where('situacao', $filtros['situacao']))
            ->when($filtros['de'], fn ($q, $de) => $q->whereDate('bom_para', '>=', $de))
            ->when($filtros['ate'], fn ($q, $ate) => $q->whereDate('bom_para', '<=', $ate))
            ->when($filtros['q'] !== '', function ($q) use ($filtros) {
                $termo = $filtros['q'];
                $numero = ltrim(preg_replace('/\D/', '', $termo), '0');

                $q->where(function ($sub) use ($termo, $numero) {
                    if ($numero !== '') {
                        $sub->where('numero', (int) $numero);
                    }
                    $sub->orWhereHas('pagamentos.compra', fn ($c) => $c->whereRaw('LOWER(supplier) LIKE ?', ['%' . mb_strtolower($termo) . '%']));
                });
            });

        $valendo = (clone $query)->where('situacao', '!=', 'cancelado');
        $total = round((float) (clone $valendo)->sum('valor'), 2);
        $qtdValendo = (clone $valendo)->count();

        $cheques = $query->orderByDesc('bom_para')->orderBy('banco')->orderBy('numero')->paginate(self::POR_PAGINA)->withQueryString();
        // Bancos cadastrados e os que já aparecem em alguma folha.
        $bancos = collect(Banco::nomes())->merge(Cheque::query()->distinct()->pluck('banco'))
            ->unique(fn ($b) => Banco::chave($b))->sortBy(fn ($b) => Banco::chave($b))->values();

        return view('financeiro.cheques', compact('cheques', 'filtros', 'total', 'qtdValendo', 'bancos'));
    }

    private function dataValida(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) && strtotime($valor) !== false ? $valor : null;
    }

    /** Lista paginada de compras (abas Aguardando e Pagos), com busca por fornecedor, produto, comprador ou nº da requisição. */
    private function lista(Request $request, Collection $linhas, string $modo, array $extra = [])
    {
        $q = trim((string) $request->query('q', ''));
        $empresaSel = $this->empresaEscolhida($request);
        $empresas = app(SaldoFornecedores::class)->empresas(app(SaldoFornecedores::class)->linhas());

        if ($q !== '') {
            $linhas = $linhas->filter(fn ($l) => $this->contem($l['fornecedor'], $q)
                || $this->contem((string) $l['compra']->product_name, $q)
                || $this->contem((string) $l['compra']->requester_name, $q)
                || ltrim($q, '#') === (string) $l['compra']->id)->values();
        }

        $total = round($linhas->sum($modo === 'aguardando' ? 'aberto' : 'pago'), 2);

        $pagina = max(1, (int) $request->query('page', 1));
        $itens = new LengthAwarePaginator(
            $linhas->forPage($pagina, self::POR_PAGINA)->values(),
            $linhas->count(),
            self::POR_PAGINA,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('financeiro.compras', compact('itens', 'modo', 'q', 'total', 'empresas', 'empresaSel') + $extra + ['atalhos' => [], 'vence' => null, 'de' => null, 'ate' => null]);
    }

    /** Para o pagamento se alguma das folhas já foi usada nesse banco (mesmo em cheque cancelado). */
    private function recusarFolhasUsadas(?string $banco, array $numeros): void
    {
        $usadas = Cheque::where('banco', $banco)->whereIn('numero', $numeros)->orderBy('numero')->get();

        if ($usadas->isNotEmpty()) {
            $lista = $usadas->map(fn (Cheque $ch) => $ch->numeroFormatado())->implode(', ');
            throw $this->erroFolha((count($usadas) === 1 ? "A folha {$lista} do {$banco} já foi usada" : "As folhas {$lista} do {$banco} já foram usadas")
                . '. A próxima folha livre desse banco é ' . str_pad((string) (Cheque::proximasFolhas()[$banco] ?? 1), 6, '0', STR_PAD_LEFT) . '.');
        }
    }

    private function erroFolha(string $mensagem): ValidationException
    {
        return ValidationException::withMessages(['cheque_primeira_folha' => $mensagem]);
    }

    /** A empresa escolhida no filtro (a chave), ou null para "todas". */
    private function empresaEscolhida(Request $request): ?string
    {
        $empresa = trim((string) $request->query('empresa', ''));

        return $empresa === '' ? null : $empresa;
    }

    /** Busca sem diferenciar maiúsculas, acentos ou espaços nas pontas. */
    private function contem(string $texto, string $busca): bool
    {
        $normalizar = fn (string $s) => mb_strtolower(Str::ascii(trim($s)));

        return str_contains($normalizar($texto), $normalizar($busca));
    }
}
