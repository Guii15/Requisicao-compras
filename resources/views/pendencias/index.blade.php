@extends('layouts.app')

@section('content')

<style>
.pend-mobile-cards { display: none; }
@media (max-width: 768px) {
    .pend-desktop-table { display: none; }
    .pend-mobile-cards  { display: block; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Pendências</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Itens divergentes de estoque aguardando sua decisão</p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">
            ✓ {{ session('success') }}
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

    <div class="pend-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solic. / Receb.</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Observação do Conferente</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Foto</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $req->product_name }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; color:#374151;">{{ $req->quantity }} / {{ $req->quantidade_recebida }}</td>
                            <td style="padding:12px 16px; font-size:13px; color:#374151;">
                                @if($req->conferente)
                                    <div style="font-size:11px; color:#9ca3af; margin-bottom:2px;">Conferido por: {{ $req->conferente->name }}</div>
                                @endif
                                {{ $req->observacao_conferencia }}
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->fotosConferencia->first())
                                    <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#1d4ed8; font-size:12px; text-decoration:underline;">Ver foto</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                <button onclick="document.getElementById('modal-resolver-{{ $req->id }}').style.display='flex'"
                                        style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Resolver
                                </button>
                            </td>
                        </tr>

                        <div id="modal-resolver-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                            <div style="background:#fff; border-radius:12px; padding:28px; width:100%; max-width:440px; margin:16px;">
                                <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Resolver Pendência</h3>
                                <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                                <form method="POST" action="{{ route('pendencias.resolver', $req) }}" id="form-resolver-{{ $req->id }}">
                                    @csrf
                                    @method('PATCH')

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Decisão</label>
                                        <select name="decisao" required onchange="atualizaObservacaoPendencia{{ $req->id }}(this.value)"
                                                style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                            <option value="aceitar">Aceitar Mesmo Assim</option>
                                            <option value="cancelar">Cancelar Item</option>
                                        </select>
                                    </div>

                                    <div style="margin-bottom:16px;">
                                        <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span id="obs-obrigatoria-{{ $req->id }}" style="display:none; color:#dc2626;">*</span></label>
                                        <textarea name="observacao" rows="3"
                                                  style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                    </div>

                                    <div style="display:flex; gap:10px; justify-content:flex-end;">
                                        <button type="button" onclick="document.getElementById('modal-resolver-{{ $req->id }}').style.display='none'"
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
                            <td colspan="7" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
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

    <div class="pend-mobile-cards">
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:10px;">{{ $req->product_name }}</div>

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
                        <span style="color:#9ca3af;">Qtd Solic. / Receb.</span>
                        <div style="font-weight:700; color:#374151;">{{ $req->quantity }} / {{ $req->quantidade_recebida }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Foto</span>
                        <div>
                            @if($req->fotosConferencia->first())
                                <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#1d4ed8; font-size:12px; text-decoration:underline;">Ver foto</a>
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>

                <div style="font-size:13px; color:#374151; margin-bottom:12px;">
                    <span style="color:#9ca3af;">Observação do Conferente</span>
                    @if($req->conferente)
                        <div style="font-size:11px; color:#9ca3af; margin-top:2px;">Conferido por: {{ $req->conferente->name }}</div>
                    @endif
                    <div>{{ $req->observacao_conferencia }}</div>
                </div>

                <div style="display:flex; justify-content:flex-end;">
                    <button onclick="document.getElementById('modal-resolver-m-{{ $req->id }}').style.display='flex'"
                            style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Resolver
                    </button>
                </div>
            </div>

            <div id="modal-resolver-m-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:#fff; border-radius:12px; padding:20px; width:100%; max-width:440px; margin:16px; max-height:88vh; overflow-y:auto;">
                    <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Resolver Pendência</h3>
                    <p style="margin:0 0 20px; font-size:13px; color:#9ca3af;">{{ $req->product_name }} — {{ $req->requester_name }}</p>

                    <form method="POST" action="{{ route('pendencias.resolver', $req) }}" id="form-resolver-m-{{ $req->id }}">
                        @csrf
                        @method('PATCH')

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Decisão</label>
                            <select name="decisao" required onchange="atualizaObservacaoPendenciaMobile{{ $req->id }}(this.value)"
                                    style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                <option value="aceitar">Aceitar Mesmo Assim</option>
                                <option value="cancelar">Cancelar Item</option>
                            </select>
                        </div>

                        <div style="margin-bottom:16px;">
                            <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span id="obs-obrigatoria-m-{{ $req->id }}" style="display:none; color:#dc2626;">*</span></label>
                            <textarea name="observacao" rows="3"
                                      style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                        </div>

                        <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" onclick="document.getElementById('modal-resolver-m-{{ $req->id }}').style.display='none'"
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

            <script>
            function atualizaObservacaoPendenciaMobile{{ $req->id }}(valor) {
                var form = document.getElementById('form-resolver-m-{{ $req->id }}');
                var textarea = form.querySelector('textarea[name="observacao"]');
                var marcador = document.getElementById('obs-obrigatoria-m-{{ $req->id }}');
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
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhuma pendência no momento.</p>
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
