@extends('layouts.app')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:16px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Compras Feitas</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Requisições aprovadas que já têm os dados da compra registrados. Falta alguma? Registre em
            <a href="{{ route('admin.compras.index') }}" style="color:#05018D; font-weight:600;">Compras</a>.
        </p>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <form method="GET" action="{{ route('admin.compras.feitas') }}" style="display:grid; grid-template-columns:1fr 1fr 160px 160px auto; gap:8px; align-items:end;">
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Produto</label>
                <input type="text" name="produto" value="{{ request('produto') }}" placeholder="Buscar produto..."
                       style="width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Vendedor</label>
                <input type="text" name="vendedor" value="{{ request('vendedor') }}" placeholder="Nome do vendedor..."
                       style="width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Compra de</label>
                <input type="date" name="data_inicial" value="{{ request('data_inicial') }}"
                       style="width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box;">
            </div>
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:4px; text-transform:uppercase;">Compra até</label>
                <input type="date" name="data_final" value="{{ request('data_final') }}"
                       style="width:100%; padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; box-sizing:border-box;">
            </div>
            <div style="display:flex; gap:8px;">
                <button type="submit" style="padding:8px 16px; background:#05018D; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; white-space:nowrap;">Filtrar</button>
                @if(request('produto') || request('vendedor') || request('data_inicial') || request('data_final'))
                    <a href="{{ route('admin.compras.feitas') }}" style="padding:8px 14px; border-radius:8px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:13px; white-space:nowrap;">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow-x:auto; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; color:#6b7280; text-align:left; font-size:11.5px; text-transform:uppercase; letter-spacing:0.4px;">
                    <th style="padding:10px 14px;">Produto</th>
                    <th style="padding:10px 14px;">Qtd</th>
                    <th style="padding:10px 14px;">Fornecedor</th>
                    <th style="padding:10px 14px;">Compra</th>
                    <th style="padding:10px 14px; text-align:right;">Unitário</th>
                    <th style="padding:10px 14px; text-align:right;">Total</th>
                    <th style="padding:10px 14px;">Coleta</th>
                    <th style="padding:10px 14px;">Conferência</th>
                    <th style="padding:10px 14px;">Entrada</th>
                    <th style="padding:10px 14px;">Pedido</th>
                    <th style="padding:10px 14px;"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $grupo)
                    @php
                        $primeiroFeita = $grupo->first();
                        $chaveFeita = $primeiroFeita->grupo_id;
                        $todosComDados = $grupo->every(fn ($r) => $r->temDadosDaCompra());
                        $produtosResumoFeita = $grupo->pluck('product_name')->filter()->implode(', ');
                        if (mb_strlen($produtosResumoFeita) > 60) {
                            $produtosResumoFeita = mb_substr($produtosResumoFeita, 0, 60) . '…';
                        }
                    @endphp
                    <tr class="grupo-cabecalho" style="border-top:1px solid #f3f4f6; cursor:pointer; background:#fafafa;" onclick="toggleGrupoCompraFeita('{{ $chaveFeita }}')">
                        <td colspan="11" style="padding:0;">
                            <div style="display:flex; align-items:center; gap:12px; min-height:48px; padding:8px 14px;">
                                <div style="flex:1; min-width:0;">
                                    <span style="color:#111827; font-weight:700; font-size:13.5px;">Requisição #{{ $primeiroFeita->id }}</span>
                                    <span style="color:#9ca3af; font-weight:500; font-size:13px;"> — {{ $primeiroFeita->requester_name ?? 'Não informado' }}</span>
                                    <div style="font-size:12px; color:#6b7280; margin-top:2px;">
                                        {{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }} · {{ $produtosResumoFeita }}
                                    </div>
                                </div>
                                @unless($todosComDados)
                                    <span style="background:#fef3c7; color:#b45309; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; white-space:nowrap;">Parcial</span>
                                @endunless
                                <button type="button" onclick="event.stopPropagation(); toggleGrupoCompraFeita('{{ $chaveFeita }}')"
                                        style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                    <span id="seta-grupo-compra-{{ $chaveFeita }}">Ver itens</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @foreach($grupo as $item)
                    <tr class="grupo-item-compra-{{ $chaveFeita }}" style="display:none; border-top:1px solid #f3f4f6;">
                        <td style="padding:10px 14px; color:#111827;">
                            <strong>{{ $item->product_name }}</strong>
                            @if($item->codigo_fornecedor)
                                <div style="color:#9ca3af; font-size:12px;">Cód. fornecedor: {{ $item->codigo_fornecedor }}</div>
                            @endif
                        </td>
                        <td style="padding:10px 14px;">{{ $item->quantity }}</td>
                        <td style="padding:10px 14px;">{{ $item->supplier ?: '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->data_compra?->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px; text-align:right; white-space:nowrap;">{{ $item->preco_unitario !== null ? 'R$ ' . number_format($item->preco_unitario, 2, ',', '.') : '—' }}</td>
                        <td style="padding:10px 14px; text-align:right; white-space:nowrap; font-weight:600;">{{ $item->valor ? 'R$ ' . number_format($item->valor, 2, ',', '.') : '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->data_coleta?->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px; white-space:nowrap;">@include('admin.compras._conferencia')</td>
                        <td style="padding:10px 14px; white-space:nowrap;">{{ $item->entrada_concluida_em?->timezone('America/Sao_Paulo')->format('d/m/Y') ?? '—' }}</td>
                        <td style="padding:10px 14px;">
                            @if($item->pedido_compra_path)
                                <a href="{{ route('admin.compras.pedido', $item) }}" target="_blank" style="color:#05018D; font-weight:600;">Ver</a>
                            @else
                                <span style="color:#9ca3af;">—</span>
                            @endif
                        </td>
                        <td style="padding:10px 14px; text-align:right;">
                            <a href="{{ route('admin.compras.edit', $item) }}"
                               style="display:inline-block; padding:6px 14px; border-radius:6px; font-size:12.5px; font-weight:600; text-decoration:none; white-space:nowrap; border:1px solid #d1d5db; color:#374151; background:#fff;">
                                Editar
                            </a>
                        </td>
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="11" style="padding:40px 16px; text-align:center; color:#6b7280;">Nenhuma compra registrada ainda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $requests->links() }}</div>
</div>

<script>
function toggleGrupoCompraFeita(chave) {
    var linhas = document.querySelectorAll('.grupo-item-compra-' + CSS.escape(chave));
    var seta = document.getElementById('seta-grupo-compra-' + chave);
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? 'table-row' : 'none';
    });
    if (seta) seta.textContent = abrindo ? 'Ocultar itens' : 'Ver itens';
}
</script>

@endsection
