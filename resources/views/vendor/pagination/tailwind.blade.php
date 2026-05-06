@if ($paginator->hasPages())
<div style="display:flex; align-items:center; justify-content:space-between; margin-top:20px; flex-wrap:wrap; gap:12px;">

    <span style="font-size:13px; color:#6b7280;">
        Mostrando <strong>{{ $paginator->firstItem() }}</strong> a <strong>{{ $paginator->lastItem() }}</strong> de <strong>{{ $paginator->total() }}</strong> resultados
    </span>

    <div style="display:flex; gap:4px; align-items:center;">

        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <span style="padding:7px 14px; border-radius:7px; border:1px solid #e5e7eb; color:#d1d5db; font-size:13px; font-weight:500; cursor:default;">&#8592; Anterior</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" style="padding:7px 14px; border-radius:7px; border:1px solid #e5e7eb; color:#374151; font-size:13px; font-weight:500; text-decoration:none; background:#fff;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">&#8592; Anterior</a>
        @endif

        {{-- Páginas --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span style="padding:7px 10px; font-size:13px; color:#9ca3af;">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span style="padding:7px 13px; border-radius:7px; background:linear-gradient(90deg,#1e3a8a,#1d4ed8); color:#fff; font-size:13px; font-weight:600;">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" style="padding:7px 13px; border-radius:7px; border:1px solid #e5e7eb; color:#374151; font-size:13px; font-weight:500; text-decoration:none; background:#fff;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Próxima --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" style="padding:7px 14px; border-radius:7px; border:1px solid #e5e7eb; color:#374151; font-size:13px; font-weight:500; text-decoration:none; background:#fff;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#fff'">Próxima &#8594;</a>
        @else
            <span style="padding:7px 14px; border-radius:7px; border:1px solid #e5e7eb; color:#d1d5db; font-size:13px; font-weight:500; cursor:default;">Próxima &#8594;</span>
        @endif

    </div>
</div>
@endif
