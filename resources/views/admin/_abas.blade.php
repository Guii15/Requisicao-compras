@php
    $abasAdmin = [
        ['rota' => 'admin.index', 'rotulo' => 'Requisições'],
    ];
    if (auth()->user()?->isSuperAdmin()) {
        $abasAdmin[] = ['rota' => 'admin.users.index', 'rotulo' => 'Usuários'];
    }
    $abasAdmin[] = ['rota' => 'pendencias.index', 'rotulo' => '📋 Pendências'];
    $abasAdmin[] = ['rota' => 'admin.compras.feitas', 'rotulo' => '📦 Compras Feitas'];
@endphp
<div class="m-rolagem" style="display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid #e5e7eb; flex-wrap:wrap;">
    @foreach($abasAdmin as $aba)
        @php $ativa = request()->routeIs($aba['rota']); @endphp
        <a href="{{ route($aba['rota']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; margin-bottom:-2px;
                  {{ $ativa ? 'background:#05018D; color:#fff; border:2px solid #05018D;' : 'background:transparent; color:#6b7280; border:2px solid transparent;' }}"
           @unless($ativa) onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endunless>
            {{ $aba['rotulo'] }}
        </a>
    @endforeach
</div>
