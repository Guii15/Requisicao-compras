@extends('layouts.app')

@section('content')

<style>
.entr-mobile-cards { display: none; }
@media (max-width: 768px) {
    .entr-desktop-table { display: none; }
    .entr-mobile-cards  { display: block; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Entrada</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">{{ $aba === 'concluidas' ? 'Itens que já tiveram entrada registrada' : 'Itens liberados pela conferência aguardando entrada' }}</p>
    </div>

    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb;">
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
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
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

    <div class="entr-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">{{ $aba === 'concluidas' ? 'Vendedor Destino' : 'Vendedor' }}</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solic. / {{ $aba === 'concluidas' ? 'Entrada' : 'Receb.' }}</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Foto</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">{{ $aba === 'concluidas' ? 'Data da Entrada' : 'Ação' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $grupo)
                        @php $primeiroEntr = $grupo->first(); $chaveEntr = $primeiroEntr->grupo_id; @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #e5e7eb; background:#f8fafc; cursor:pointer;" onclick="toggleGrupoRequisicao('{{ $chaveEntr }}')">
                            <td colspan="6" style="padding:12px 16px; font-size:14px; color:#05018D; font-weight:700;">
                                Requisição #{{ $primeiroEntr->id }} — {{ ($aba === 'concluidas' ? $primeiroEntr->vendedor_destino : $primeiroEntr->requester_name) ?? 'Não informado' }} —
                                {{ $grupo->count() }} item{{ $grupo->count() > 1 ? 's' : '' }}
                                <span id="seta-grupo-{{ $chaveEntr }}" style="float:right; color:#9ca3af;">▾ ver itens</span>
                            </td>
                        </tr>
                    @foreach($grupo as $req)
                        <tr class="grupo-item-{{ $chaveEntr }}" style="display:none; border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">
                                {{ $req->product_name }}
                                @if($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="display:block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; width:fit-content;">⚠ Avançado Mesmo Assim</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ ($req->entrada_concluida_em ? $req->vendedor_destino : $req->requester_name) ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; color:#374151;">{{ $req->quantity }} / {{ $req->entrada_concluida_em ? $req->quantidade_entrada : $req->quantidade_recebida }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->fotosConferencia->first())
                                    <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank">
                                        <img src="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" alt="Foto da conferência"
                                             style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; display:inline-block;">
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            @if($req->entrada_concluida_em)
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $req->entrada_concluida_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            @else
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Dar Entrada
                                </button>
                            </td>
                            @endif
                        </tr>

                        @if(!$req->entrada_concluida_em)
                        <div id="modal-entrada-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Dar Entrada</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }}</p>

                                <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" id="form-entrada-{{ $req->id }}">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Vendedor Destino</label>
                                        <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Dada Entrada</label>
                                        <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida }}" min="0" required
                                               style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                    </div>

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-entrada-{{ $req->id }}').style.display='none'"
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

    <div class="entr-mobile-cards">
        @forelse($requests as $grupo)
            @php $primeiroEntrM = $grupo->first(); $chaveEntrM = $primeiroEntrM->grupo_id; @endphp
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:14px 16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06); cursor:pointer;"
                 onclick="toggleGrupoRequisicao('{{ $chaveEntrM }}')">
                <div style="font-size:14px; font-weight:700; color:#05018D;">Requisição #{{ $primeiroEntrM->id }}</div>
                <div style="font-size:13px; color:#374151; margin-top:2px;">{{ ($aba === 'concluidas' ? $primeiroEntrM->vendedor_destino : $primeiroEntrM->requester_name) ?? 'Não informado' }} — {{ $grupo->count() }} item{{ $grupo->count() > 1 ? 's' : '' }}</div>
                <div style="font-size:12px; color:#9ca3af; margin-top:4px;"><span id="seta-grupo-{{ $chaveEntrM }}" style="float:right;">▾ ver itens</span></div>
            </div>
            @foreach($grupo as $req)
            <div class="grupo-item-{{ $chaveEntrM }}" style="display:none; background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin:-6px 0 12px 12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:6px;">{{ $req->product_name }}</div>
                @if($req->status_conferencia === 'avancado_mesmo_assim')
                    <span style="display:inline-block; margin-bottom:10px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">⚠ Avançado Mesmo Assim</span>
                @endif

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px; margin-bottom:10px;">
                    <div>
                        <span style="color:#9ca3af;">{{ $req->entrada_concluida_em ? 'Vendedor Destino' : 'Vendedor' }}</span>
                        <div style="font-weight:600; color:#374151;">{{ ($req->entrada_concluida_em ? $req->vendedor_destino : $req->requester_name) ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Fornecedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $req->supplier ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Qtd Solic. / {{ $req->entrada_concluida_em ? 'Entrada' : 'Receb.' }}</span>
                        <div style="font-weight:700; color:#374151;">{{ $req->quantity }} / {{ $req->entrada_concluida_em ? $req->quantidade_entrada : $req->quantidade_recebida }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Foto</span>
                        <div>
                            @if($req->fotosConferencia->first())
                                <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank">
                                    <img src="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" alt="Foto da conferência"
                                         style="width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #e5e7eb; display:inline-block;">
                                </a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                @if($req->entrada_concluida_em)
                <div style="text-align:right; font-size:12px; color:#6b7280;">
                    Entrada em {{ $req->entrada_concluida_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
                </div>
                @else
                <div style="display:flex; justify-content:flex-end;">
                    <button onclick="document.getElementById('modal-entrada-m-{{ $req->id }}').style.display='flex'"
                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Dar Entrada
                    </button>
                </div>
                @endif
            </div>

            @if(!$req->entrada_concluida_em)
            <div id="modal-entrada-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Dar Entrada</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }}</p>

                    <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" id="form-entrada-m-{{ $req->id }}">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Vendedor Destino</label>
                            <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Dada Entrada</label>
                            <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida }}" min="0" required
                                   style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" onclick="document.getElementById('modal-entrada-m-{{ $req->id }}').style.display='none'"
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
                <p style="color:#6b7280; font-size:15px; margin:0;">{{ $aba === 'concluidas' ? 'Nenhum item com entrada registrada ainda.' : 'Nenhum item liberado aguardando entrada.' }}</p>
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
    if (seta) seta.textContent = abrindo ? '▴ ocultar itens' : '▾ ver itens';
}
</script>

@endsection
