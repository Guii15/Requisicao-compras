{{--
    Trilha de etapas da requisição: Aprovado → Comprado → Coletado → Conferido → Entrada.
    Recebe os itens (um grupo inteiro ou um item só) e mostra a etapa do item mais atrasado entre os aprovados.
--}}
@props(['itens'])

@php
    $todosDaTrilha = collect($itens);
    $aprovadosDaTrilha = $todosDaTrilha->where('status', 'aprovado');

    $etapaDoItem = function ($i) {
        if ($i->entrada_concluida_em || $i->status_conferencia === 'legado') return 5;
        if (in_array($i->status_conferencia, ['conferido_ok', 'avancado_mesmo_assim'], true)) return 4;
        if ($i->status_coleta === 'coletado') return 3;
        if ($i->temDadosDaCompra()) return 2;
        return 1;
    };

    if ($aprovadosDaTrilha->isEmpty()) {
        $etapaAtual = 0;
        $rotuloEtapa = $todosDaTrilha->every(fn ($i) => $i->status === 'rejeitado') ? 'Encerrada' : 'Aguardando aprovação';
    } else {
        $etapaAtual = $aprovadosDaTrilha->map($etapaDoItem)->min();
        $rotuloEtapa = [1 => 'Aprovado', 2 => 'Comprado', 3 => 'Coletado', 4 => 'Conferido', 5 => 'Entrada feita'][$etapaAtual];
    }
@endphp

<div style="display:inline-flex; flex-direction:column; gap:5px; text-align:left;">
    <div style="display:flex; align-items:center;">
        @for($n = 1; $n <= 5; $n++)
            @if($n > 1)
                <span style="width:16px; height:2px; background:{{ $etapaAtual >= $n ? '#111827' : '#d7dbe2' }};"></span>
            @endif
            <span style="width:10px; height:10px; border-radius:50%; box-sizing:border-box; {{ $etapaAtual >= $n ? 'background:#111827;' : 'background:#fff; border:2px solid #c5cbd6;' }}"></span>
        @endfor
    </div>
    <span style="font-size:12px; color:#374151; white-space:nowrap;">{{ $rotuloEtapa }}</span>
</div>
