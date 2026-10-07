@extends('layouts.app')

{{-- Mesma largura das outras listagens do sistema (o layout lê esta seção) --}}
@section('tela_cheia', '1')

@section('content')
@php
    use App\Support\Dinheiro;

    $semDados = $d['comprado'] <= 0;
    $maiorTop = max(1.0, (float) collect($d['top'])->max('saldo'));
    $maiorIdade = max(1.0, (float) collect($d['idade'])->max('valor'));
    $rampa = ['var(--fin-r1)', 'var(--fin-r2)', 'var(--fin-r3)', 'var(--fin-r4)'];
    $mes = ucfirst(collect($d['meses'])->last()['rotulo']);
@endphp

@include('financeiro._estilo')

<div class="fin-viz" style="padding: 8px 0;">

    <div style="margin-bottom:20px;">
        <h1 style="margin:0; font-size:24px; font-weight:700; color:#05018D;">Financeiro</h1>
        <p style="margin:4px 0 0; color:#6b7280; font-size:14px;">Contas a pagar por fornecedor: cada compra feita soma no saldo e cada pagamento dá baixa.</p>
    </div>

    @include('financeiro._abas')
    @include('financeiro._filtro-empresa')

    @if(session('success'))
        <div style="background:#dcfce7; color:#166534; border:1px solid #86efac; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">✓ {{ session('success') }}</div>
    @endif
    @if(session('aviso'))
        <div style="background:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:12px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">⚠️ {{ session('aviso') }}</div>
    @endif

    @if($empresaSel !== null)
        @php $nomeFiltrada = collect($d['empresas'])->firstWhere('chave', $empresaSel)['nome'] ?? $empresaSel; @endphp
        <div style="background:#eff6ff; color:#1e3a8a; border:1px solid #bfdbfe; padding:10px 16px; border-radius:8px; margin-bottom:16px; font-size:14px;">
            Mostrando só as compras de <strong>{{ $nomeFiltrada }}</strong>.
        </div>
    @endif

    @if($semDados)
        <div class="fin-card" style="text-align:center; padding:48px 20px;">
            <div style="font-size:40px;">💰</div>
            <h2 style="margin-top:8px;">Ainda não há compras com fornecedor registradas</h2>
            <p class="fin-sub" style="margin-bottom:0;">Quando o comprador preencher os dados de uma compra aprovada, ela aparece aqui e soma no saldo do fornecedor.</p>
        </div>
    @else

    {{-- Indicadores --}}
    <div class="fin-kpis">
        <div class="fin-card" style="border-color:#fca5a5;">
            <div class="fin-rotulo">Saldo devedor</div>
            <div class="fin-hero">{{ Dinheiro::brl($d['saldo']) }}</div>
            <div class="fin-nota">em {{ $d['aguardando']['compras'] }} {{ $d['aguardando']['compras'] === 1 ? 'compra' : 'compras' }} de {{ $d['aguardando']['fornecedores'] }} {{ $d['aguardando']['fornecedores'] === 1 ? 'fornecedor' : 'fornecedores' }}</div>
            @if($d['vencido']['compras'] > 0)
                <div style="margin-top:8px;"><span style="background:#fee2e2; color:#dc2626; padding:2px 10px; border-radius:20px; font-size:12px; font-weight:700;">{{ Dinheiro::brl($d['vencido']['valor']) }} vencido</span> <span class="fin-nota">em {{ $d['vencido']['compras'] }} {{ $d['vencido']['compras'] === 1 ? 'compra' : 'compras' }}</span></div>
            @endif
            <div class="fin-medidor" role="img" aria-label="{{ number_format($d['percentual_pago'], 1, ',', '.') }}% do total comprado já foi pago"><div style="width:{{ min(100, $d['percentual_pago']) }}%;"></div></div>
            <div class="fin-nota">{{ number_format($d['percentual_pago'], 1, ',', '.') }}% do total comprado já foi pago</div>
        </div>

        <div class="fin-card">
            <div class="fin-rotulo">Comprado em {{ $mes }}</div>
            <div class="fin-num">{{ Dinheiro::brl($d['comprado_mes']) }}</div>
            <div class="fin-nota">Total comprado até hoje: {{ Dinheiro::brl($d['comprado']) }}</div>
        </div>

        <div class="fin-card">
            <div class="fin-rotulo">Pago em {{ $mes }}</div>
            <div class="fin-num" style="color:#16a34a;">{{ Dinheiro::brl($d['pago_mes']) }}</div>
            <div class="fin-nota">Total pago até hoje: {{ Dinheiro::brl($d['pago']) }}</div>
        </div>

        <div class="fin-card">
            <div class="fin-rotulo">Compras pagas</div>
            <div class="fin-num">{{ $d['pagas'] }}</div>
            <div class="fin-nota"><a href="{{ route('financeiro.pagos', array_filter(['empresa' => request('empresa')])) }}" style="color:#05018D; font-weight:600;">Ver pagos</a> · <a href="{{ route('financeiro.aguardando', array_filter(['empresa' => request('empresa')])) }}" style="color:#05018D; font-weight:600;">{{ $d['aguardando']['compras'] }} aguardando</a></div>
        </div>
    </div>

    @if($empresaSel === null && count($d['empresas']) >= 2)
        <div class="fin-card" style="margin-bottom:16px;">
            <h2>Saldo devedor por empresa</h2>
            <p class="fin-sub">Quanto cada empresa ainda deve. Clique numa empresa para ver só as compras dela.</p>
            @foreach($d['empresas'] as $e)
                <div class="fin-pagamento">
                    <a href="{{ route('financeiro.index', ['empresa' => $e['chave']]) }}" style="color:#111827; font-weight:600; text-decoration:none;">{{ $e['nome'] }}</a>
                    <div style="text-align:right; white-space:nowrap;">
                        <span class="fin-valor" style="color:{{ $e['saldo'] > 0 ? '#dc2626' : '#16a34a' }};">{{ Dinheiro::brl($e['saldo']) }}</span>
                        <span class="fin-nota"> · {{ $e['compras'] }} {{ $e['compras'] === 1 ? 'compra' : 'compras' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Comprado × pago + idade --}}
    <div class="fin-grade">
        <div class="fin-card fin-span2">
            <h2>Comprado × pago por mês</h2>
            <p class="fin-sub">Últimos 6 meses. Compras pela data da compra, pagamentos pela data em que foram feitos.</p>
            <x-grafico-colunas :meses="$d['meses']" />
        </div>

        <div class="fin-card">
            <h2>Idade do que está em aberto</h2>
            <p class="fin-sub">Quanto falta pagar, por tempo desde a compra.</p>
            @if($d['aguardando']['compras'] === 0)
                <p style="margin:24px 0; text-align:center; color:#16a34a; font-weight:600;">✓ Nada em aberto. Tudo pago!</p>
            @else
                @foreach($d['idade'] as $i => $faixa)
                    <div class="fin-barra-linha" style="grid-template-columns:minmax(90px,110px) 1fr auto;" title="{{ $faixa['rotulo'] }}: {{ Dinheiro::brl($faixa['valor']) }} em {{ $faixa['compras'] }} {{ $faixa['compras'] === 1 ? 'compra' : 'compras' }}">
                        <span class="fin-nome" style="color:#374151; font-weight:500;">{{ $faixa['rotulo'] }}</span>
                        <div class="fin-trilho">@if($faixa['valor'] > 0)<div class="fin-fio" style="width:{{ max(1, $faixa['valor'] / $maiorIdade * 100) }}%; background:{{ $rampa[$i] }};"></div>@endif</div>
                        <span class="fin-valor">{{ Dinheiro::brl($faixa['valor']) }}</span>
                    </div>
                @endforeach
                <p class="fin-nota" style="margin-top:12px;">Mais escuro = compra mais antiga.</p>
            @endif
        </div>
    </div>

    {{-- Maiores saldos + últimos pagamentos --}}
    <div class="fin-grade">
        <div class="fin-card">
            <h2>Maiores saldos devedores</h2>
            <p class="fin-sub">Os 8 fornecedores com mais a pagar.</p>
            @forelse($d['top'] as $f)
                <div class="fin-barra-linha" title="{{ $f['nome'] }}: {{ Dinheiro::brl($f['saldo']) }}">
                    <a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $f['chave'], 'empresa' => request('empresa')])) }}">{{ $f['nome'] }}</a>
                    <div class="fin-trilho"><div class="fin-fio" style="width:{{ max(1, $f['saldo'] / $maiorTop * 100) }}%; background:var(--fin-s1);"></div></div>
                    <span class="fin-valor">{{ Dinheiro::brl($f['saldo']) }}</span>
                </div>
            @empty
                <p style="margin:24px 0; text-align:center; color:#16a34a; font-weight:600;">✓ Nenhum fornecedor com saldo a pagar.</p>
            @endforelse
        </div>

        <div class="fin-card">
            <h2>Últimos pagamentos</h2>
            <p class="fin-sub">Os 6 mais recentes.</p>
            @forelse($d['ultimos'] as $u)
                <div class="fin-pagamento">
                    <div style="min-width:0;">
                        <a href="{{ route('financeiro.fornecedor', array_filter(['chave' => $u['chave'], 'empresa' => request('empresa')])) }}" style="color:#111827; font-weight:600; text-decoration:none;">{{ $u['fornecedor'] }}</a>
                        <div class="fin-nota" style="margin-top:1px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $u['produto'] }} · req. #{{ $u['requisicao'] }}</div>
                    </div>
                    <div style="text-align:right; white-space:nowrap;">
                        <div class="fin-valor" style="color:#16a34a;">{{ Dinheiro::brl($u['valor']) }}</div>
                        <div class="fin-nota" style="margin-top:1px;">{{ $u['data']->format('d/m/Y') }} · {{ $u['meio'] ?? ($u['forma'] === 'a_vista' ? 'À vista' : 'Parcelado') }}@if($u['banco']) · {{ $u['banco'] }}@endif</div>
                    </div>
                </div>
            @empty
                <p style="margin:24px 0; text-align:center; color:#9ca3af;">Nenhum pagamento registrado ainda.</p>
            @endforelse
        </div>
    </div>

    @endif
</div>
@endsection
