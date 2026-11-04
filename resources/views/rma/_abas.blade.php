{{-- Abas do setor RMA. $abaRma: 'envios' ou 'compras'. --}}
@php $qtdAbertosRma = \App\Models\RmaEnvio::whereNotIn('status', \App\Models\RmaEnvio::FINALIZADOS)->count(); @endphp
<div class="m-rolagem" style="display:flex; gap:4px; margin-bottom:24px; padding-bottom:8px; border-bottom:2px solid #e5e7eb;">
    @foreach(['envios' => ['rota' => 'rma.envios', 'rotulo' => 'Enviados ao RMA'], 'compras' => ['rota' => 'rma.index', 'rotulo' => 'Compras que chegaram']] as $chaveAba => $abaCfg)
        <a href="{{ route($abaCfg['rota']) }}"
           style="padding:9px 20px; font-size:14px; font-weight:600; text-decoration:none; border-radius:6px 6px 0 0; display:inline-flex; gap:8px; align-items:center;
                  background:{{ $abaRma === $chaveAba ? '#05018D' : 'transparent' }}; color:{{ $abaRma === $chaveAba ? '#fff' : '#6b7280' }};"
           @if($abaRma !== $chaveAba) onmouseover="this.style.color='#05018D'" onmouseout="this.style.color='#6b7280'" @endif>
            {{ $abaCfg['rotulo'] }}
            @if($chaveAba === 'envios' && $qtdAbertosRma > 0)
                <span style="background:{{ $abaRma === 'envios' ? 'rgba(255,255,255,0.22)' : '#fee2e2' }}; color:{{ $abaRma === 'envios' ? '#fff' : '#dc2626' }}; padding:1px 8px; border-radius:20px; font-size:11.5px; font-weight:700;">{{ $qtdAbertosRma }}</span>
            @endif
        </a>
    @endforeach
</div>
