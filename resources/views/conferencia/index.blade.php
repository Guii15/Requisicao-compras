@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

@php
    $podeConferir = Auth::user()->isConferente();
    // Cada janela (Conferir, Editar parcial, Coleta) existe UMA vez na página e é preenchida pelo item clicado.
    $todosItens = collect($requests->items())->flatten(1);
    $haParaConferir = $podeConferir && $todosItens->contains(fn ($r) => $r->status_conferencia === null);
    $haParcial = $podeConferir && $todosItens->contains(fn ($r) => $r->restante_de_id && $r->status_conferencia === null);
    $haParaColetar = $podeConferir && $todosItens->contains(fn ($r) => $r->status_coleta !== 'coletado');
    // Já conferido: ainda dá para fotografar o que chegar depois (ex.: o restante de uma entrega divergente).
    $podeAdicionarFotos = fn ($r) => $podeConferir && in_array($r->status_conferencia, ['conferido_ok', 'divergente', 'avancado_mesmo_assim'], true);
    $haParaFotos = $todosItens->contains($podeAdicionarFotos);
@endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Conferência</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">{{ $aba === 'conferidos' ? 'Requisições já conferidas' : ($aba === 'coleta' ? 'Requisições aprovadas aguardando coleta' : 'Requisições aprovadas aguardando conferência') }}</p>
    </div>

    <div class="m-rolagem" style="display:flex; gap:4px; margin-bottom:24px; padding-bottom:8px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('conferencia.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0;
                  background:{{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'aguardando' ? '#fff' : '#6b7280' }};
                  border:2px solid transparent; border-bottom:2px solid transparent;"
           @if($aba !== 'aguardando') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Aguardando
        </a>
        <a href="{{ route('conferencia.index', ['aba' => 'conferidos']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0;
                  background:{{ $aba === 'conferidos' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'conferidos' ? '#fff' : '#6b7280' }};
                  border:2px solid transparent; border-bottom:2px solid transparent;"
           @if($aba !== 'conferidos') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Conferidos
        </a>
        <a href="{{ route('conferencia.index', ['aba' => 'coleta']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0;
                  background:{{ $aba === 'coleta' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'coleta' ? '#fff' : '#6b7280' }};
                  border:2px solid transparent; border-bottom:2px solid transparent;"
           @if($aba !== 'coleta') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Coleta
        </a>
    </div>

    @if($aba === 'conferidos')
        <div class="m-rolagem m-pilulas" style="display:flex; gap:8px; margin-bottom:20px;">
            @foreach(['todos' => 'Todos', 'ok' => 'OK', 'divergente' => 'Divergente'] as $valor => $rotulo)
                <a href="{{ route('conferencia.index', array_filter(['aba' => 'conferidos', 'resultado' => $valor === 'todos' ? null : $valor, 'q' => $q !== '' ? $q : null])) }}" @class(['ativo' => $resultado === $valor])
                   style="padding:5px 14px; font-size:13px; font-weight:600; text-decoration:none; border-radius:20px;
                          background:{{ $resultado === $valor ? '#05018D' : '#f3f4f6' }}; color:{{ $resultado === $valor ? '#fff' : '#6b7280' }};">
                    {{ $rotulo }}
                </a>
            @endforeach
        </div>
    @endif

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" action="{{ route('conferencia.index') }}" class="m-busca" style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="hidden" name="aba" value="{{ $aba }}">
            @if($aba === 'conferidos' && $resultado !== 'todos')
                <input type="hidden" name="resultado" value="{{ $resultado }}">
            @endif
            <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por produto, vendedor ou fornecedor..." data-placeholder-mobile="Produto, vendedor ou fornecedor"
                   style="flex:1; min-width:200px; border:1px solid #d1d5db; border-radius:7px; padding:9px 14px; font-size:14px; box-sizing:border-box;">
            <button type="submit" style="background:#05018D; color:#fff; padding:9px 20px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap;">
                Buscar
            </button>
            @if($q !== '')
                <a href="{{ route('conferencia.index', array_filter(['aba' => $aba, 'resultado' => $resultado !== 'todos' ? $resultado : null])) }}"
                   style="padding:9px 16px; border-radius:7px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:14px; white-space:nowrap;">
                    Limpar
                </a>
            @endif
        </form>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif

    @if(session('aviso'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ⚠️ {{ session('aviso') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            <strong>Não foi possível salvar a conferência:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($aba !== 'coleta')
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                {{-- Mesmo desenho do painel do admin: uma linha por requisição; ao abrir, cada item é um cartão. --}}
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thConf = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thConf }} text-align:left;">Nº</th>
                        <th style="{{ $thConf }} text-align:left;">Vendedor</th>
                        <th style="{{ $thConf }} text-align:left;">Itens</th>
                        <th style="{{ $thConf }} text-align:left;">Fornecedor</th>
                        <th style="{{ $thConf }} text-align:right;">Qtd</th>
                        <th style="{{ $thConf }} text-align:left;">Entrega</th>
                        <th style="{{ $thConf }} text-align:left;">Aprovado em</th>
                        <th style="{{ $thConf }} text-align:left;">{{ $aba === 'conferidos' ? 'Resultado' : 'Situação' }}</th>
                        <th style="{{ $thConf }} text-align:right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroConf = $grupo->first();
                            $chaveConf = $primeiroConf->grupo_id;
                            $statusConfUnicos = $grupo->map(fn($r) => $r->status_conferencia ?? 'aguardando')->unique();
                            if ($statusConfUnicos->count() === 1) {
                                $statusChaveConf = $statusConfUnicos->first();
                                $rotuloConf = ['aguardando' => 'Aguardando', 'conferido_ok' => 'OK', 'divergente' => 'Divergente', 'avancado_mesmo_assim' => 'Avançado', 'cancelado' => 'Cancelado', 'legado' => 'Legado'][$statusChaveConf] ?? ucfirst($statusChaveConf);
                            } else {
                                $statusChaveConf = 'parcial';
                                $rotuloConf = 'Parcial';
                            }
                            $corsGrupoConf = [
                                'aguardando'           => ['barra' => '#e5e7eb', 'bg' => '#f4b728', 'texto' => '#2b1d00'],
                                'conferido_ok'          => ['barra' => '#e5e7eb', 'bg' => '#17794a', 'texto' => '#ffffff'],
                                'divergente'            => ['barra' => '#e5e7eb', 'bg' => '#b8301a', 'texto' => '#ffffff'],
                                'avancado_mesmo_assim'  => ['barra' => '#e5e7eb', 'bg' => '#475569', 'texto' => '#ffffff'],
                                'cancelado'             => ['barra' => '#e5e7eb', 'bg' => '#b8301a', 'texto' => '#ffffff'],
                                'legado'                => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                                'parcial'               => ['barra' => '#e5e7eb', 'bg' => '#475569', 'texto' => '#ffffff'],
                            ][$statusChaveConf];
                            $produtosResumoConf = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoConf) > 80) {
                                $produtosResumoConf = mb_substr($produtosResumoConf, 0, 80) . '…';
                            }
                            // O mesmo fornecedor escrito de formas diferentes conta como um só.
                            $fornecedoresConf = $grupo->pluck('supplier')->filter()->unique(fn ($f) => \App\Support\RankingPorNome::chave($f));
                            $fornecedorConf = $fornecedoresConf->count() === 0 ? '—' : ($fornecedoresConf->count() === 1 ? $fornecedoresConf->first() : $fornecedoresConf->count() . ' fornecedores');
                            $entregasConf = $grupo->map(fn ($r) => $r->tipo_entrega === 'entrega_direta' ? 'Venda Casada' : 'Estoque')->unique();
                            $entregaConf = $entregasConf->count() === 1 ? $entregasConf->first() : 'Mista';
                            $aprovadoConf = $grupo->pluck('approved_at')->filter()->max();
                            $tdConf = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveConf }}')">
                            <td class="lr-num" style="{{ $tdConf }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroConf->id }}</td>
                            <td data-rotulo="Vendedor" style="{{ $tdConf }}">{{ $primeiroConf->requester_name ?? 'Não informado' }}</td>
                            <td class="lr-larga" data-rotulo="Itens" style="{{ $tdConf }} max-width:380px;">
                                <div style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoConf }}</div>
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                            </td>
                            <td data-rotulo="Fornecedor" style="{{ $tdConf }}">{{ $fornecedorConf }}</td>
                            <td data-rotulo="Qtd" style="{{ $tdConf }} text-align:right; font-weight:600; color:#111827; font-variant-numeric:tabular-nums;">{{ $grupo->sum('quantity') }}</td>
                            <td data-rotulo="Entrega" style="{{ $tdConf }} font-size:13px; white-space:nowrap;">{{ $entregaConf }}</td>
                            <td data-rotulo="Aprovado em" style="{{ $tdConf }} font-size:13px; white-space:nowrap;">
                                @if($aprovadoConf)
                                    {{ $aprovadoConf->timezone('America/Sao_Paulo')->format('d/m/Y') }}
                                    <span style="display:block; font-size:12px; color:#6b7280;">{{ $aprovadoConf->timezone('America/Sao_Paulo')->format('H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-rotulo="{{ $aba === 'conferidos' ? 'Resultado' : 'Situação' }}" style="{{ $tdConf }}">
                                <span style="background:{{ $corsGrupoConf['bg'] }}; color:{{ $corsGrupoConf['texto'] }}; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">{{ $rotuloConf }}</span>
                            </td>
                            <td class="lr-acao" style="{{ $tdConf }} text-align:right;">
                                <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveConf }}')"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    <span id="seta-grupo-{{ $chaveConf }}">Ver itens</span>
                                </button>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveConf }}" style="display:none; background:#f7f8fa;">
                            <td colspan="9" style="padding:{{ $loop->first ? '14px' : '0' }} 20px {{ $loop->last ? '18px' : '10px' }} 20px; {{ $loop->last ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
                                <x-item-requisicao :req="$req" :sem-precos="true">
                                    <span style="align-self:center; background:#f3f4f6; color:#374151; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">{{ $req->tipo_entrega === 'entrega_direta' ? 'Venda Casada' : 'Estoque' }}</span>
                                    @if($aba === 'conferidos' && $req->conferente)
                                        <span style="align-self:center; font-size:12.5px; color:#6b7280;">Conferido por <strong style="color:#374151;">{{ $req->conferente->name }}</strong></span>
                                    @endif
                                    {{-- O resultado da conferência já aparece no próprio cartão (coluna Produto e anexos). --}}
                                    @if($req->status_conferencia !== null)
                                        @if($podeAdicionarFotos($req))
                                            <button type="button" onclick="abrirAdicionarFotos(this)"
                                                    data-url="{{ route('conferencia.fotos', $req) }}"
                                                    data-produto="{{ $req->product_name }}"
                                                    style="background:#fff; color:#05018D; border:1px solid #05018D; border-radius:9999px; padding:7px 16px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                                📷 Adicionar fotos
                                            </button>
                                        @endif
                                    @elseif($podeConferir)
                                        <x-editar-parcial :item="$req" />
                                        <button type="button" onclick="abrirConferir({{ $req->id }})"
                                                style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                            Conferir
                                        </button>
                                    @else
                                        <span style="align-self:center; color:#9ca3af; font-size:12px;">Aguardando conferência</span>
                                    @endif
                                </x-item-requisicao>
                                @if(in_array($req->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true))
                                    <div style="margin-top:8px;"><x-entrada-info :item="$req" margem="0" /></div>
                                @endif
                            </td>
                        </tr>

                        @if($req->status_conferencia === null && $podeConferir)
                        {{-- Dados do item para a janela Conferir (única): o JS lê os data-* e copia as notas daqui. --}}
                        <template id="conferir-item-{{ $req->id }}"
                                  data-url="{{ route('conferencia.conferir', $req) }}"
                                  data-produto="{{ $req->product_name }}"
                                  data-solicitante="{{ $req->requester_name }}"
                                  data-qtd="{{ $req->quantity }}"
                                  data-fornecedor="{{ $req->supplier }}"
                                  data-entrega="{{ $req->tipo_entrega }}"
                                  data-pedido-url="{{ $req->pedido_compra_path ? route('admin.compras.pedido', $req) : '' }}">
                            @if(filled($req->admin_note) || filled($req->reason) || filled($req->justification) || filled($req->obs) || filled($req->observacao_conferencia) || filled($req->obs_entrada))
                                <x-obs-todas :item="$req" margem="0" :plano="true" />
                            @else
                                <div style="font-size:13px; color:#64748b;">Nenhuma nota.</div>
                            @endif
                        </template>
                        @endif
                    @endforeach
                    @empty
                        <tr>
                            <td colspan="9" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                {{ $aba === 'conferidos' ? 'Nenhuma requisição conferida ainda' : 'Nenhuma requisição aguardando conferência' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
            <div style="padding:16px 20px; border-top:1px solid #f3f4f6; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

    @endif

    @if($aba === 'coleta')
        <div class="m-rolagem m-pilulas" style="display:flex; gap:12px; margin-bottom:24px; flex-wrap:wrap;">
            @foreach(['aguardando' => ['emoji' => '⏳', 'label' => 'Aguardando'], 'coletado' => ['emoji' => '✅', 'label' => 'Coletados'], 'atraso' => ['emoji' => '⚠️', 'label' => 'Atraso']] as $valor => $config)
                <a href="{{ route('conferencia.index', array_filter(['aba' => 'coleta', 'resultado' => $valor])) }}" @class(['ativo' => $resultado === $valor])
                   style="padding:12px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:8px; transition:all 0.2s;
                          background:{{ $resultado === $valor ? '#05018D' : '#f3f4f6' }};
                          color:{{ $resultado === $valor ? '#fff' : '#374151' }};
                          border:{{ $resultado === $valor ? '2px solid #05018D' : '2px solid transparent' }};
                          box-shadow:{{ $resultado === $valor ? '0 4px 12px rgba(5, 1, 141, 0.2)' : 'none' }};">
                    {{ $config['emoji'] }} {{ $config['label'] }}
                </a>
            @endforeach
        </div>

        <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    {{-- Mesmo desenho do painel do admin: uma linha por requisição; ao abrir, cada item é um cartão. --}}
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                            @php $thCol = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                            <th style="{{ $thCol }} text-align:left;">Nº</th>
                            <th style="{{ $thCol }} text-align:left;">Vendedor</th>
                            <th style="{{ $thCol }} text-align:left;">Itens</th>
                            <th style="{{ $thCol }} text-align:left;">Fornecedor</th>
                            <th style="{{ $thCol }} text-align:right;">Qtd</th>
                            <th style="{{ $thCol }} text-align:left;">Status</th>
                            <th style="{{ $thCol }} text-align:left;">Data Coleta</th>
                            <th style="{{ $thCol }} text-align:right;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $grupo)
                            @php
                                $primeiroCol = $grupo->first();
                                $chaveCol = $primeiroCol->grupo_id;
                                $produtosResumoCol = $grupo->pluck('product_name')->filter()->implode(', ');
                                if (mb_strlen($produtosResumoCol) > 80) {
                                    $produtosResumoCol = mb_substr($produtosResumoCol, 0, 80) . '…';
                                }
                                // O mesmo fornecedor escrito de formas diferentes conta como um só.
                                $fornecedoresCol = $grupo->pluck('supplier')->filter()->unique(fn ($f) => \App\Support\RankingPorNome::chave($f));
                                $fornecedorCol = $fornecedoresCol->count() === 0 ? '—' : ($fornecedoresCol->count() === 1 ? $fornecedoresCol->first() : $fornecedoresCol->count() . ' fornecedores');
                                $situacoesCol = $grupo->map(fn ($r) => in_array($r->status_coleta, ['coletado', 'atraso'], true) ? $r->status_coleta : 'aguardando')->unique();
                                $situacaoCol = $situacoesCol->count() === 1 ? $situacoesCol->first() : ($situacoesCol->contains('atraso') ? 'atraso' : 'parcial');
                                $coletasCol = $grupo->pluck('data_coleta')->filter();
                                $faltaColetarCol = $grupo->filter(fn ($r) => $r->status_coleta !== 'coletado');
                                $tdCol = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                            @endphp
                            <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveCol }}')">
                                <td class="lr-num" style="{{ $tdCol }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroCol->id }}</td>
                                <td data-rotulo="Vendedor" style="{{ $tdCol }}">{{ $primeiroCol->requester_name ?? 'Não informado' }}</td>
                                <td class="lr-larga" data-rotulo="Itens" style="{{ $tdCol }} max-width:380px;">
                                    <div style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoCol }}</div>
                                    <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                                </td>
                                <td data-rotulo="Fornecedor" style="{{ $tdCol }}">{{ $fornecedorCol }}</td>
                                <td data-rotulo="Qtd" style="{{ $tdCol }} text-align:right; font-weight:600; color:#111827; font-variant-numeric:tabular-nums;">{{ $grupo->sum('quantity') }}</td>
                                <td data-rotulo="Status" style="{{ $tdCol }}">
                                    @if($situacaoCol === 'coletado')
                                        <span style="background:#f3f4f6; color:#374151; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Coletado</span>
                                    @elseif($situacaoCol === 'atraso')
                                        <span style="background:#fff; color:#b8301a; border:1px solid #b8301a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Atraso</span>
                                    @elseif($situacaoCol === 'parcial')
                                        <span style="background:#fff; color:#374151; border:1px solid #9ca3af; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Parcial</span>
                                    @else
                                        <span style="background:#fff; color:#7a4f00; border:1px solid #c98a00; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Aguardando</span>
                                    @endif
                                </td>
                                <td data-rotulo="Data coleta" style="{{ $tdCol }} font-size:13px; white-space:nowrap;">{{ $coletasCol->isEmpty() ? '—' : $coletasCol->max()->format('d/m/Y H:i') }}</td>
                                <td class="lr-acao" style="{{ $tdCol }} text-align:right; white-space:nowrap;">
                                    {{-- Requisição de um item só: dá para coletar direto da linha, sem abrir. --}}
                                    @if($podeConferir && $grupo->count() === 1 && $faltaColetarCol->count() === 1)
                                        <button type="button" onclick="event.stopPropagation(); abrirModalColeta({{ $faltaColetarCol->first()->id }})"
                                                style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 16px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap; margin-right:6px;">
                                            Coletar Agora
                                        </button>
                                    @endif
                                    <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveCol }}')"
                                            style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                        <span id="seta-grupo-{{ $chaveCol }}">Ver itens</span>
                                    </button>
                                </td>
                            </tr>
                            @foreach($grupo as $req)
                            <tr class="grupo-item-{{ $chaveCol }}" style="display:none; background:#f7f8fa;">
                                <td colspan="8" style="padding:{{ $loop->first ? '14px' : '0' }} 20px {{ $loop->last ? '18px' : '10px' }} 20px; {{ $loop->last ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
                                    <x-item-requisicao :req="$req" :sem-precos="true">
                                        @if($req->status_coleta === 'coletado')
                                            <span style="align-self:center; color:#6b7280; font-size:12.5px;">✓ Coletado{{ $req->data_coleta ? ' em ' . $req->data_coleta->format('d/m/Y H:i') : '' }}</span>
                                        @else
                                            @if($req->status_coleta === 'atraso')
                                                <span style="align-self:center; background:#fff; color:#b8301a; border:1px solid #b8301a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Atraso</span>
                                            @endif
                                            @if($podeConferir && $grupo->count() > 1)
                                                <button type="button" onclick="abrirModalColeta({{ $req->id }})"
                                                        style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                                    Coletar Agora
                                                </button>
                                            @endif
                                        @endif
                                    </x-item-requisicao>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="8" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                    {{ $resultado === 'coletado' ? 'Nenhuma coleta registrada.' : 'Nenhuma requisição aguardando coleta.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @foreach($requests as $grupo)
            @foreach($grupo as $req)
            @if($podeConferir && $req->status_coleta !== 'coletado')
            {{-- Dados do item para a janela Registrar Coleta (única). --}}
            <template id="coleta-item-{{ $req->id }}"
                      data-url="{{ route('conferencia.coleta', $req) }}"
                      data-produto="{{ $req->product_name }}"
                      data-solicitante="{{ $req->requester_name }}"
                      data-qtd="{{ $req->quantity }}">
                @if(filled($req->admin_note) || filled($req->reason) || filled($req->justification) || filled($req->obs) || filled($req->observacao_conferencia) || filled($req->obs_entrada))
                    <x-obs-todas :item="$req" margem="0" :plano="true" />
                @else
                    <div style="font-size:13px; color:#64748b;">Nenhuma nota.</div>
                @endif
            </template>
            @endif
            @endforeach
        @endforeach

        @if($haParaColetar)
            @include('conferencia._modal-coleta')
        @endif
    @endif

</div>

@if($aba !== 'coleta' && $haParaConferir)
    @include('conferencia._modal-conferir')
@endif
@if($aba !== 'coleta' && $haParcial)
    @include('conferencia._modal-editar-parcial')
@endif
@if($aba !== 'coleta' && $haParaFotos)
    @include('conferencia._modal-adicionar-fotos')
@endif
@if($aba !== 'coleta' && ($haParaConferir || $haParaFotos))
    @include('conferencia._camera')
@endif

<script>
function toggleGrupoRequisicao(chave) {
    var linhas = document.querySelectorAll('.grupo-item-' + CSS.escape(chave));
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? '' : 'none'; // '' devolve ao CSS: linha de tabela no PC, bloco no celular
    });
    // querySelectorAll: se o mesmo rótulo aparecer mais de uma vez na página, todos mudam juntos.
    document.querySelectorAll('[id="seta-grupo-' + chave + '"]').forEach(function (seta) {
        seta.textContent = abrindo ? 'Ocultar itens' : 'Ver itens';
    });
}

function protegerEnvioDuplo(form) {
    if (form.dataset.enviando === '1') {
        return false;
    }
    form.dataset.enviando = '1';
    form.querySelectorAll('button[type="submit"]').forEach(function (botao) {
        botao.disabled = true;
    });
    return true;
}

</script>

@endsection
