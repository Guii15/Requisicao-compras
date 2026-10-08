@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Pendências</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Itens divergentes de estoque aguardando sua decisão</p>
    </div>

    @include('admin._abas')

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
            <strong>Não foi possível resolver a pendência:</strong>
            <ul style="margin:6px 0 0; padding-left:18px;">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Mesmo desenho das outras listagens: uma linha por item divergente; ao abrir, o cartão do item. --}}
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                        @php $thPend = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                        <th style="{{ $thPend }} text-align:left;">Nº</th>
                        <th style="{{ $thPend }} text-align:left;">Vendedor</th>
                        <th style="{{ $thPend }} text-align:left;">Produto</th>
                        <th style="{{ $thPend }} text-align:left;">Fornecedor</th>
                        <th style="{{ $thPend }} text-align:right;">Pedido / Recebido</th>
                        <th style="{{ $thPend }} text-align:left;">Conferido por</th>
                        <th style="{{ $thPend }} text-align:left;">Situação</th>
                        <th style="{{ $thPend }} text-align:right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @php $tdPend = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;'; @endphp
                        <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleItemPendencia({{ $req->id }})">
                            <td class="lr-num" style="{{ $tdPend }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $req->id }}</td>
                            <td data-rotulo="Vendedor" style="{{ $tdPend }}">{{ $req->requester_name ?? '—' }}</td>
                            <td class="lr-larga" data-rotulo="Produto" style="{{ $tdPend }} max-width:380px;">
                                <div style="font-weight:600; color:#111827;">{{ $req->product_name }}</div>
                                @if($req->observacao_conferencia)
                                    <div style="font-size:12px; color:#7f1d1d; margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $req->observacao_conferencia }}</div>
                                @endif
                            </td>
                            <td data-rotulo="Fornecedor" style="{{ $tdPend }}">{{ $req->supplier ?? '—' }}</td>
                            <td data-rotulo="Pedido / recebido" style="{{ $tdPend }} text-align:right; white-space:nowrap; font-variant-numeric:tabular-nums;">{{ $req->quantity }} / <strong style="color:#b8301a;">{{ $req->quantidade_recebida }}</strong></td>
                            <td data-rotulo="Conferido por" style="{{ $tdPend }} font-size:13px;">{{ $req->conferente->name ?? '—' }}</td>
                            <td data-rotulo="Situação" style="{{ $tdPend }}">
                                <span style="background:#fff; color:#b8301a; border:1px solid #b8301a; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Divergente</span>
                            </td>
                            <td class="lr-acao" style="{{ $tdPend }} text-align:right; white-space:nowrap;">
                                <button type="button" onclick="event.stopPropagation(); toggleItemPendencia({{ $req->id }})"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap; margin-right:6px;">
                                    <span id="seta-pend-{{ $req->id }}">Ver item</span>
                                </button>
                                <button type="button" onclick="event.stopPropagation(); document.getElementById('modal-resolver-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:1px solid #05018D; border-radius:9999px; padding:7px 18px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    Resolver
                                </button>
                            </td>
                        </tr>
                        <tr id="item-pend-{{ $req->id }}" class="grupo-item-pend" style="display:none; background:#f7f8fa;">
                            <td colspan="8" style="padding:14px 20px 18px; border-bottom:1px solid #e5e7eb;">
                                <x-item-requisicao :req="$req">
                                    <span style="align-self:center; font-size:12.5px; color:#6b7280;">Pedido: <strong style="color:#111827;">{{ $req->quantity }}</strong> · Recebido: <strong style="color:#b8301a;">{{ $req->quantidade_recebida }}</strong></span>
                                </x-item-requisicao>
                            </td>
                        </tr>

                        {{-- Janela Resolver: mesmo modelo largo das outras janelas, com as notas ao lado. --}}
                        <div id="modal-resolver-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(15, 23, 42, 0.6); backdrop-filter:blur(3px); z-index:1000; align-items:center; justify-content:center; padding:16px;">
                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; width:100%; max-width:860px; max-height:94vh; display:flex; flex-direction:column; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); overflow:hidden; text-align:left;">
                                <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc;">
                                    <div>
                                        <h3 style="margin:0; font-family:'Playfair Display', Georgia, serif; font-size:22px; font-weight:400; color:#0f172a; letter-spacing:0.01em;">Resolver Pendência</h3>
                                        <div style="margin-top:4px; font-size:13px; color:#64748b; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                            <span style="font-weight:600; color:#1e293b;">{{ $req->product_name }}</span>
                                            <span>·</span>
                                            <span>Solicitante: <strong>{{ $req->requester_name }}</strong></span>
                                            <span>·</span>
                                            <span>Pedido / recebido: <strong>{{ $req->quantity }} / {{ $req->quantidade_recebida }}</strong></span>
                                        </div>
                                    </div>
                                    <button type="button" onclick="document.getElementById('modal-resolver-{{ $req->id }}').style.display='none'" aria-label="Fechar"
                                            style="border:none; background:transparent; color:#94a3b8; font-size:18px; cursor:pointer; padding:4px 8px; border-radius:4px; line-height:1;">✕</button>
                                </div>

                                <form method="POST" action="{{ route('pendencias.resolver', $req) }}" id="form-resolver-{{ $req->id }}" onsubmit="return protegerEnvioDuplo(this)" style="display:flex; flex-direction:column; flex:1; min-height:0; overflow:hidden; margin:0;">
                                    @csrf
                                    @method('PATCH')

                                    <div class="jan-corpo" style="padding:22px 24px; display:grid; grid-template-columns:1.1fr 1fr; gap:28px; flex:1; min-height:0; overflow-y:auto;">
                                        <div>
                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Decisão</div>
                                            <div style="margin-bottom:14px;">
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Decisão</label>
                                                <select name="decisao" required onchange="atualizaObservacaoPendencia{{ $req->id }}(this.value)" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; background-color:#fff;">
                                                    <option value="aceitar">Aceitar Mesmo Assim</option>
                                                    <option value="cancelar">Cancelar Item</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label style="display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:5px;">Observação <span id="obs-obrigatoria-{{ $req->id }}" style="display:none; color:#ef4444;">*</span></label>
                                                <textarea name="observacao" rows="4" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 12px; font-size:13.5px; color:#0f172a; outline:none; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                            </div>
                                        </div>
                                        <div>
                                            <div style="font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:14px; padding-bottom:6px; border-bottom:1px solid #f1f5f9;">Notas e ocorrências</div>
                                            <x-obs-todas :item="$req" margem="14px" :plano="true" />
                                            <div style="font-size:13px; color:#334155;">
                                                @if($req->conferente)<div style="margin-bottom:6px;">Conferido por <strong>{{ $req->conferente->name }}</strong></div>@endif
                                                <x-fotos-conferencia :item="$req" modo="links" vazio="Sem foto da conferência." />
                                            </div>
                                        </div>
                                    </div>

                                    <div style="padding:16px 24px; border-top:1px solid #e2e8f0; background:#f8fafc; display:flex; justify-content:flex-end; gap:10px;">
                                        <button type="button" onclick="document.getElementById('modal-resolver-{{ $req->id }}').style.display='none'"
                                                style="padding:8px 18px; border-radius:6px; border:1px solid #cbd5e1; background:#ffffff; color:#475569; font-size:13px; font-weight:600; cursor:pointer;">Cancelar</button>
                                        <button type="submit"
                                                style="padding:8px 22px; border-radius:6px; background:#05018D; color:#ffffff; font-size:13px; font-weight:600; border:none; cursor:pointer;">Confirmar</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                        function atualizaObservacaoPendencia{{ $req->id }}(valor) {
                            var form = document.getElementById('form-resolver-{{ $req->id }}');
                            var textarea = form.querySelector('textarea[name="observacao"]');
                            var marcador = document.getElementById('obs-obrigatoria-{{ $req->id }}');
                            if (valor === 'cancelar') {
                                textarea.setAttribute('required', 'required');
                                marcador.style.display = 'inline';
                            } else {
                                textarea.removeAttribute('required');
                                marcador.style.display = 'none';
                            }
                        }
                        </script>
                    @empty
                        <tr>
                            <td colspan="8" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma pendência no momento.
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

</div>

<script>
function toggleItemPendencia(id) {
    var linha = document.getElementById('item-pend-' + id);
    var abrindo = linha.style.display === 'none';
    linha.style.display = abrindo ? '' : 'none'; // '' devolve ao CSS (linha no PC, bloco no celular)
    document.getElementById('seta-pend-' + id).textContent = abrindo ? 'Ocultar item' : 'Ver item';
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
