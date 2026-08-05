@extends('layouts.app')

@section('content')

<style>
.met-mobile-cards { display: none; }
@media (max-width: 768px) {
    .met-desktop-table { display: none; }
    .met-mobile-cards  { display: block; }
    .met-stats { grid-template-columns: 1fr !important; }
}
</style>

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Métricas</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Tempo médio de cada etapa e requisições estouradas</p>
    </div>

    <div class="met-stats" style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #05018D;">
            <p style="margin:0; font-size:{{ $tempoConferencia !== null ? '26px' : '15px' }}; font-weight:800; color:#05018D;">
                {{ $tempoConferencia !== null ? $tempoConferencia . 'h' : 'Sem dados suficientes' }}
            </p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Tempo médio de conferência</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #16a34a;">
            <p style="margin:0; font-size:{{ $tempoEntrada !== null ? '26px' : '15px' }}; font-weight:800; color:#16a34a;">
                {{ $tempoEntrada !== null ? $tempoEntrada . 'h' : 'Sem dados suficientes' }}
            </p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Tempo médio de entrada</p>
        </div>
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; border-top:3px solid #b40000;">
            <p style="margin:0; font-size:{{ $tempoCiclo !== null ? '26px' : '15px' }}; font-weight:800; color:#b40000;">
                {{ $tempoCiclo !== null ? $tempoCiclo . 'h' : 'Sem dados suficientes' }}
            </p>
            <p style="margin:4px 0 0; font-size:12px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px;">Tempo total do ciclo</p>
        </div>
    </div>

    <div style="margin-bottom:12px; font-size:15px; font-weight:700; color:#111827;">Requisições Estouradas</div>

    <div class="met-desktop-table" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        <div style="overflow-x:auto;">
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(90deg,#05018D,#1d4ed8);">
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Produto</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Vendedor</th>
                        <th style="padding:13px 16px; text-align:left; color:#fff; font-size:13px; font-weight:600;">Etapa</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Parado desde</th>
                        <th style="padding:13px 16px; text-align:center; color:#fff; font-size:13px; font-weight:600;">Tempo parado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($estourados as $item)
                        <tr style="border-bottom:1px solid #f3f4f6;">
                            <td style="padding:12px 16px; font-size:14px; color:#111827; font-weight:500;">{{ $item['product_name'] }}</td>
                            <td style="padding:12px 16px; font-size:14px; color:#374151;">{{ $item['requester_name'] ?? '—' }}</td>
                            <td style="padding:12px 16px;">
                                <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">{{ $item['etapa'] }}</span>
                            </td>
                            <td style="padding:12px 16px; text-align:center; font-size:13px; color:#6b7280;">{{ $item['desde']->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</td>
                            <td style="padding:12px 16px; text-align:center; font-size:14px; font-weight:600; color:#b40000;">{{ $item['horas_parado'] }}h</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="padding:48px 16px; text-align:center; color:#9ca3af; font-size:15px;">
                                Nenhuma requisição estourada no momento
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="met-mobile-cards">
        @forelse($estourados as $item)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:12px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:15px; font-weight:700; color:#05018D; margin-bottom:6px;">{{ $item['product_name'] }}</div>
                <span style="display:inline-block; margin-bottom:10px; background:#fef3c7; color:#d97706; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600;">{{ $item['etapa'] }}</span>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
                    <div>
                        <span style="color:#9ca3af;">Vendedor</span>
                        <div style="font-weight:600; color:#374151;">{{ $item['requester_name'] ?? '—' }}</div>
                    </div>
                    <div>
                        <span style="color:#9ca3af;">Tempo parado</span>
                        <div style="font-weight:700; color:#b40000;">{{ $item['horas_parado'] }}h</div>
                    </div>
                </div>
            </div>
        @empty
            <div style="text-align:center; padding:48px 16px;">
                <p style="color:#6b7280; font-size:15px; margin:0;">Nenhuma requisição estourada no momento</p>
            </div>
        @endforelse
    </div>

</div>

@endsection
