{{-- Filtro "Empresa" do Financeiro. Só aparece quando alguma compra tem empresa informada. Recebe $empresas e $empresaSel. --}}
@php
    $temEmpresa = collect($empresas)->contains(fn ($e) => $e['chave'] !== '_sem');
@endphp

@if($temEmpresa)
    <form method="GET" action="{{ url()->current() }}" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px;">
        @foreach(request()->except(['empresa', 'page']) as $nomeCampo => $valorCampo)
            @if(is_scalar($valorCampo))
                <input type="hidden" name="{{ $nomeCampo }}" value="{{ $valorCampo }}">
            @endif
        @endforeach
        <label for="filtro-empresa" style="font-size:11.5px; font-weight:700; color:#6b7280; text-transform:uppercase; letter-spacing:0.4px;">Empresa</label>
        <select id="filtro-empresa" name="empresa" onchange="this.form.submit()"
                style="padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:13.5px; background:#fff; min-width:220px;">
            <option value="">Todas as empresas</option>
            @foreach($empresas as $e)
                <option value="{{ $e['chave'] }}" {{ $empresaSel === $e['chave'] ? 'selected' : '' }}>{{ $e['nome'] }} ({{ $e['compras'] }} {{ $e['compras'] === 1 ? 'compra' : 'compras' }})</option>
            @endforeach
        </select>
        <noscript><button type="submit" style="padding:8px 14px; background:#05018D; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600;">Filtrar</button></noscript>
        @if($empresaSel !== null)
            <a href="{{ url()->current() }}{{ request()->except(['empresa', 'page']) ? '?' . http_build_query(request()->except(['empresa', 'page'])) : '' }}"
               style="font-size:13px; color:#6b7280;">Limpar filtro</a>
        @endif
    </form>
@endif
