{{-- Só leitura: como está a entrada de um item já liberado pela conferência (aguardando ou realizada). --}}
@props(['item', 'margem' => '12px'])

@if(in_array($item->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true))
    <div style="margin-bottom:{{ $margem }}; padding:10px 12px; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:8px; font-size:13px; color:#4c1d95; line-height:1.5;">
        @if($item->entrada_concluida_em)
            <span style="font-size:11px; font-weight:700; text-transform:uppercase;">Entrada realizada</span>
            em {{ $item->entrada_concluida_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}
            · Destino: <strong>{{ $item->vendedor_destino ?? '—' }}</strong>
            · Qtd: <strong>{{ $item->quantidade_entrada ?? '—' }}</strong>
        @else
            <span style="font-size:11px; font-weight:700; text-transform:uppercase;">Aguardando entrada</span>
            · ainda não deram entrada neste item
        @endif
    </div>
@endif
