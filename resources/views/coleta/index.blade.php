@extends('layouts.app')

@section('content')

<style>
.col-mobile-cards { display: none; }
@media (max-width: 768px) {
    .col-desktop-table { display: none; }
    .col-mobile-cards  { display: block; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Coleta</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">{{ $aba === 'coletados' ? 'Itens que já tiveram a coleta registrada' : 'Itens liberados pela conferência aguardando coleta' }}</p>
    </div>

    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
        <a href="{{ route('coleta.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'aguardando' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'aguardando' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'aguardando') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Aguardando
        </a>
        <a href="{{ route('coleta.index', ['aba' => 'coletados']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:{{ $aba === 'coletados' ? '#05018D' : 'transparent' }}; color:{{ $aba === 'coletados' ? '#fff' : '#6b7280' }};
                  border:2px solid {{ $aba === 'coletados' ? '#05018D' : 'transparent' }}; border-bottom:2px solid {{ $aba === 'coletados' ? '#05018D' : 'transparent' }};"
           @if($aba !== 'coletados') onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            Coletados
        </a>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px; margin-bottom:20px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" action="{{ route('coleta.index') }}" style="display:flex; gap:8px; flex-wrap:wrap;">
            <input type="hidden" name="aba" value="{{ $aba }}">
            <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por produto, vendedor ou fornecedor..."
                   style="flex:1; min-width:200px; border:1px solid #d1d5db; border-radius:7px; padding:9px 14px; font-size:14px; box-sizing:border-box;">
            <button type="submit" style="background:#05018D; color:#fff; padding:9px 20px; border:none; border-radius:7px; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap;">
                Buscar
            </button>
            @if($q !== '')
                <a href="{{ route('coleta.index', ['aba' => $aba]) }}" style="padding:9px 16px; border-radius:7px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:14px; white-space:nowrap;">
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
            <strong>Não foi possível registrar a coleta:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="col-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">{{ $aba === 'coletados' ? 'Coletado por' : 'Ação' }}</th>
                        @if($aba === 'coletados')
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data da Coleta</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroCol = $grupo->first();
                            $chaveCol = $primeiroCol->grupo_id;
                            $colspanCol = $aba === 'coletados' ? 6 : 5;
                            $statusColUnicos = $grupo->map(fn($r) => $r->data_coleta ? 'coletado' : 'aguardando')->unique();
                            if ($statusColUnicos->count() === 1) {
                                $statusChaveCol = $statusColUnicos->first();
                                $rotuloCol = $statusChaveCol === 'coletado' ? 'Coletado' : 'Aguardando';
                            } else {
                                $statusChaveCol = 'parcial';
                                $rotuloCol = 'Parcial';
                            }
                            $corsGrupoCol = [
                                'aguardando' => ['barra' => '#f59e0b', 'bg' => '#fef3c7', 'texto' => '#b45309'],
                                'coletado'   => ['barra' => '#16a34a', 'bg' => '#dcfce7', 'texto' => '#15803d'],
                                'parcial'    => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                            ][$statusChaveCol];
                            $produtosResumoCol = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoCol) > 60) {
                                $produtosResumoCol = mb_substr($produtosResumoCol, 0, 60) . '…';
                            }
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:0.5px solid #e5e7eb; cursor:pointer;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveCol }}')">
                            <td colspan="{{ $colspanCol }}" style="padding:0;">
                                <div style="display:flex; align-items:center; gap:12px; min-height:52px; padding:8px 16px 8px 0;">
                                    <div style="width:4px; align-self:stretch; border-radius:2px; background:{{ $corsGrupoCol['barra'] }};"></div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-size:13.5px; line-height:1.4;">
                                            <span style="color:#111827; font-weight:700;">Requisição #{{ $primeiroCol->id }}</span>
                                            <span style="color:#9ca3af; font-weight:500;"> — {{ $primeiroCol->requester_name ?? 'Não informado' }}</span>
                                        </div>
                                        <div style="font-size:12px; color:#6b7280; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoCol }}
                                        </div>
                                    </div>
                                    <span style="background:{{ $corsGrupoCol['bg'] }}; color:{{ $corsGrupoCol['texto'] }}; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">{{ $rotuloCol }}</span>
                                    <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveCol }}')"
                                            style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                        <span id="seta-grupo-{{ $chaveCol }}">Ver itens</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveCol }}" style="display:none; border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->product_name }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; color:#374151;">{{ $req->quantity }}</td>
                            @if($req->data_coleta)
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->coletado_por ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->data_coleta->format('d/m/Y') }}</td>
                            @else
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-coleta-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Registrar Coleta
                                </button>
                            </td>
                            @endif
                        </tr>

                        @if(!$req->data_coleta)
                        <div id="modal-coleta-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Registrar Coleta</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->supplier ?? 'Fornecedor não informado' }}</p>

                                <form method="POST" action="{{ route('coleta.registrar', $req) }}" id="form-coleta-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quem coletou</label>
                                        <input type="text" name="coletado_por" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Data da coleta</label>
                                        <input type="date" name="data_coleta" value="{{ now()->format('Y-m-d') }}" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-coleta-{{ $req->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
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
                            <td colspan="6" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                {{ $aba === 'coletados' ? 'Nenhum item com coleta registrada ainda.' : 'Nenhum item liberado aguardando coleta.' }}
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

    <div class="col-mobile-cards">
        @forelse($requests as $grupo)
            @php
                $primeiroColM = $grupo->first();
                $chaveColM = $primeiroColM->grupo_id;
                $statusColUnicosM = $grupo->map(fn($r) => $r->data_coleta ? 'coletado' : 'aguardando')->unique();
                if ($statusColUnicosM->count() === 1) {
                    $statusChaveColM = $statusColUnicosM->first();
                    $rotuloColM = $statusChaveColM === 'coletado' ? 'Coletado' : 'Aguardando';
                } else {
                    $statusChaveColM = 'parcial';
                    $rotuloColM = 'Parcial';
                }
                $corsGrupoColM = [
                    'aguardando' => ['barra' => '#f59e0b', 'bg' => '#fef3c7', 'texto' => '#b45309'],
                    'coletado'   => ['barra' => '#16a34a', 'bg' => '#dcfce7', 'texto' => '#15803d'],
                    'parcial'    => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                ][$statusChaveColM];
                $produtosResumoColM = $grupo->pluck('product_name')->filter()->implode(', ');
                if (mb_strlen($produtosResumoColM) > 60) {
                    $produtosResumoColM = mb_substr($produtosResumoColM, 0, 60) . '…';
                }
            @endphp
            <div style="background:#fff; border:0.5px solid #e5e7eb; border-radius:10px; margin-bottom:10px; cursor:pointer; overflow:hidden;"
                 onclick="toggleGrupoRequisicao('{{ $chaveColM }}')">
                <div style="display:flex; align-items:stretch; gap:10px;">
                    <div style="width:4px; background:{{ $corsGrupoColM['barra'] }};"></div>
                    <div style="flex:1; min-width:0; padding:12px 12px 12px 0;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div style="font-size:14px; font-weight:700; color:#111827;">Requisição #{{ $primeiroColM->id }}</div>
                            <span style="background:{{ $corsGrupoColM['bg'] }}; color:{{ $corsGrupoColM['texto'] }}; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; white-space:nowrap;">{{ $rotuloColM }}</span>
                        </div>
                        <div style="font-size:12.5px; color:#9ca3af; margin-top:2px;">{{ $primeiroColM->requester_name ?? 'Não informado' }}</div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoColM }}
                        </div>
                        <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveColM }}')"
                                style="margin-top:8px; border:1px solid #d1d5db; background:#fff; color:#374151; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">
                            <span id="seta-grupo-{{ $chaveColM }}">Ver itens</span>
                        </button>
                    </div>
                </div>
            </div>
            @foreach($grupo as $req)
            <div class="grupo-item-{{ $chaveColM }}" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:-6px 0 12px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:6px;">{{ $req->product_name }}</div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px; margin-bottom:10px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->requester_name ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Qtd</span>
                        <div style="font-weight:700; color:#374151;">{{ $req->quantity }}</div>
                    </div>
                    @if($req->data_coleta)
                    <div>
                        <span style="color:#9ca3af;">Coletado por</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->coletado_por ?? '—' }}</div>
                    </div>
                    @endif
                </div>

                @if($req->data_coleta)
                <div style="text-align:right; font-size:12px; color:#6b7280;">
                    Coletado em {{ $req->data_coleta->format('d/m/Y') }}
                </div>
                @else
                <div style="display:flex; justify-content:flex-end;">
                    <button onclick="document.getElementById('modal-coleta-m-{{ $req->id }}').style.display='flex'"
                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Registrar Coleta
                    </button>
                </div>
                @endif
            </div>

            @if(!$req->data_coleta)
            <div id="modal-coleta-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Registrar Coleta</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->supplier ?? 'Fornecedor não informado' }}</p>

                    <form method="POST" action="{{ route('coleta.registrar', $req) }}" id="form-coleta-m-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quem coletou</label>
                            <input type="text" name="coletado_por" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Data da coleta</label>
                            <input type="date" name="data_coleta" value="{{ now()->format('Y-m-d') }}" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" onclick="document.getElementById('modal-coleta-m-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit"
                                    style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Confirmar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            @endforeach
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">{{ $aba === 'coletados' ? 'Nenhum item com coleta registrada ainda.' : 'Nenhum item liberado aguardando coleta.' }}</p>
            </div>
        @endforelse
        @if($requests->hasPages())
            <div style="padding:16px 4px; display:flex; justify-content:center;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>

</div>

<script>
function toggleGrupoRequisicao(chave) {
    var linhas = document.querySelectorAll('.grupo-item-' + CSS.escape(chave));
    var seta = document.getElementById('seta-grupo-' + chave);
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? (linha.tagName === 'TR' ? 'table-row' : 'block') : 'none';
    });
    if (seta) seta.textContent = abrindo ? 'Ocultar itens' : 'Ver itens';
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
