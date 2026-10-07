@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Entrada</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">{{ $aba === 'concluidas' ? 'Itens que já tiveram entrada registrada' : ($aba === 'divergencias' ? 'Itens que chegaram com divergência e ainda aguardam decisão' : 'Itens liberados pela conferência aguardando entrada') }}</p>
    </div>

    <div class="m-rolagem" style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('entrada.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'aguardando' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'aguardando') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Aguardando
        </a>
        <a href="{{ route('entrada.index', ['aba' => 'concluidas']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'concluidas' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'concluidas' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'concluidas' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'concluidas' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'concluidas') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Entrada Realizada
        </a>
        <a href="{{ route('entrada.index', ['aba' => 'divergencias']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px; display:inline-flex; align-items:center; gap:8px;
                  background:{{ $aba === 'divergencias' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'divergencias' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'divergencias' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'divergencias' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'divergencias') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Divergências
            @if($qtdDivergencias > 0)
                <span style="background:{{ $aba === 'divergencias' ? 'rgba(255,255,255,0.22)' : '#fee2e2' }}; color:{{ $aba === 'divergencias' ? '#fff' : '#dc2626' }}; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:700;">{{ $qtdDivergencias }}</span>
            @endif
        </a>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" action="{{ route('entrada.index') }}" class="m-busca" style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="hidden" name="aba" value="{{ $aba }}">
            <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por produto, vendedor ou fornecedor..." data-placeholder-mobile="Produto, vendedor ou fornecedor"
                   style="flex:1; min-width:200px; border:1px solid #d1d5db; border-radius:7px; padding:9px 14px; font-size:14px; box-sizing:border-box;">
            <button type="submit" style="background:#05018D; color:#fff; padding:9px 20px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap;">
                Buscar
            </button>
            @if($q !== '')
                <a href="{{ route('entrada.index', ['aba' => $aba]) }}" style="padding:9px 16px; border-radius:7px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:14px; white-space:nowrap;">
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
            <strong>Não foi possível registrar a entrada:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($aba === 'divergencias')
        @include('entrada._divergencias')
    @else
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                {{-- Mesmo desenho do painel do admin: uma linha por requisição; ao abrir, cada item é um cartão. --}}
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thEntr = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thEntr }} text-align:left;">Nº</th>
                        <th style="{{ $thEntr }} text-align:left;">{{ $aba === 'concluidas' ? 'Vendedor Destino' : 'Vendedor' }}</th>
                        <th style="{{ $thEntr }} text-align:left;">Itens</th>
                        <th style="{{ $thEntr }} text-align:left;">Fornecedor</th>
                        <th style="{{ $thEntr }} text-align:right;">Qtd Solic. / {{ $aba === 'concluidas' ? 'Entrada' : 'Receb.' }}</th>
                        <th style="{{ $thEntr }} text-align:right;">Total</th>
                        <th style="{{ $thEntr }} text-align:left;">Coleta</th>
                        <th style="{{ $thEntr }} text-align:left;">Situação</th>
                        <th style="{{ $thEntr }} text-align:right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroEntr = $grupo->first();
                            $chaveEntr = $primeiroEntr->grupo_id;
                            $statusEntrUnicos = $grupo->map(fn($r) => $r->entrada_concluida_em ? 'concluida' : 'aguardando')->unique();
                            if ($statusEntrUnicos->count() === 1) {
                                $statusChaveEntr = $statusEntrUnicos->first();
                                $rotuloEntr = $statusChaveEntr === 'concluida' ? 'Entrada Realizada' : 'Aguardando';
                            } else {
                                $statusChaveEntr = 'parcial';
                                $rotuloEntr = 'Parcial';
                            }
                            $corsGrupoEntr = [
                                'aguardando' => ['barra' => '#e5e7eb', 'bg' => '#f4b728', 'texto' => '#2b1d00'],
                                'concluida'  => ['barra' => '#e5e7eb', 'bg' => '#17794a', 'texto' => '#ffffff'],
                                'parcial'    => ['barra' => '#e5e7eb', 'bg' => '#475569', 'texto' => '#ffffff'],
                            ][$statusChaveEntr];
                            $produtosResumoEntr = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoEntr) > 80) {
                                $produtosResumoEntr = mb_substr($produtosResumoEntr, 0, 80) . '…';
                            }
                            // O mesmo fornecedor escrito de formas diferentes conta como um só.
                            $fornecedoresEntr = $grupo->pluck('supplier')->filter()->unique(fn ($f) => \App\Support\RankingPorNome::chave($f));
                            $fornecedorEntr = $fornecedoresEntr->count() === 0 ? '—' : ($fornecedoresEntr->count() === 1 ? $fornecedoresEntr->first() : $fornecedoresEntr->count() . ' fornecedores');
                            $qtdPedidaEntr = $grupo->sum('quantity');
                            $qtdChegouEntr = $grupo->sum(fn ($r) => (int) ($r->entrada_concluida_em ? $r->quantidade_entrada : $r->quantidade_recebida));
                            $totalEntr = (float) $grupo->sum(fn ($r) => $r->preco_unitario ? $r->preco_unitario * $r->quantity : 0);
                            $tdEntr = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveEntr }}')">
                            <td class="lr-num" style="{{ $tdEntr }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroEntr->id }}</td>
                            <td data-rotulo="{{ $aba === 'concluidas' ? 'Vendedor destino' : 'Vendedor' }}" style="{{ $tdEntr }}">{{ ($aba === 'concluidas' ? $primeiroEntr->vendedor_destino : $primeiroEntr->requester_name) ?? 'Não informado' }}</td>
                            <td class="lr-larga" data-rotulo="Itens" style="{{ $tdEntr }} max-width:380px;">
                                <div style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoEntr }}</div>
                                <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                            </td>
                            <td data-rotulo="Fornecedor" style="{{ $tdEntr }}">{{ $fornecedorEntr }}</td>
                            <td data-rotulo="Qtd solic. / {{ $aba === 'concluidas' ? 'entrada' : 'receb.' }}" style="{{ $tdEntr }} text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">{{ $qtdPedidaEntr }} / <strong style="color:{{ $qtdChegouEntr === $qtdPedidaEntr ? '#111827' : '#b8301a' }};">{{ $qtdChegouEntr }}</strong></td>
                            <td data-rotulo="Total" style="{{ $tdEntr }} text-align:right; font-weight:600; color:#111827; white-space:nowrap;">{{ $totalEntr > 0 ? 'R$ ' . number_format($totalEntr, 2, ',', '.') : '—' }}</td>
                            <td data-rotulo="Coleta" style="{{ $tdEntr }} font-size:13px; white-space:nowrap;">
                                @if($grupo->contains('status_coleta', 'atraso'))
                                    <strong style="color:#b8301a;">Atrasada</strong>
                                @else
                                    <span style="color:#6b7280;">No prazo</span>
                                @endif
                            </td>
                            <td data-rotulo="Situação" style="{{ $tdEntr }}">
                                <span style="background:{{ $corsGrupoEntr['bg'] }}; color:{{ $corsGrupoEntr['texto'] }}; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">{{ $rotuloEntr }}</span>
                            </td>
                            <td class="lr-acao" style="{{ $tdEntr }} text-align:right;">
                                <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveEntr }}')"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    <span id="seta-grupo-{{ $chaveEntr }}">Ver itens</span>
                                </button>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        @php
                            $elegivelEntradaEntr = $req->status === 'aprovado' && in_array($req->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true);
                            $chegouEntr = $req->entrada_concluida_em ? $req->quantidade_entrada : $req->quantidade_recebida;
                        @endphp
                        <tr class="grupo-item-{{ $chaveEntr }}" style="display:none; background:#f7f8fa;">
                            <td colspan="9" style="padding:{{ $loop->first ? '14px' : '0' }} 20px {{ $loop->last ? '18px' : '10px' }} 20px; {{ $loop->last ? 'border-bottom:1px solid #e5e7eb;' : '' }}">
                                <x-item-requisicao :req="$req">
                                    <span style="align-self:center; font-size:12.5px; color:#6b7280;">Pedido / {{ $req->entrada_concluida_em ? 'entrada' : 'recebido' }}: <strong style="color:#111827;">{{ $req->quantity }} / {{ $chegouEntr ?? '—' }}</strong></span>
                                    @if($req->entrada_concluida_em)
                                        <span style="align-self:center; font-size:12.5px; color:#6b7280;">Destino: <strong style="color:#374151;">{{ $req->vendedor_destino ?? '—' }}</strong></span>
                                    @elseif($elegivelEntradaEntr)
                                        <button onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='flex'"
                                                style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                            Dar Entrada
                                        </button>
                                    @else
                                        <span style="align-self:center; color:#9ca3af; font-size:12px;">Aguardando conferência</span>
                                    @endif
                                </x-item-requisicao>
                            </td>
                        </tr>

                        @if(!$req->entrada_concluida_em && $elegivelEntradaEntr)
                        {{-- Janela Dar Entrada (desktop): mesmo modelo largo da janela do admin, com as notas ao lado. --}}
                        <div id="modal-entrada-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:900px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
                                <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
                                    <div>
                                        <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Dar Entrada</h3>
                                        <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <span style="font-weight:600; color:#1e293b;">{{ $req->product_name }}</span>
                                            <span>·</span>
                                            <span>Solicitante: <strong>{{ $req->requester_name }}</strong></span>
                                            <span>·</span>
                                            <span>Pedido / recebido: <strong>{{ $req->quantity }} / {{ $req->quantidade_recebida ?? '—' }}</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='none'" aria-label="Fechar"
                                            style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
                                </div>

                                <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" id="form-entrada-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
                                    @csrf
                                    @method('PATCH')

                                    <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1.2fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">
                                        <div style="display:grid; grid-template-columns:1.6fr 1fr; column-gap:12px; align-content:start;">
                                            <div style="grid-column:1 / -1; font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Entrada</div>

                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Vendedor destino</label>
                                                <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box;">
                                            </div>
                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Quantidade dada entrada</label>
                                                <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida ?? $req->quantity }}" readonly style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background:#f1f5f9; color:#64748b; font-weight:700;">
                                            </div>
                                            <div style="grid-column:1 / -1; margin:-6px 0 14px; font-size:12px; color:#64748b;">Entrada precisa ser da quantidade cheia recebida na conferência ({{ $req->quantidade_recebida ?? $req->quantity }}). Se faltou unidade, resolva na conferência.</div>

                                            <div style="grid-column:1 / -1;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação <span style="font-weight:400; color:#94a3b8;">(opcional — o admin e o vendedor veem)</span></label>
                                                <textarea name="obs_entrada" rows="3" maxlength="500" placeholder="Ex: caixa amassada, veio sem manual..." style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                            </div>
                                        </div>

                                        <div>
                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Notas e ocorrências</div>
                                            @if(filled($req->admin_note) || filled($req->reason) || filled($req->justification) || filled($req->obs) || filled($req->observacao_conferencia) || filled($req->obs_entrada))
                                                <x-obs-todas :item="$req" margem="0" :plano="true" />
                                            @else
                                                <div style="font-size:13px; color:#64748b;">Nenhuma nota.</div>
                                            @endif
                                            @if($req->pedido_compra_path)
                                                <div style="margin-top:14px; font-size:13px;">
                                                    <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; font-weight:600; text-decoration:underline;">📎 Pedido de compra{{ $req->pedido_compra_nome ? ': ' . $req->pedido_compra_nome : '' }}</a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px;">
                                        <button type="button" onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='none'"
                                                style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">
                                            Confirmar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endif
                    @endforeach
                    @empty
                        <tr>
                            <td colspan="9" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                {{ $aba === 'concluidas' ? 'Nenhum item com entrada registrada ainda.' : 'Nenhum item liberado aguardando entrada.' }}
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
