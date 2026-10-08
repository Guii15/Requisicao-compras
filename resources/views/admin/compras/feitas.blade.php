@extends('layouts.app')

{{-- Listagem usa a largura toda da tela (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')

<div style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Painel Administrativo</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Gerencie todas as requisições de compra</p>
    </div>

    @include('admin._abas')

    <div style="margin-bottom:14px;">
        <h2 style="margin:0; font-size:18px; font-weight:700; color:#111827;">Compras Feitas</h2>
        <p style="margin:4px 0 0; color:#6b7280; font-size:13px;">
            Registre e corrija os dados de cada compra aqui mesmo, sem sair desta tela.
            Aprovadas a partir de {{ $dataCorte }} entram na hora; as de antes entram quando têm os dados.
        </p>
    </div>

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:14px;">
            ✓ {{ session('success') }}
        </div>
    @endif
    @if(session('aviso'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:14px;">
            {{ session('aviso') }}
        </div>
    @endif

    @if($abrir)
        <div style="background:#fff; color:#374151; border:1px solid #e5e7eb; border-left:4px solid #05018D; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px;">
            Mostrando só a requisição de <strong>{{ $abrir->product_name }}</strong>.
            <a href="{{ route('admin.compras.feitas') }}" style="color:#05018D; font-weight:700; text-decoration:underline; white-space:nowrap;">Ver todas as compras →</a>
        </div>
    @endif

    {{-- O que ver: a lista normal ou só o que ainda falta registrar (inclui as aprovadas antes do corte) --}}
    @php
        $filtrosAtuais = request()->only(['produto', 'vendedor', 'data_inicial', 'data_final']);
        $abaLista = 'display:inline-flex; align-items:center; gap:8px; padding:7px 16px; border-radius:9999px; font-size:13px; font-weight:600; text-decoration:none; border:1px solid ';
    @endphp
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
        <a href="{{ route('admin.compras.feitas', $filtrosAtuais) }}"
           style="{{ $abaLista }}{{ $situacao === null ? '#111827; background:#111827; color:#fff;' : '#d1d5db; background:#fff; color:#374151;' }}">Todas as compras</a>
        <a href="{{ route('admin.compras.feitas', $filtrosAtuais + ['situacao' => 'falta']) }}"
           style="{{ $abaLista }}{{ $situacao === 'falta' ? '#111827; background:#111827; color:#fff;' : '#d1d5db; background:#fff; color:#374151;' }}">
            Falta registrar
            <span style="font-variant-numeric:tabular-nums; {{ $totalFalta > 0 ? 'color:#b45309;' : 'opacity:.6;' }} {{ $situacao === 'falta' ? 'color:#fcd34d;' : '' }}">{{ $totalFalta }}</span>
        </a>
    </div>

    @if($totalSemDados > 0 && $situacao !== 'falta')
        <div style="background:#fff; color:#374151; border:1px solid #e5e7eb; border-left:4px solid #d97706; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px; line-height:1.5;">
            {{ $totalSemDados }} {{ $totalSemDados === 1 ? 'compra aprovada' : 'compras aprovadas' }} antes de {{ $dataCorte }} {{ $totalSemDados === 1 ? 'ainda não tem' : 'ainda não têm' }} data e preço unitário registrados, por isso não aparecem nesta lista.
            <a href="{{ route('admin.compras.feitas', ['situacao' => 'falta']) }}" style="color:#05018D; font-weight:700; text-decoration:underline; white-space:nowrap;">Ver e registrar aqui →</a>
        </div>
    @endif

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; margin-bottom:14px;">
        <form method="GET" action="{{ route('admin.compras.feitas') }}" class="m-empilhar" style="display:grid; grid-template-columns:1fr 1fr 160px 160px auto; gap:8px; align-items:end;">
            @if($situacao)<input type="hidden" name="situacao" value="{{ $situacao }}">@endif
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
                    <a href="{{ route('admin.compras.feitas', $situacao ? ['situacao' => $situacao] : []) }}" style="padding:8px 14px; border-radius:8px; border:1px solid #e5e7eb; color:#6b7280; text-decoration:none; font-size:13px; white-space:nowrap;">Limpar</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Mesmo desenho do painel do admin: uma linha por requisição; ao abrir, cada item é um cartão. --}}
    <div class="lista-resp" style="background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
        <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc; border-bottom:1px solid #e5e7eb;">
                    @php $thFeita = 'padding:12px 16px; color:#6b7280; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap;'; @endphp
                    <th style="{{ $thFeita }} text-align:left;">Nº</th>
                    <th style="{{ $thFeita }} text-align:left;">Vendedor</th>
                    <th style="{{ $thFeita }} text-align:left;">Itens</th>
                    <th style="{{ $thFeita }} text-align:left;">Fornecedor</th>
                    <th style="{{ $thFeita }} text-align:left;">Compra</th>
                    <th style="{{ $thFeita }} text-align:right;">Total</th>
                    <th style="{{ $thFeita }} text-align:left;">Etapa</th>
                    <th style="{{ $thFeita }} text-align:left;">Dados da compra</th>
                    <th style="{{ $thFeita }} text-align:right;">Ação</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $grupo)
                    @php
                        $primeiroFeita = $grupo->first();
                        $chaveFeita = $primeiroFeita->grupo_id;
                        $faltamGrupo = $grupo->filter(fn ($r) => !$r->temDadosDaCompra())->count();
                        $todosComDados = $faltamGrupo === 0;
                        $nenhumComDados = $faltamGrupo === $grupo->count();
                        $produtosResumoFeita = $grupo->pluck('product_name')->filter()->implode(', ');
                        if (mb_strlen($produtosResumoFeita) > 80) {
                            $produtosResumoFeita = mb_substr($produtosResumoFeita, 0, 80) . '…';
                        }
                        // O mesmo fornecedor escrito de formas diferentes conta como um só.
                        $fornecedoresGrupo = $grupo->pluck('supplier')->filter()->unique(fn ($f) => \App\Support\RankingPorNome::chave($f));
                        $fornecedorGrupo = $fornecedoresGrupo->count() === 0 ? '—' : ($fornecedoresGrupo->count() === 1 ? $fornecedoresGrupo->first() : $fornecedoresGrupo->count() . ' fornecedores');
                        $datasGrupo = $grupo->pluck('data_compra')->filter()->map->format('d/m/Y')->unique();
                        $dataGrupo = $datasGrupo->count() === 0 ? '—' : ($datasGrupo->count() === 1 ? $datasGrupo->first() : $datasGrupo->count() . ' datas');
                        $totalGrupo = (float) $grupo->sum('valor');
                        $tdFeita = 'padding:12px 16px; font-size:14px; color:#374151; vertical-align:middle;';
                    @endphp
                    <tr class="grupo-cabecalho" style="border-bottom:1px solid #eef0f3; cursor:pointer;" onmouseover="this.style.background='#f7f8fa'" onmouseout="this.style.background='transparent'" onclick="toggleGrupoCompraFeita('{{ $chaveFeita }}')">
                        <td class="lr-num" style="{{ $tdFeita }} font-weight:700; color:#111827; white-space:nowrap;">#{{ $primeiroFeita->id }}</td>
                        <td data-rotulo="Vendedor" style="{{ $tdFeita }}">{{ $primeiroFeita->requester_name ?? 'Não informado' }}</td>
                        <td class="lr-larga" data-rotulo="Itens" style="{{ $tdFeita }} max-width:380px;">
                            <div style="font-weight:600; color:#111827; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $produtosResumoFeita }}</div>
                            <div style="font-size:12px; color:#6b7280; margin-top:2px;">{{ $grupo->count() }} {{ $grupo->count() > 1 ? 'itens' : 'item' }}</div>
                        </td>
                        <td data-rotulo="Fornecedor" style="{{ $tdFeita }}">{{ $fornecedorGrupo }}</td>
                        <td data-rotulo="Compra" style="{{ $tdFeita }} font-size:13px; white-space:nowrap;">{{ $dataGrupo }}</td>
                        <td data-rotulo="Total" style="{{ $tdFeita }} text-align:right; font-weight:600; color:#111827; white-space:nowrap;">{{ $totalGrupo > 0 ? 'R$ ' . number_format($totalGrupo, 2, ',', '.') : '—' }}</td>
                        <td data-rotulo="Etapa" style="{{ $tdFeita }}"><x-trilha-etapas :itens="$grupo" /></td>
                        <td data-rotulo="Dados da compra" style="{{ $tdFeita }} font-size:13px; white-space:nowrap;">
                            @if($todosComDados)
                                <span style="color:#6b7280;">Registrados</span>
                            @else
                                <span style="background:#fff; color:#7a4f00; border:1px solid #c98a00; padding:3px 10px; border-radius:9999px; font-size:12px; font-weight:700;">{{ $nenhumComDados ? 'Sem dados' : 'Parcial' }}</span>
                                @if($grupo->count() > 1)
                                    <span style="display:block; font-size:12px; color:#6b7280; margin-top:3px;">{{ $faltamGrupo === 1 ? 'falta 1 item' : 'faltam ' . $faltamGrupo . ' itens' }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="lr-acao" style="{{ $tdFeita }} text-align:right;">
                            <button type="button" onclick="event.stopPropagation(); toggleGrupoCompraFeita('{{ $chaveFeita }}')"
                                    style="border:1px solid #d1d5db; background:#fff; color:#374151; padding:7px 16px; border-radius:9999px; font-size:12.5px; font-weight:600; cursor:pointer; white-space:nowrap;">
                                <span data-seta-compra="{{ $chaveFeita }}">Ver itens</span>
                            </button>
                        </td>
                    </tr>
                    @foreach($grupo as $item)
                        @include('admin.compras._linha-desktop', ['item' => $item, 'chave' => $chaveFeita, 'primeiro' => $loop->first, 'ultimo' => $loop->last])
                    @endforeach
                @empty
                    <tr>
                        <td colspan="9" style="padding:40px 16px; text-align:center; color:#6b7280;">
                            {{ $situacao === 'falta' ? 'Nenhuma compra com dados faltando. Tudo registrado.' : 'Nenhuma compra registrada ainda.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div style="margin-top:16px;">{{ $requests->links() }}</div>
</div>

<script>
function toggleGrupoCompraFeita(chave) {
    var linhas = document.querySelectorAll('.grupo-item-compra-' + CSS.escape(chave));
    if (!linhas.length) return;
    var abrindo = linhas[0].style.display === 'none';
    linhas.forEach(function (linha) {
        linha.style.display = abrindo ? '' : 'none'; // '' devolve ao CSS: linha de tabela no PC, bloco no celular
    });
    document.querySelectorAll('[data-seta-compra="' + chave + '"]').forEach(function (seta) {
        seta.textContent = abrindo ? (seta.dataset.aberto || 'Ocultar itens') : (seta.dataset.fechado || 'Ver itens');
    });
}
</script>

{{-- Depois do script acima: a janela usa toggleGrupoCompraFeita ao abrir por atalho. --}}
@include('admin.compras._janela-dados')

@endsection
