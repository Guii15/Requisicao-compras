{{-- Bloco unificado de notas da requisição, na ordem do fluxo (admin primeiro). Só aparece se houver algum texto. --}}
{{-- plano=true: sem caixa e sem título (quando já está dentro de uma coluna que tem o próprio título). --}}
@props(['item', 'margem' => '12px', 'plano' => false])

@php
    $temObs = filled($item->admin_note) ||
              filled($item->reason) ||
              filled($item->justification) ||
              filled($item->obs) ||
              filled($item->observacao_conferencia) ||
              filled($item->obs_entrada);
@endphp

@if($temObs)
<div style="margin-bottom:{{ $margem }}; font-size:12.5px; {{ $plano ? '' : 'background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px;' }}">
    <div style="font-size:10.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; display:{{ $plano ? 'none' : 'flex' }}; align-items:center; gap:6px;">
        <svg style="width:13px; height:13px; color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
        Notas e Ocorrências
    </div>

    <div style="display:flex; flex-direction:column; gap:8px;">
        @if(filled($item->admin_note))
            <div style="display:flex; gap:8px; align-items:flex-start;">
                <span style="display:inline-block; padding:1px 6px; border-radius:4px; background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; white-space:nowrap; margin-top:2px;">ADMIN</span>
                <div style="color:#334155; line-height:1.45; white-space:pre-line; flex:1;">{{ $item->admin_note }}</div>
            </div>
        @endif

        @if(filled($item->justification) || filled($item->reason))
            <div style="display:flex; gap:8px; align-items:flex-start;">
                <span style="display:inline-block; padding:1px 6px; border-radius:4px; background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; white-space:nowrap; margin-top:2px;">VENDEDOR</span>
                <div style="color:#334155; line-height:1.45; flex:1;">
                    @if(filled($item->reason))<strong>Motivo:</strong> {{ $item->reason }}@endif
                    @if(filled($item->reason) && filled($item->justification)) · @endif
                    @if(filled($item->justification)){{ $item->justification }}@endif
                </div>
            </div>
        @endif

        @if(filled($item->obs))
            <div style="display:flex; gap:8px; align-items:flex-start;">
                <span style="display:inline-block; padding:1px 6px; border-radius:4px; background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; white-space:nowrap; margin-top:2px;">CONFERÊNCIA</span>
                <div style="color:#334155; line-height:1.45; white-space:pre-line; flex:1;">{{ $item->obs }}</div>
            </div>
        @endif

        @if(filled($item->observacao_conferencia))
            <div style="display:flex; gap:8px; align-items:flex-start;">
                <span style="display:inline-block; padding:1px 6px; border-radius:4px; background:#fee2e2; color:#991b1b; font-size:10px; font-weight:700; white-space:nowrap; margin-top:2px;">DIVERGÊNCIA</span>
                <div style="color:#7f1d1d; line-height:1.45; white-space:pre-line; flex:1;">{{ $item->observacao_conferencia }}</div>
            </div>
        @endif

        @if(filled($item->obs_entrada))
            <div style="display:flex; gap:8px; align-items:flex-start;">
                <span style="display:inline-block; padding:1px 6px; border-radius:4px; background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; white-space:nowrap; margin-top:2px;">ENTRADA</span>
                <div style="color:#334155; line-height:1.45; white-space:pre-line; flex:1;">{{ $item->obs_entrada }}</div>
            </div>
        @endif
    </div>
</div>
@endif
