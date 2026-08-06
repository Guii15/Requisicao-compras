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
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">{{ $aba === 'conferidos' ? 'Requisições já conferidas' : 'Requisições aprovadas aguardando conferência' }}</p>
    </div>

    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
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
    </div>

    @if($aba === 'conferidos')
        <div style="display:flex; gap:8px; margin-bottom:20px;">
            @foreach(['todos' => 'Todos', 'ok' => 'OK', 'divergente' => 'Divergente'] as $valor => $rotulo)
                <a href="{{ route('conferencia.index', array_filter(['aba' => 'conferidos', 'resultado' => $valor === 'todos' ? null : $valor, 'q' => $q !== '' ? $q : null])) }}"
                   style="padding:5px 14px; font-size:13px; font-weight:600; text-decoration:none; border-radius:20px;
                          background:{{ $resultado === $valor ? '#05018D' : '#f3f4f6' }}; color:{{ $resultado === $valor ? '#fff' : '#6b7280' }};">
                    {{ $rotulo }}
                </a>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ route('conferencia.index') }}" style="display:flex; gap:8px; margin-bottom:20px;">
        <input type="hidden" name="aba" value="{{ $aba }}">
        @if($aba === 'conferidos' && $resultado !== 'todos')
            <input type="hidden" name="resultado" value="{{ $resultado }}">
        @endif
        <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por produto, vendedor ou fornecedor..."
               style="flex:1; border:1.5px solid #e5e7eb; border-radius:8px; padding:9px 14px; font-size:14px; box-sizing:border-box;">
        <button type="submit" style="padding:9px 20px; border-radius:8px; background:#05018D; color:#fff; border:none; font-size:14px; font-weight:600; cursor:pointer; white-space:nowrap;">
            Buscar
        </button>
        @if($q !== '')
            <a href="{{ route('conferencia.index', array_filter(['aba' => $aba, 'resultado' => $resultado !== 'todos' ? $resultado : null])) }}"
               style="padding:9px 16px; border-radius:8px; border:1.5px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:14px; white-space:nowrap;">
                Limpar
            </a>
        @endif
    </form>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
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
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Data</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">{{ $aba === 'conferidos' ? 'Resultado' : 'Ação' }}</th>
                        @if($aba === 'conferidos')
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Conferido por</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->product_name }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#374151;">{{ $req->quantity }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->tipo_entrega === 'entrega_direta')
                                    <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Venda Casada</span>
                                @else
                                    <span style="background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Estoque</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($aba === 'conferidos')
                                    @if($req->status_conferencia === 'conferido_ok')
                                        <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                                    @elseif($req->status_conferencia === 'divergente')
                                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                                    @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                                        <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                                    @elseif($req->status_conferencia === 'cancelado')
                                        <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Cancelado</span>
                                    @endif
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

                        @if($aba === 'aguardando' && $podeConferir)
                        <div id="modal-conferir-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Conferir Item</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                                <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-{{ $req->id }}">
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
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">

                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                    <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $req->product_name }}</div>
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
                        <span style="color:#9ca3af;">Data</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
                    </div>
                </div>

                @if($aba === 'conferidos')
                    <div style="font-size:12px; color:#9ca3af; margin-bottom:8px;">Conferido por: <strong style="color:#374151;">{{ $req->conferente->name ?? '—' }}</strong></div>
                @endif

                <div style="display:flex; justify-content:flex-end;">
                    @if($aba === 'conferidos')
                        @if($req->status_conferencia === 'conferido_ok')
                            <span style="background:#dcfce7; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">OK</span>
                        @elseif($req->status_conferencia === 'divergente')
                            <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Divergente</span>
                        @elseif($req->status_conferencia === 'avancado_mesmo_assim')
                            <span style="background:#dbeafe; color:#2563eb; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Avançado Mesmo Assim</span>
                        @elseif($req->status_conferencia === 'cancelado')
                            <span style="background:#fee2e2; color:#dc2626; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">Cancelado</span>
                        @endif
                    @elseif($podeConferir)
                        <button onclick="document.getElementById('modal-conferir-m-{{ $req->id }}').style.display='flex'"
                                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                            Conferir
                        </button>
                    @else
                        <span style="color:#9ca3af; font-size:12px;">Aguardando conferência</span>
                    @endif
                </div>

            </div>

            @if($aba === 'aguardando' && $podeConferir)
            <div id="modal-conferir-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Conferir Item</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                    <form method="POST" action="{{ route('conferencia.conferir', $req) }}" enctype="multipart/form-data" id="form-conferir-m-{{ $req->id }}">
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

                        <input type="hidden" name="acao" id="campo-acao-m-{{ $req->id }}" value="salvar">

                        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
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

</div>

@endsection
