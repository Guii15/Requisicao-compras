@php
    $abasFinanceiro = [
        ['ativa' => request()->routeIs('financeiro.index'), 'rota' => 'financeiro.index', 'rotulo' => 'Painel'],
        ['ativa' => request()->routeIs('financeiro.aguardando'), 'rota' => 'financeiro.aguardando', 'rotulo' => 'Aguardando', 'contagem' => $qtdAguardando ?? null],
        ['ativa' => request()->routeIs('financeiro.pagos'), 'rota' => 'financeiro.pagos', 'rotulo' => 'Pagos'],
        ['ativa' => request()->routeIs('financeiro.fornecedores', 'financeiro.fornecedor'), 'rota' => 'financeiro.fornecedores', 'rotulo' => 'Fornecedores'],
    ];
@endphp
<div class="m-rolagem" style="display:flex; gap:4px; margin-bottom:24px; padding-bottom:8px; border-bottom:2px solid #e5e7eb; flex-wrap:wrap;">
    @foreach($abasFinanceiro as $aba)
        <a href="{{ route($aba['rota'], array_filter(['empresa' => request('empresa')])) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; display:inline-flex; align-items:center; gap:8px;
                  {{ $aba['ativa'] ? 'background:#05018D; color:#fff; border:2px solid transparent;' : 'background:transparent; color:#6b7280; border:2px solid transparent;' }}"
           @unless($aba['ativa']) onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endunless>
            {{ $aba['rotulo'] }}
            @if(($aba['contagem'] ?? null) !== null)
                <span style="background:{{ $aba['ativa'] ? 'rgba(255,255,255,0.22)' : '#fee2e2' }}; color:{{ $aba['ativa'] ? '#fff' : '#dc2626' }}; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:700;">{{ $aba['contagem'] }}</span>
            @endif
        </a>
    @endforeach
</div>
