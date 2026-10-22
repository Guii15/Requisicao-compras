<?php

namespace App\Http\Controllers;

use App\Models\AcrescimoCompra;
use App\Models\Banco;
use App\Models\Cheque;
use App\Models\MeioPagamento;
use App\Models\PagamentoCompra;
use App\Models\PurchaseRequest;
use App\Services\DesdobramentoCheque;
use App\Services\PainelFinanceiro;
use App\Services\RelatorioFinanceiro;
use App\Services\SaldoFornecedores;
use App\Support\Dinheiro;
use App\Support\PlanilhaXlsx;
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

    public function index(Request $request, PainelFinanceiro $painel)
    {
        $d = $painel->dados(null, $request->query('empresa'));

        return view('financeiro.index', ['d' => $d, 'empresas' => collect($d['empresas']), 'empresaSel' => $d['empresa']]);
    }

    public function aguardando(Request $request, SaldoFornecedores $saldos)
    {
        $linhas = $saldos->somenteEmpresa($saldos->linhas(), $request->query('empresa'))
            ->where('aberto', '>', 0)
            ->sortByDesc(fn ($l) => $l['compra']->data_compra->format('Ymd') . str_pad((string) $l['compra']->id, 10, '0', STR_PAD_LEFT))
            ->values();

        return $this->lista($request, $linhas, 'aguardando');
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
            $fornecedores = $fornecedores
                ->filter(fn ($f) => $this->contem($f['nome'], $q) || str_contains($f['chave'], $saldos->chave($q)))
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

        return view('financeiro.fornecedor', compact('chave', 'nome', 'compras', 'totais', 'empresas', 'empresaSel'));
    }

    public function pagar(Request $request, PurchaseRequest $purchaseRequest, SaldoFornecedores $saldos, DesdobramentoCheque $desdobramento)
    {
        // Volta para a tela de onde o pagamento foi feito (lista ou fornecedor).
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($purchaseRequest->supplier))));

        if (!$saldos->conta($purchaseRequest)) {
            return redirect()->route('financeiro.index')->with('aviso', 'Esta compra não entra no financeiro — nada foi alterado.');
        }

        $situacao = $saldos->situacao($purchaseRequest->load('pagamentos'));

        if ($situacao['aberto'] <= 0) {
            return $volta->with('aviso', 'Esta compra já está paga — nada foi alterado.');
        }

        if ($request->filled('valor')) {
            $request->merge(['valor' => Dinheiro::decimal($request->input('valor'))]);
        }

        // A folha vem impressa com zeros na frente ("000101"): guarda só o número.
        if (preg_match('/^\s*0*(\d+)\s*$/', (string) $request->input('cheque_primeira_folha'), $m)) {
            $request->merge(['cheque_primeira_folha' => $m[1]]);
        }

        $dados = $request->validate([
            'forma' => 'required|in:a_vista,parcelado',
            'valor' => 'required_if:forma,parcelado|nullable|numeric|min:0.01',
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

        // À vista quita o que falta; parcelado abate só o que foi informado.
        $valor = $dados['forma'] === 'a_vista' ? $situacao['aberto'] : round((float) $dados['valor'], 2);

        if ($valor > $situacao['aberto'] + 0.004) {
            throw ValidationException::withMessages([
                'valor' => 'O valor é maior do que falta pagar nesta compra (' . Dinheiro::brl($situacao['aberto']) . ').',
            ]);
        }

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
            // Pagamento e folhas são gravados juntos: se uma parte falhar, nada fica gravado.
            DB::transaction(function () use ($purchaseRequest, $valor, $dados, $meio, $banco, $folhas, $lote) {
                PagamentoCompra::create([
                    'lote' => $lote,
                    'purchase_request_id' => $purchaseRequest->id,
                    'valor' => $valor,
                    'forma' => $dados['forma'],
                    'meio' => $meio,
                    'banco' => $banco,
                    'data_pagamento' => $dados['data_pagamento'],
                    'obs' => $dados['obs'] ?? null,
                    'user_id' => auth()->id(),
                ]);

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

        $mensagem = 'Pagamento de ' . Dinheiro::brl($valor) . ' registrado.';
        if ($folhas !== []) {
            $mensagem .= ' ' . count($folhas) . (count($folhas) === 1 ? ' folha de cheque' : ' folhas de cheque')
                . ' (nº ' . str_pad((string) $folhas[0]['numero'], 6, '0', STR_PAD_LEFT)
                . (count($folhas) > 1 ? ' a ' . str_pad((string) end($folhas)['numero'], 6, '0', STR_PAD_LEFT) : '') . ').';
        }

        return $volta->with('success', $mensagem);
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

        // As folhas de cheque do pagamento ficam canceladas (o número continua usado: a folha física já foi preenchida).
        DB::transaction(function () use ($pagamento, $dados) {
            $pagamento->forceFill([
                'estornado_em' => now(),
                'estornado_por' => auth()->id(),
                'motivo_estorno' => trim($dados['motivo']),
            ])->save();

            if ($pagamento->lote !== null) {
                Cheque::where('lote', $pagamento->lote)->where('situacao', '!=', 'cancelado')
                    ->update(['situacao' => 'cancelado', 'cancelado_em' => now()]);
            }
        });

        return $volta->with('success', 'Pagamento de ' . Dinheiro::brl($pagamento->valor) . ' estornado; o valor voltou para o saldo.');
    }

    /** Lança um acréscimo (impostos, frete...): soma no custo real da compra, sem aumentar a dívida com o fornecedor. */
    public function acrescentar(Request $request, PurchaseRequest $purchaseRequest, SaldoFornecedores $saldos)
    {
        $volta = redirect()->to(url()->previous(route('financeiro.fornecedor', $saldos->chave($purchaseRequest->supplier))));

        if (!$saldos->conta($purchaseRequest)) {
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
            'purchase_request_id' => $purchaseRequest->id,
            'valor' => $valor,
            'motivo' => trim($dados['motivo']),
            'user_id' => auth()->id(),
        ]);

        return $volta->with('success', 'Acréscimo de ' . Dinheiro::brl($valor) . ' lançado na requisição #' . $purchaseRequest->id . '.');
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
        $r = $relatorios->gerar($de, $ate);

        return view('financeiro.relatorio', compact('r', 'periodo'));
    }

    /** O mesmo relatório em Excel: uma aba para o resumo, os pagamentos, as compras, os fornecedores e o que está em aberto. */
    public function planilha(Request $request, RelatorioFinanceiro $relatorios)
    {
        [$de, $ate] = $relatorios->periodo($request->filled('de') || $request->filled('ate') ? 'personalizado' : $request->query('periodo'), $request->query('de'), $request->query('ate'));
        $r = $relatorios->gerar($de, $ate);

        if (!class_exists(\ZipArchive::class)) {
            return redirect()->route('financeiro.relatorio', ['periodo' => 'personalizado', 'de' => $de->format('Y-m-d'), 'ate' => $ate->format('Y-m-d')])
                ->with('aviso', 'Não deu para gerar a planilha: falta a extensão zip do PHP no servidor. Use Imprimir / PDF.');
        }

        $titulo = 'Relatório financeiro · ' . $de->format('d/m/Y') . ' a ' . $ate->format('d/m/Y');
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

        $total = round((float) (clone $query)->where('situacao', '!=', 'cancelado')->sum('valor'), 2);

        $cheques = $query->orderByDesc('bom_para')->orderBy('banco')->orderBy('numero')->paginate(self::POR_PAGINA)->withQueryString();
        $bancos = Cheque::query()->distinct()->orderBy('banco')->pluck('banco');

        return view('financeiro.cheques', compact('cheques', 'filtros', 'total', 'bancos'));
    }

    private function dataValida(mixed $valor): ?string
    {
        $valor = trim((string) $valor);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) && strtotime($valor) !== false ? $valor : null;
    }

    /** Lista paginada de compras (abas Aguardando e Pagos), com busca por fornecedor, produto, comprador ou nº da requisição. */
    private function lista(Request $request, Collection $linhas, string $modo)
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

        return view('financeiro.compras', compact('itens', 'modo', 'q', 'total', 'empresas', 'empresaSel'));
    }

    /** Para o pagamento se alguma das folhas já foi usada nesse banco (mesmo em cheque cancelado). */
    private function recusarFolhasUsadas(?string $banco, array $numeros): void
    {
        $usadas = Cheque::where('banco', $banco)->whereIn('numero', $numeros)->orderBy('numero')->get();

        if ($usadas->isNotEmpty()) {
            $lista = $usadas->map(fn (Cheque $ch) => $ch->numeroFormatado())->implode(', ');
            throw $this->erroFolha((count($usadas) === 1 ? "A folha {$lista} do {$banco} já foi usada" : "As folhas {$lista} do {$banco} já foram usadas")
                . '. Confira o número da primeira folha.');
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
