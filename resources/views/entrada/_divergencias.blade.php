{{-- Aba "Divergências" da Entrada: só consulta. Itens que a conferência marcou como divergentes e que ainda aguardam decisão. --}}
@php use Illuminate\Support\Facades\Storage; @endphp

<div style="margin-bottom:14px; padding:12px 16px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; color:#991b1b; font-size:13.5px; line-height:1.5;">
    Estes itens chegaram com divergência e <strong>ainda aguardam decisão</strong>. Se já foi liberado (por exemplo, o admin avisou no grupo interno),
    use <strong>Dar Entrada</strong> e escreva na observação o motivo. Quando o admin decide pelas Pendências, o item passa para a aba <strong>Aguardando</strong>.
</div>

@if($requests->isEmpty())
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; text-align:center; padding:48px 16px; color:#9ca3af; font-size:15px;">
        Nenhuma divergência em análise.
    </div>
@else
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(min(100%, 420px), 1fr)); gap:14px;">
        @foreach($requests as $grupo)
            @foreach($grupo as $req)
                <div style="background:#fff; border:1px solid #e5e7eb; border-left:4px solid #dc2626; border-radius:12px; padding:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:8px;">
                        <div style="min-width:0;">
                            <div style="font-size:12px; color:#9ca3af;">Requisição #{{ $req->id }} — {{ $req->requester_name ?? 'Não informado' }}</div>
                            <div style="font-size:15px; font-weight:700; color:#05018D;">{{ $req->product_name }}</div>
                            @if($req->supplier)
                                <div style="font-size:12.5px; color:#6b7280;">{{ $req->supplier }}</div>
                            @endif
                        </div>
                        @if($req->tipo_entrega === 'entrega_direta')
                            <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Venda Casada</span>
                        @else
                            <span style="background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Estoque</span>
                        @endif
                    </div>

                    <div style="display:flex; gap:18px; flex-wrap:wrap; font-size:13px; color:#374151; margin-bottom:10px;">
                        <span>Pedido: <strong>{{ $req->quantity }}</strong></span>
                        <span>Recebido: <strong style="color:#dc2626;">{{ $req->quantidade_recebida ?? '—' }}</strong></span>
                        @if($req->conferente)
                            <span style="color:#6b7280;">Conferido por {{ $req->conferente->name }}</span>
                        @endif
                        <x-fotos-conferencia :item="$req" modo="links" />
                    </div>

                    <x-obs-divergencia :item="$req" margem="8px" />
                    <x-obs-vendedor :item="$req" margem="8px" />
                    <x-obs-admin :item="$req" margem="8px" />
                    @if($req->pedido_compra_path)
                        <div style="margin-bottom:8px; font-size:13px;">
                            <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; font-weight:600; text-decoration:underline;">📎 Pedido de compra{{ $req->pedido_compra_nome ? ': ' . $req->pedido_compra_nome : '' }}</a>
                        </div>
                    @endif

                    <div style="margin-top:6px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                        <span style="font-size:12.5px; font-weight:600; color:#b45309;">
                            ⏳ {{ $req->tipo_entrega === 'entrega_direta' ? 'Venda Casada: a conferência decide se avança.' : 'Aguardando decisão do admin (tela Pendências).' }}
                        </span>
                        <button type="button" onclick="document.getElementById('modal-entrada-div-{{ $req->id }}').style.display='flex'"
                                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap;">
                            Dar Entrada
                        </button>
                    </div>

                    <div id="modal-entrada-div-{{ $req->id }}" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                        <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:440px; margin:16px; max-height:90vh; overflow-y:auto;">
                            <h3 style="margin:0 0 4px; font-size:17px; font-weight:700; color:#05018D;">Dar Entrada com divergência</h3>
                            <p style="margin:0 0 14px; font-size:13px; color:#9ca3af;">{{ $req->product_name }}</p>

                            <x-obs-divergencia :item="$req" margem="12px" />
                            @if($req->pedido_compra_path)
                                <div style="margin-bottom:12px; font-size:13px;">
                                    <a href="{{ route('admin.compras.pedido', $req) }}" target="_blank" style="color:#05018D; font-weight:600; text-decoration:underline;">📎 Pedido de compra{{ $req->pedido_compra_nome ? ': ' . $req->pedido_compra_nome : '' }}</a>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('entrada.darEntrada', $req) }}" onsubmit="return protegerEnvioDuplo(this)">
                                @csrf
                                @method('PATCH')

                                <div style="margin-bottom:14px;">
                                    <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Vendedor Destino</label>
                                    <input type="text" name="vendedor_destino" value="{{ $req->requester_name }}" required
                                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box;">
                                </div>

                                <div style="margin-bottom:14px;">
                                    <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Quantidade Dada Entrada</label>
                                    <input type="number" name="quantidade_entrada" value="{{ $req->quantidade_recebida ?? $req->quantity }}" readonly
                                           style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; background:#f3f4f6; color:#6b7280;">
                                    <div style="margin-top:4px; font-size:11px; color:#9ca3af;">A entrada é da quantidade que chegou ({{ $req->quantidade_recebida ?? $req->quantity }}).</div>
                                </div>

                                <div style="margin-bottom:16px;">
                                    <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase;">Observação <span style="color:#ef4444;">*</span></label>
                                    <textarea name="obs_entrada" rows="3" maxlength="500" required placeholder="Ex: admin liberou no grupo interno em 05/10..."
                                              style="width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:14px; box-sizing:border-box; resize:vertical; font-family:inherit;"></textarea>
                                    <div style="margin-top:4px; font-size:11px; color:#9ca3af;">Obrigatória: explica por que o item entrou mesmo com divergência.</div>
                                </div>

                                <div style="display:flex; gap:10px; justify-content:flex-end;">
                                    <button type="button" onclick="document.getElementById('modal-entrada-div-{{ $req->id }}').style.display='none'"
                                            style="padding:9px 20px; border-radius:8px; border:1.5px solid #e5e7eb; background:#fff; color:#6b7280; font-size:14px; font-weight:600; cursor:pointer;">Cancelar</button>
                                    <button type="submit"
                                            style="padding:9px 24px; border-radius:8px; background:linear-gradient(90deg,#05018D,#b40000); color:#fff; font-size:14px; font-weight:700; border:none; cursor:pointer;">Confirmar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endforeach
    </div>

    @if($requests->hasPages())
        <div style="padding:16px 4px; display:flex; justify-content:center;">
            {{ $requests->links() }}
        </div>
    @endif
@endif
