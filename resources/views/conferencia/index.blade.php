@extends('layouts.app')

@section('content')

@php $podeConferir = Auth::user()->isConferente(); @endphp

<style>
.conf-mobile-cards { display: none; }
@media (max-width: 768px) {
    .conf-desktop-table { display: none; }
    .conf-mobile-cards  { display: block; }
}
</style>

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
    <div class="conf-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solicitada</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Tipo de Entrega</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Aprovado em</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">{{ $aba === 'conferidos' ? 'Resultado' : 'Ação' }}</th>
                        @if($aba === 'conferidos')
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Conferido por</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php
                            $primeiroConf = $grupo->first();
                            $chaveConf = $primeiroConf->grupo_id;
                            $colspanConf = $aba === 'conferidos' ? 8 : 7;
                            $statusConfUnicos = $grupo->map(fn($r) => $r->status_conferencia ?? 'aguardando')->unique();
                            if ($statusConfUnicos->count() === 1) {
                                $statusChaveConf = $statusConfUnicos->first();
                                $rotuloConf = ['aguardando' => 'Aguardando', 'conferido_ok' => 'OK', 'divergente' => 'Divergente', 'avancado_mesmo_assim' => 'Avançado', 'cancelado' => 'Cancelado', 'legado' => 'Legado'][$statusChaveConf] ?? ucfirst($statusChaveConf);
                            } else {
                                $statusChaveConf = 'parcial';
                                $rotuloConf = 'Parcial';
                            }
                            $corsGrupoConf = [
                                'aguardando'           => ['barra' => '#f59e0b', 'bg' => '#fef3c7', 'texto' => '#b45309'],
                                'conferido_ok'          => ['barra' => '#16a34a', 'bg' => '#dcfce7', 'texto' => '#15803d'],
                                'divergente'            => ['barra' => '#dc2626', 'bg' => '#fee2e2', 'texto' => '#b91c1c'],
                                'avancado_mesmo_assim'  => ['barra' => '#2563eb', 'bg' => '#dbeafe', 'texto' => '#1d4ed8'],
                                'cancelado'             => ['barra' => '#dc2626', 'bg' => '#fee2e2', 'texto' => '#b91c1c'],
                                'legado'                => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                                'parcial'               => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                            ][$statusChaveConf];
                            $produtosResumoConf = $grupo->pluck('product_name')->filter()->implode(', ');
                            if (mb_strlen($produtosResumoConf) > 60) {
                                $produtosResumoConf = mb_substr($produtosResumoConf, 0, 60) . '…';
                            }
                        @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:0.5px solid #e5e7eb; cursor:pointer;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoRequisicao('{{ $chaveConf }}')">
                            <td colspan="{{ $colspanConf }}" style="padding:0;">
                                <div style="display:flex; align-items:center; gap:12px; min-height:52px; padding:8px 16px 8px 0;">
                                    <div style="width:4px; align-self:stretch; border-radius:2px; background:{{ $corsGrupoConf['barra'] }};"></div>
                                    <div style="flex:1; min-width:0;">
                                        <div style="font-size:13.5px; line-height:1.4;">
                                            <span style="color:#111827; font-weight:700;">Requisição #{{ $primeiroConf->id }}</span>
                                            <span style="color:#9ca3af; font-weight:500;"> — {{ $primeiroConf->requester_name ?? 'Não informado' }}</span>
                                        </div>
                                        <div style="font-size:12px; color:#6b7280; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoConf }}
                                        </div>
                                    </div>
                                    <span style="background:{{ $corsGrupoConf['bg'] }}; color:{{ $corsGrupoConf['texto'] }}; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">{{ $rotuloConf }}</span>
                                    <button type="button" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveConf }}')"
                                            style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                        <span id="seta-grupo-{{ $chaveConf }}">Ver itens</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveConf }}" style="display:none; border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">
                                {{ $req->product_name }}
                                @if($req->pedido_compra_path)
                                    <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="display:block; font-size:11px; color:#05018D; text-decoration:underline; margin-top:2px;">📎 Pedido de compra</a>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#374151;">{{ $req->quantity }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->tipo_entrega === 'entrega_direta')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Venda Casada</span>
                                @else
                                    <span style="background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Estoque</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->approved_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->status_conferencia === 'conferido_ok')
                                    <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                                @elseif($req->status_conferencia === 'divergente')
                                    <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                                @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                                @elseif($req->status_conferencia === 'cancelado')
                                    <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Cancelado</span>
                                @elseif($podeConferir)
                                    <button onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='flex'"
                                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                        Conferir
                                    </button>
                                @else
                                    <span style="color:#9ca3af; font-size:12px;">Aguardando conferência</span>
                                @endif
                            </td>
                            @if($aba === 'conferidos')
                            <td style="padding:12px 16px; font-size:13px; color:#374151;">{{ $req->conferente->name ?? '—' }}</td>
                            @endif
                        </tr>

                        @if($req->obs)
                        <tr class="grupo-item-{{ $chaveConf }}" style="display:none; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
                            <td colspan="8" style="padding:12px 16px;">
                                <div style="padding:10px 12px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
                                    <span style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Obs (Conferente):</span>
                                    <div style="margin-top:4px; font-size:13px; color:#166534; line-height:1.5;">{{ $req->obs }}</div>
                                </div>
                            </td>
                        </tr>
                        @endif

                        @if($req->obs_entrada)
                        <tr class="grupo-item-{{ $chaveConf }}" style="display:none; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
                            <td colspan="8" style="padding:12px 16px;"><x-obs-entrada :item="$req" margem="0" /></td>
                        </tr>
                        @endif

                        @if($req->admin_note)
                        <tr class="grupo-item-{{ $chaveConf }}" style="display:none; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
                            <td colspan="8" style="padding:12px 16px;">
                                <div style="padding:10px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
                                    <span style="font-size:11px; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Obs (Admin):</span>
                                    <div style="margin-top:4px; font-size:13px; color:#1e3a8a; line-height:1.5; white-space:pre-line;">{{ $req->admin_note }}</div>
                                </div>
                            </td>
                        </tr>
                        @endif

                        @if($req->status_conferencia === null && $podeConferir)
                        <div id="modal-conferir-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Conferir Item</h3>
                                <p style="margin:0 0 8px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>
                                @if($req->pedido_compra_path)
                                    <p style="margin:0 0 16px;"><a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="font-size:13px; color:#05018D; font-weight:600; text-decoration:underline;">📎 Ver pedido de compra</a></p>
                                @endif

                                <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Recebida</label>
                                        <input type="number" name="quantidade_recebida" id="campo-qtd-{{ $req->id }}" value="{{ $req->quantity }}" min="0" required
                                               oninput="verificaDivergencia{{ $req->id }}(this.value)"
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                        <div id="aviso-divergencia-{{ $req->id }}" style="display:none; margin-top:6px; font-size:12px; color:#d97706; font-weight:600;">
                                            ⚠️ Diferente da quantidade solicitada (pedido: {{ $req->quantity }})
                                        </div>
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Foto</label>
                                        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Resultado</label>
                                        <select name="resultado" id="campo-resultado-{{ $req->id }}" required onchange="atualizaResultado{{ $req->id }}(this.value)"
                                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                            <option value="ok">OK</option>
                                            <option value="divergente">Divergente</option>
                                        </select>
                                    </div>

                                    <div id="campo-observacao-{{ $req->id }}" style="display:none; margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação</label>
                                        <textarea name="observacao_conferencia" rows="3"
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Obs (geral)</label>
                                        <textarea name="obs" rows="2"
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                    </div>

                                    <input type="hidden" name="acao" id="campo-acao-{{ $req->id }}" value="salvar">

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-conferir-{{ $req->id }}').style.display='none'"
                                                style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                            Cancelar
                                        </button>
                                        <button type="submit" onclick="document.getElementById('campo-acao-{{ $req->id }}').value='salvar'"
                                                style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Salvar
                                        </button>
                                        @if($req->tipo_entrega === 'entrega_direta')
                                        <button type="submit" id="btn-avancar-{{ $req->id }}" onclick="document.getElementById('campo-acao-{{ $req->id }}').value='avancar_mesmo_assim'"
                                                style="display:none; padding:9px 24px; border-radius:8px; background:#d97706; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                            Avançar Mesmo Assim
                                        </button>
                                        @endif
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
                            var divergiu = valor !== '' && parseInt(valor, 10) !== original;
                            document.getElementById('aviso-divergencia-{{ $req->id }}').style.display = divergiu ? 'block' : 'none';
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
                            <td colspan="{{ $aba === 'conferidos' ? 8 : 7 }}" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
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

    <div class="conf-mobile-cards">
        @forelse($requests as $grupo)
            @php
                $primeiroConfM = $grupo->first();
                $chaveConfM = $primeiroConfM->grupo_id;
                $statusConfUnicosM = $grupo->map(fn($r) => $r->status_conferencia ?? 'aguardando')->unique();
                if ($statusConfUnicosM->count() === 1) {
                    $statusChaveConfM = $statusConfUnicosM->first();
                    $rotuloConfM = ['aguardando' => 'Aguardando', 'conferido_ok' => 'OK', 'divergente' => 'Divergente', 'avancado_mesmo_assim' => 'Avançado', 'cancelado' => 'Cancelado', 'legado' => 'Legado'][$statusChaveConfM] ?? ucfirst($statusChaveConfM);
                } else {
                    $statusChaveConfM = 'parcial';
                    $rotuloConfM = 'Parcial';
                }
                $corsGrupoConfM = [
                    'aguardando'           => ['barra' => '#f59e0b', 'bg' => '#fef3c7', 'texto' => '#b45309'],
                    'conferido_ok'          => ['barra' => '#16a34a', 'bg' => '#dcfce7', 'texto' => '#15803d'],
                    'divergente'            => ['barra' => '#dc2626', 'bg' => '#fee2e2', 'texto' => '#b91c1c'],
                    'avancado_mesmo_assim'  => ['barra' => '#2563eb', 'bg' => '#dbeafe', 'texto' => '#1d4ed8'],
                    'cancelado'             => ['barra' => '#dc2626', 'bg' => '#fee2e2', 'texto' => '#b91c1c'],
                    'legado'                => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                    'parcial'               => ['barra' => '#64748b', 'bg' => '#e2e8f0', 'texto' => '#475569'],
                ][$statusChaveConfM];
                $produtosResumoConfM = $grupo->pluck('product_name')->filter()->implode(', ');
                if (mb_strlen($produtosResumoConfM) > 60) {
                    $produtosResumoConfM = mb_substr($produtosResumoConfM, 0, 60) . '…';
                }
            @endphp
            <div style="background:#fff; border:0.5px solid #e5e7eb; border-radius:10px; margin-bottom:10px; cursor:pointer; overflow:hidden;"
                 onclick="toggleGrupoRequisicao('{{ $chaveConfM }}')">
                <div style="display:flex; align-items:stretch; gap:10px;">
                    <div style="width:4px; background:{{ $corsGrupoConfM['barra'] }};"></div>
                    <div style="flex:1; min-width:0; padding:12px 12px 12px 0;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                            <div style="font-size:14px; font-weight:700; color:#111827;">Requisição #{{ $primeiroConfM->id }}</div>
                            <span style="background:{{ $corsGrupoConfM['bg'] }}; color:{{ $corsGrupoConfM['texto'] }}; padding:3px 10px; border-radius:20px; font-size:11.5px; font-weight:700; white-space:nowrap;">{{ $rotuloConfM }}</span>
                        </div>
                        <div style="font-size:12.5px; color:#9ca3af; margin-top:2px;">{{ $primeiroConfM->requester_name ?? 'Não informado' }}</div>
                        <div style="font-size:12px; color:#6b7280; margin-top:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoConfM }}
                        </div>
                        <button type="button" class="m-botao" onclick="event.stopPropagation(); toggleGrupoRequisicao('{{ $chaveConfM }}')"
                                style="margin-top:8px; border:1px solid #d1d5db; background:#fff; color:#374151; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer;">
                            <span id="seta-grupo-{{ $chaveConfM }}">Ver itens</span>
                        </button>
                    </div>
                </div>
            </div>
            @foreach($grupo as $req)
            <div class="grupo-item-{{ $chaveConfM }}" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:-6px 0 12px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div>
                        <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $req->product_name }}</div>
                        @if($req->pedido_compra_path)
                            <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="display:block; font-size:11px; color:#05018D; text-decoration:underline; margin-top:2px;">📎 Pedido de compra</a>
                        @endif
                    </div>
                    @if($req->tipo_entrega === 'entrega_direta')
                        <span style="background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Venda Casada</span>
                    @else
                        <span style="background:#e0e7ff; color:#3730a3; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Estoque</span>
                    @endif
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px; margin-bottom:12px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->requester_name ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Qtd Solicitada</span>
                        <div style="font-weight:700; font-size:15px; color:#374151;">{{ $req->quantity }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Aprovado em</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->approved_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') ?? '—' }}</div>
                    </div>
                </div>

                @if($aba === 'conferidos')
                    <div style="font-size:12px; color:#9ca3af; margin-bottom:8px;">Conferido por: <strong style="color:#374151;">{{ $req->conferente->name ?? '—' }}</strong></div>
                @endif

                @if($req->obs)
                <div style="margin-bottom:12px; padding:10px 12px; background:#f0fdf4; border:1px solid #86efac; border-radius:8px;">
                    <span style="font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase;">Obs (Conferente):</span>
                    <div style="margin-top:4px; font-size:13px; color:#166534; line-height:1.5;">{{ $req->obs }}</div>
                </div>
                @endif

                <x-obs-entrada :item="$req" />

                @if($req->admin_note)
                <div style="margin-bottom:12px; padding:10px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;">
                    <span style="font-size:11px; font-weight:700; color:#1d4ed8; text-transform:uppercase;">Obs (Admin):</span>
                    <div style="margin-top:4px; font-size:13px; color:#1e3a8a; line-height:1.5; white-space:pre-line;">{{ $req->admin_note }}</div>
                </div>
                @endif

                <div style="display:flex; justify-content:flex-end;">
                    @if($req->status_conferencia === 'conferido_ok')
                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                    @elseif($req->status_conferencia === 'divergente')
                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                    @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                        <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                    @elseif($req->status_conferencia === 'cancelado')
                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Cancelado</span>
                    @elseif($podeConferir)
                        <button class="m-botao" onclick="document.getElementById('modal-conferir-m-{{ $req->id }}').style.display='flex'"
                                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                            Conferir
                        </button>
                    @else
                        <span style="color:#9ca3af; font-size:12px;">Aguardando conferência</span>
                    @endif
                </div>

            </div>

            @if($req->status_conferencia === null && $podeConferir)
            <div id="modal-conferir-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div class="m-modal-caixa" style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Conferir Item</h3>
                    <p style="margin:0 0 8px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>
                    @if($req->pedido_compra_path)
                        <p style="margin:0 0 16px;"><a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="font-size:13px; color:#05018D; font-weight:600; text-decoration:underline;">📎 Ver pedido de compra</a></p>
                    @endif

                    <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-m-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Recebida</label>
                            <input type="number" name="quantidade_recebida" id="campo-qtd-m-{{ $req->id }}" value="{{ $req->quantity }}" min="0" required
                                   oninput="verificaDivergenciaMobile{{ $req->id }}(this.value)"
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                            <div id="aviso-divergencia-m-{{ $req->id }}" style="display:none; margin-top:6px; font-size:12px; color:#d97706; font-weight:600;">
                                ⚠️ Diferente da quantidade solicitada (pedido: {{ $req->quantity }})
                            </div>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Foto</label>
                            <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Resultado</label>
                            <select name="resultado" id="campo-resultado-m-{{ $req->id }}" required onchange="atualizaResultadoMobile{{ $req->id }}(this.value)"
                                    style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                <option value="ok">OK</option>
                                <option value="divergente">Divergente</option>
                            </select>
                        </div>

                        <div id="campo-observacao-m-{{ $req->id }}" style="display:none; margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação</label>
                            <textarea name="observacao_conferencia" rows="3"
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Obs (geral)</label>
                            <textarea name="obs" rows="2"
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                        </div>

                        <input type="hidden" name="acao" id="campo-acao-m-{{ $req->id }}" value="salvar">

                        <div class="m-modal-acoes" style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" onclick="document.getElementById('modal-conferir-m-{{ $req->id }}').style.display='none'"
                                    style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">
                                Cancelar
                            </button>
                            <button type="submit" onclick="document.getElementById('campo-acao-m-{{ $req->id }}').value='salvar'"
                                    style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Salvar
                            </button>
                            @if($req->tipo_entrega === 'entrega_direta')
                            <button type="submit" id="btn-avancar-m-{{ $req->id }}" onclick="document.getElementById('campo-acao-m-{{ $req->id }}').value='avancar_mesmo_assim'"
                                    style="display:none; padding:9px 24px; border-radius:8px; background:#d97706; color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
                                Avançar Mesmo Assim
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <script>
            function atualizaResultadoMobile{{ $req->id }}(valor) {
                document.getElementById('campo-observacao-m-{{ $req->id }}').style.display = valor === 'divergente' ? 'block' : 'none';
                var btnAvancar = document.getElementById('btn-avancar-m-{{ $req->id }}');
                if (btnAvancar) {
                    btnAvancar.style.display = valor === 'divergente' ? 'inline-block' : 'none';
                }
            }
            function verificaDivergenciaMobile{{ $req->id }}(valor) {
                var original = {{ $req->quantity }};
                var divergiu = valor !== '' && parseInt(valor, 10) !== original;
                document.getElementById('aviso-divergencia-m-{{ $req->id }}').style.display = divergiu ? 'block' : 'none';
                if (divergiu) {
                    document.getElementById('campo-resultado-m-{{ $req->id }}').value = 'divergente';
                    atualizaResultadoMobile{{ $req->id }}('divergente');
                }
            }
            </script>
            @endif
            @endforeach
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">{{ $aba === 'conferidos' ? 'Nenhuma requisição conferida ainda' : 'Nenhuma requisição aguardando conferência' }}</p>
            </div>
        @endforelse
        @if($requests->hasPages())
            <div style="padding:16px 4px; display:flex; justify-content:center;">
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
                          background:{{ $resultado === $valor ? 'linear-gradient(135deg,#05018D,#1d4ed8)' : '#f3f4f6' }};
                          color:{{ $resultado === $valor ? '#fff' : '#374151' }};
                          border:{{ $resultado === $valor ? '2px solid #05018D' : '2px solid transparent' }};
                          box-shadow:{{ $resultado === $valor ? '0 4px 12px rgba(5, 1, 141, 0.2)' : 'none' }};">
                    {{ $config['emoji'] }} {{ $config['label'] }}
                </a>
            @endforeach
        </div>

        <div class="m-desktop" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                            <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                            <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                            <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd</th>
                            <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Status</th>
                            <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data Coleta</th>
                            <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $grupo)
                            @foreach($grupo as $req)
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:12px 16px; font-size:14px; color:#111827;">{{ $req->requester_name ?? '—' }}</td>
                                <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->product_name }}</td>
                                <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#374151;">{{ $req->quantity }}</td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($req->status_coleta === 'coletado')
                                        <span style="background:#dcfce7; color:#16a34a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Coletado</span>
                                    @elseif($req->status_coleta === 'atraso')
                                        <span style="background:#fee2e2; color:#dc2626; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Atraso</span>
                                    @else
                                        <span style="background:#fef3c7; color:#b45309; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Aguardando</span>
                                    @endif
                                </td>
                                <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">
                                    {{ $req->data_coleta?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td style="padding:12px 16px; text-align:center;">
                                    @if($req->status_coleta === 'coletado')
                                        <span style="color:#999; font-size:12px;">✓ Concluído</span>
                                    @elseif($podeConferir)
                                        <button type="button" onclick="abrirModalColeta({{ $req->id }})"
                                                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                            Coletar Agora
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                    {{ $resultado === 'coletado' ? 'Nenhuma coleta registrada.' : 'Nenhuma requisição aguardando coleta.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="m-cards">
            @forelse($requests as $grupo)
                @foreach($grupo as $req)
                    <x-mobile-card :titulo="$req->product_name" :campos="[
                        'Requisição' => '#' . $req->id,
                        'Vendedor' => $req->requester_name,
                        'Fornecedor' => $req->supplier,
                        'Quantidade' => $req->quantity,
                        'Data da coleta' => $req->data_coleta?->format('d/m/Y H:i'),
                    ]">
                        <x-slot:badge>
                            @if($req->status_coleta === 'coletado')
                                <span style="background:#dcfce7; color:#16a34a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">Coletado</span>
                            @elseif($req->status_coleta === 'atraso')
                                <span style="background:#fee2e2; color:#dc2626; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">Atraso</span>
                            @else
                                <span style="background:#fef3c7; color:#b45309; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; white-space:nowrap;">Aguardando</span>
                            @endif
                        </x-slot:badge>
                        @if($req->status_coleta !== 'coletado' && $podeConferir)
                            <x-slot:acao>
                                <button type="button" onclick="abrirModalColeta({{ $req->id }})"
                                        style="background:#05018D; color:#fff; border:none; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer;">
                                    Coletar Agora
                                </button>
                            </x-slot:acao>
                        @endif
                    </x-mobile-card>
                @endforeach
            @empty
                <div style="text-align:center; padding:48px 16px; color:#9ca3af; font-size:15px;">
                    {{ $resultado === 'coletado' ? 'Nenhuma coleta registrada.' : 'Nenhuma requisição aguardando coleta.' }}
                </div>
            @endforelse
        </div>

        @forelse($requests as $grupo)
            @foreach($grupo as $req)
            @if($podeConferir && $req->status_coleta !== 'coletado')
            <div id="modal-coleta-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:2000; align-items:center; justify-content:center;">
                <div class="m-modal-caixa" style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:480px; margin:16px; box-shadow:0 20px 25px rgba(0,0,0,0.15);">
                    <h3 style="margin:0 0 8px; font-size:18px; font-weight:700; color:#05018D;">Registrar Coleta</h3>
                    <p style="margin:0 0 20px; font-size:14px; color:#6b7280;">Requisição #{{ $req->id }} - {{ $req->product_name }}</p>

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
                                    style="padding:10px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#1d4ed8); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">
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
        linha.style.display = abrindo ? (linha.tagName === 'TR' ? 'table-row' : 'block') : 'none';
    });
    // Desktop e mobile têm um rótulo cada com o mesmo id; getElementById só achava o do desktop.
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
