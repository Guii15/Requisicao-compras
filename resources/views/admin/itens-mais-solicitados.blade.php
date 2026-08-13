@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    {{-- Cabeçalho --}}
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
        <div>
            <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
            <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
        </div>
    </div>

    {{-- Abas --}}
    <div style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb; flex-wrap:wrap;">
        <a href="{{ route('admin.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            Requisições
        </a>
        <a href="{{ route('admin.users.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            Usuários
        </a>
        <a href="{{ route('pendencias.index') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:transparent; color:#6b7280; border:2px solid transparent; border-bottom:2px solid transparent;"
           onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'">
            📋 Pendências
        </a>
        <a href="{{ route('admin.itens-mais-solicitados') }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  background:#05018D; color:#fff; border:2px solid #05018D; border-bottom:2px solid #05018D;">
            📦 Itens Mais Solicitados
        </a>
    </div>

    <div style="margin-bottom:20px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Itens Mais Solicitados</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Variações de texto do mesmo item (maiúscula/minúscula, plural, espaçamento) agrupadas automaticamente — capacidades diferentes (GB/TB) nunca se misturam.
            @if($atualizadoEm)
                <span style="color:#9ca3af;">· atualizado em {{ $atualizadoEm->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</span>
            @endif
        </p>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
        @forelse($itens as $item)
            <div style="border-bottom:0.5px solid #e5e7eb;">
                <div style="display:flex; align-items:center; gap:12px; min-height:52px; padding:8px 16px 8px 0; cursor:pointer;"
                     onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='transparent'"
                     onclick="toggleItemRanking('{{ $item->id }}')">
                    <div style="width:4px; align-self:stretch; border-radius:2px; background:#05018D; margin-left:16px;"></div>
                    <div style="flex:1; min-width:0;">
                        <div style="font-size:13.5px; line-height:1.4;">
                            <span style="color:#111827; font-weight:700;">{{ $item->nome_canonico }}</span>
                            @if($item->capacidade)
                                <span style="color:#6b7280; font-weight:600;">· {{ strtoupper($item->capacidade) }}</span>
                            @endif
                        </div>
                        <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                            {{ count($item->variacoes_agrupadas) }} {{ count($item->variacoes_agrupadas) > 1 ? 'variações de texto agrupadas' : 'variação de texto' }}
                        </div>
                    </div>
                    <span style="background:#eceafd; color:#05018D; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">
                        {{ $item->total_pedidos }}× pedido{{ $item->total_pedidos > 1 ? 's' : '' }}
                    </span>
                    <button type="button" onclick="event.stopPropagation(); toggleItemRanking('{{ $item->id }}')"
                            style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap; margin-right:16px;">
                        <span id="seta-item-{{ $item->id }}">Ver variações</span>
                    </button>
                </div>
                <div id="variacoes-{{ $item->id }}" style="display:none; padding:4px 16px 14px 44px; background:#fafafa;">
                    @foreach($item->variacoes_agrupadas as $variacao)
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:13px; padding:5px 0; color:#374151; border-bottom:0.5px solid #eee;">
                            <span>"{{ $variacao['texto'] }}"</span>
                            <span style="color:#6b7280; font-weight:600; white-space:nowrap; margin-left:12px;">{{ $variacao['qtd'] }}×</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div style="padding:48px 16px; text-align:center;">
                <p style="color:#6b7280; font-size:15px; margin:0 0 4px;">Nenhum ranking gerado ainda</p>
                <p style="color:#9ca3af; font-size:13px; margin:0;">Rode <code>php artisan itens:atualizar-ranking</code> para gerar o primeiro ranking.</p>
            </div>
        @endforelse
    </div>

</div>

<script>
function toggleItemRanking(id) {
    var bloco = document.getElementById('variacoes-' + id);
    var seta = document.getElementById('seta-item-' + id);
    if (!bloco) return;
    var abrindo = bloco.style.display === 'none';
    bloco.style.display = abrindo ? 'block' : 'none';
    if (seta) seta.textContent = abrindo ? 'Ocultar variações' : 'Ver variações';
}
</script>

@endsection
