@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

@php $podeConferir = Auth::user()->isConferente(); @endphp

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Conferência</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">{{ $aba === 'conferidos' ? 'Requisições já conferidas' : ($aba === 'coleta' ? 'Requisições aprovadas aguardando coleta' : 'Requisições aprovadas aguardando conferência') }}</p>
    </div>

    <div class="m-rolagem" style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('conferencia.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'aguardando' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'aguardando') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Aguardando
        </a>
        <a href="{{ route('conferencia.index', ['aba' => 'conferidos']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'conferidos' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'conferidos' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'conferidos' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'conferidos' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'conferidos') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Conferidos
        </a>
        <a href="{{ route('conferencia.index', ['aba' => 'coleta']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'coleta' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'coleta' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'coleta' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'coleta' ? '#05018D' : 'transparent' }};"
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
                                    @elseif($podeConferir)
                                        <x-editar-parcial :item="$req" />
                                        <button onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='flex'"
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
                        {{-- Janela Conferir (desktop): mesmo modelo largo da janela do admin, com as notas ao lado em vez de empilhadas. --}}
                        <div id="modal-conferir-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:940px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
                                <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
                                    <div>
                                        <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Conferir Item</h3>
                                        <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <span style="font-weight:600; color:#1e293b;">{{ $req->product_name }}</span>
                                            <span>·</span>
                                            <span>Solicitante: <strong>{{ $req->requester_name }}</strong></span>
                                            <span>·</span>
                                            <span>Pedido: <strong>{{ $req->quantity }}</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='none'" aria-label="Fechar"
                                            style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
                                </div>

                                <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
                                    @csrf
                                    @method('PATCH')

                                    <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1.3fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">
                                        <div style="display:grid; grid-template-columns:1fr 1fr; column-gap:12px; align-content:start;">
                                            <div style="grid-column:1 / -1; font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Conferência</div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Quantidade recebida</label>
                                                <input type="number" name="quantidade_recebida" id="campo-qtd-{{ $req->id }}" value="{{ $req->quantity }}" min="0" required
                                                       oninput="verificaDivergencia{{ $req->id }}(this.value)"
                                                       style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; font-weight:700;">
                                            </div>
                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Resultado</label>
                                                <select name="resultado" id="campo-resultado-{{ $req->id }}" required onchange="atualizaResultado{{ $req->id }}(this.value)"
                                                        style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;">
                                                    <option value="ok">OK</option>
                                                    <option value="divergente">Divergente</option>
                                                </select>
                                            </div>
                                            <div id="aviso-divergencia-{{ $req->id }}" style="display:none; grid-column:1 / -1; margin:-6px 0 14px; font-size:12px; color:#b45309; font-weight:600;">
                                                ⚠️ Diferente da quantidade solicitada (pedido: {{ $req->quantity }})
                                            </div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Foto</label>
                                                <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" required style="width:100%; font-size:12px; color:#475569;">
                                            </div>
                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Fotos extras <span style="font-weight:400; color:#94a3b8;">(opcional — ex.: código de barras, até 5)</span></label>
                                                <input type="file" name="fotos_extras[]" accept=".jpg,.jpeg,.png,.webp" multiple style="width:100%; font-size:12px; color:#475569;">
                                            </div>

                                            <div id="campo-observacao-{{ $req->id }}" style="display:none; grid-column:1 / -1; margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação da divergência</label>
                                                <textarea name="observacao_conferencia" rows="2" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                            </div>

                                            <div style="grid-column:1 / -1;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Obs (geral)</label>
                                                <textarea name="obs" rows="2" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                            </div>
                                        </div>

                                        <div>
                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Sobre o pedido</div>
                                            <div style="font-size:13px; color:#334155; margin-bottom:18px;">
                                                <div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0; border-bottom:1px solid #f1f5f9;"><span style="color:#64748b;">Fornecedor</span><span style="text-align:right;">{{ $req->supplier ?: '—' }}</span></div>
                                                <div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0; border-bottom:1px solid #f1f5f9;"><span style="color:#64748b;">Entrega</span><span>{{ $req->tipo_entrega === 'entrega_direta' ? 'Venda Casada' : 'Estoque' }}</span></div>
                                                <div style="display:flex; justify-content:space-between; gap:12px; padding:6px 0;"><span style="color:#64748b;">Pedido de compra</span>
                                                    @if($req->pedido_compra_path)
                                                        <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; font-weight:600; text-decoration:underline;">📎 Ver pedido de compra</a>
                                                    @else
                                                        <span style="color:#94a3b8;">Não anexado</span>
                                                    @endif
                                                </div>
                                            </div>

                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Notas e ocorrências</div>
                                            @if(filled($req->admin_note) || filled($req->reason) || filled($req->justification) || filled($req->obs) || filled($req->observacao_conferencia) || filled($req->obs_entrada))
                                                <x-obs-todas :item="$req" margem="0" :plano="true" />
                                            @else
                                                <div style="font-size:13px; color:#64748b;">Nenhuma nota.</div>
                                            @endif
                                        </div>
                                    </div>

                                    <input type="hidden" name="acao" id="campo-acao-{{ $req->id }}" value="salvar">

                                    <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                                        <button type="button" onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='none'"
                                                style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        @if($req->tipo_entrega === 'entrega_direta')
                                        <button type="submit" id="btn-avancar-{{ $req->id }}" onclick="document.getElementById('campo-acao-{{ $req->id }}').value='avancar_mesmo_assim'"
                                                style="display:none; padding:8px 18px; border-radius:6px; background:#fff; color:#b45309; font-size:13px; font-weight:600; border:1px solid #b45309; cursor:pointer;">
                                            Avançar Mesmo Assim
                                        </button>
                                        @endif
                                        <button type="submit" id="btn-aguardar-{{ $req->id }}" value="aguardar_restante" title="O que chegou segue para a entrada; o que falta fica aguardando e será conferido quando chegar"
                                                onclick="document.getElementById('campo-acao-{{ $req->id }}').value='aguardar_restante'"
                                                style="display:none; padding:8px 18px; border-radius:6px; background:#fff; color:#374151; font-size:13px; font-weight:600; border:1px solid #9ca3af; cursor:pointer;">
                                            Aguardar restante
                                        </button>
                                        <button type="submit" onclick="document.getElementById('campo-acao-{{ $req->id }}').value='salvar'"
                                                style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">
                                            Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                        function atualizaResultado{{ $req->id }}(valor) {
                            document.getElementById('campo-observacao-{{ $req->id }}').style.display = valor === 'divergente' ? 'block' : 'none';
                            var btnAvancar = document.getElementById('btn-avancar-{{ $req->id }}');
                            if (btnAvancar) {
                                btnAvancar.style.display = valor === 'divergente' ? 'inline-block' : 'none';
                            }
                        }
                        function verificaDivergencia{{ $req->id }}(valor) {
                            var original = {{ $req->quantity }};
                            var recebida = parseInt(valor, 10);
                            var divergiu = valor !== '' && recebida !== original;
                            document.getElementById('aviso-divergencia-{{ $req->id }}').style.display = divergiu ? 'block' : 'none';
                            // Chegou só uma parte (mais que 0 e menos que o pedido): pode aguardar o restante.
                            document.getElementById('btn-aguardar-{{ $req->id }}').style.display = (recebida > 0 && recebida < original) ? 'inline-block' : 'none';
                            if (divergiu) {
                                document.getElementById('campo-resultado-{{ $req->id }}').value = 'divergente';
                                atualizaResultado{{ $req->id }}('divergente');
                            }
                        }
                        </script>
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

        @forelse($requests as $grupo)
            @foreach($grupo as $req)
            @if($podeConferir && $req->status_coleta !== 'coletado')
            <div id="modal-coleta-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center;">
                <div class="m-modal-caixa" style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:480px; margin:16px; box-shadow:0 20px 25px rgba(0,0,0,0.15);">
                    <h3 style="margin:0 0 8px; font-size:18px; font-weight:700; color:#05018D;">Registrar Coleta</h3>
                    <p style="margin:0 0 14px; font-size:14px; color:#6b7280;">Requisição #{{ $req->id }} - {{ $req->product_name }}</p>

                    <x-obs-todas :item="$req" margem="12px" />

                    <form method="POST" action="{{ route('conferencia.coleta', $req) }}" id="form-coleta-{{ $req->id }}" onsubmit="return validarColeta({{ $req->id }})">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:6px; text-transform:uppercase;">Data da Coleta *</label>
                            <input type="datetime-local" name="data_coleta" id="data-coleta-{{ $req->id }}" required
                                   value="{{ now()->format('Y-m-d\TH:i') }}"
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:6px; text-transform:uppercase;">Marcar como *</label>
                            <div style="display:flex; gap:8px;">
                                <label style="flex:1;">
                                    <input type="radio" name="status_coleta" value="coletado" checked style="margin-right:6px;">
                                    <span style="font-size:14px; color:#374151;">✓ Coletado</span>
                                </label>
                                <label style="flex:1;">
                                    <input type="radio" name="status_coleta" value="atraso" style="margin-right:6px;">
                                    <span style="font-size:14px; color:#dc2626;">⚠️ Atraso</span>
                                </label>
                            </div>
                        </div>

                        <div class="m-modal-acoes" style="display:flex; gap:10px; justify-content:flex-end;">
                            <button type="button" onclick="fecharModalColeta({{ $req->id }})"
                                    style="padding:10px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit"
                                    style="padding:10px 24px; border-radius:8px; background:#05018D; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Confirmar Coleta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            @endforeach
            @empty
            @endforelse

        <script>
        function abrirModalColeta(id) {
            document.getElementById('modal-coleta-' + id).style.display = 'flex';
        }
        function fecharModalColeta(id) {
            document.getElementById('modal-coleta-' + id).style.display = 'none';
        }
        function validarColeta(id) {
            var dataInput = document.getElementById('data-coleta-' + id);
            if (!dataInput.value) {
                alert('Por favor, selecione a data da coleta!');
                return false;
            }
            return true;
        }
        </script>
    @endif

</div>

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
