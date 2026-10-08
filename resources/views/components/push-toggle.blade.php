@props(['variant' => 'desktop'])

@if(config('services.webpush.public_key'))
    @if($variant === 'mobile')
        <button type="button" data-push-toggle data-subscribe-url="{{ route('push.subscribe') }}"
                class="menu-m-acao" style="gap:6px;">
            <span data-push-icon>🔕</span> <span data-push-label>Ativar notificações</span>
        </button>
    @else
        <button type="button" data-push-toggle data-subscribe-url="{{ route('push.subscribe') }}" title="Ativar notificações"
                style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); border-radius:8px; padding:7px 10px; cursor:pointer; color:#fff; display:flex; align-items:center;"
                onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                onmouseout="this.style.background='rgba(255,255,255,0.1)'">
            <span data-push-icon>🔕</span>
        </button>
    @endif
@endif
