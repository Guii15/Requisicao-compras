{{-- Aba "Divergências" da Entrada: só consulta. Itens que a conferência marcou como divergentes e que ainda aguardam decisão. --}}
@php use Illuminate\Support\Facades\Storage; @endphp

<div style="margin-bottom:14px; padding:12px 16px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; color:#991b1b; font-size:13.5px; line-height:1.5;">
    Estes itens chegaram com divergência e <strong>ainda não podem receber entrada</strong>. Quando forem liberados, passam para a aba <strong>Aguardando</strong>
    com a observação da divergência.
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
                        @if($req->fotosConferencia->first())
                            <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#05018D; font-weight:600;">📷 Ver foto</a>
                        @endif
                    </div>

                    <x-obs-divergencia :item="$req" margem="8px" />
                    <x-obs-vendedor :item="$req" margem="8px" />
                    <x-obs-admin :item="$req" margem="8px" />

                    <div style="margin-top:6px; font-size:12.5px; font-weight:600; color:#b45309;">
                        ⏳ {{ $req->tipo_entrega === 'entrega_direta' ? 'Venda Casada: a conferência decide se avança.' : 'Aguardando decisão do admin (tela Pendências).' }}
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
