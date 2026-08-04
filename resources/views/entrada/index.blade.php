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
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Itens liberados pela conferência aguardando entrada</p>
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
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Fornecedor</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Qtd Solic. / Receb.</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Foto</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">
                                {{ $req->product_name }}
                                @if($req->status_conferencia === 'avancado_mesmo_assim')
                                    <span style="display:block; margin-top:4px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; width:fit-content;">⚠ Avançado Mesmo Assim</span>
                                @endif
                            </td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->requester_name ?? '—' }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $req->supplier ?? '—' }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; color:#374151;">{{ $req->quantity }} / {{ $req->quantidade_recebida }}</td>
                            <td style="padding:12px 16px; text-align:center;">
                                @if($req->fotosConferencia->first())
                                    <a href="{{ Storage::url($req->fotosConferencia->first()->caminho_arquivo) }}" target="_blank" style="color:#1d4ed8; font-size:12px; text-decoration:underline;">Ver foto</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td style="padding:12px 16px; text-align:center;">
                                <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:6px 14px; font-size:12px; font-weight:600; cursor:pointer;">
                                    Dar Entrada
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhum item liberado aguardando entrada.
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
        @forelse($requests as $req)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:6px;">{{ $req->product_name }}</div>
                @if($req->status_conferencia === 'avancado_mesmo_assim')
                    <span style="display:inline-block; margin-bottom:10px; background:#dbeafe; color:#2563eb; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">⚠ Avançado Mesmo Assim</span>
                @endif

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

                <div style="display:flex; justify-content:flex-end;">
                    <button style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer;">
                        Dar Entrada
                    </button>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhum item liberado aguardando entrada.</p>
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
