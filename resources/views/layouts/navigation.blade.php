<nav x-data="{ open: false }" style="position:sticky; top:0; z-index:100; height:60px; background:var(--nav-bg); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border-bottom:1px solid var(--border);">
    <div style="max-width:1280px; margin:0 auto; padding:0 24px; height:100%; display:flex; align-items:center; justify-content:space-between;">

        <a href="{{ route('requests.index') }}" style="text-decoration:none; display:flex; align-items:center;">
            <span style="font-size:17px; font-weight:700; color:var(--text); letter-spacing:-0.3px;">Binário<span style="color:var(--accent);">.</span></span>
        </a>

        <div class="hidden sm:flex" style="align-items:center; gap:2px;">
            <a href="{{ route('requests.index') }}"
               style="padding:6px 14px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:500; color:{{ request()->routeIs('requests.*') ? 'var(--text)' : 'var(--text2)' }}; background:{{ request()->routeIs('requests.*') ? 'var(--bg-card)' : 'transparent' }}; border:1px solid {{ request()->routeIs('requests.*') ? 'var(--border)' : 'transparent' }};">
                Minhas Requisições
            </a>
            @if(Auth::user()->isAdmin())
            <a href="{{ route('admin.index') }}"
               style="padding:6px 14px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:500; color:{{ request()->routeIs('admin.*') ? 'var(--text)' : 'var(--text2)' }}; background:{{ request()->routeIs('admin.*') ? 'var(--bg-card)' : 'transparent' }}; border:1px solid {{ request()->routeIs('admin.*') ? 'var(--border)' : 'transparent' }};">
                Admin
            </a>
            @endif
        </div>

        <div class="hidden sm:flex" style="align-items:center; gap:10px;">
            <button onclick="toggleTheme()" id="themeBtn"
                    style="background:var(--bg-card); border:1px solid var(--border); border-radius:8px; padding:7px 10px; cursor:pointer; color:var(--text2); display:flex; align-items:center; justify-content:center; font-size:15px; line-height:1;">
                <span id="themeIcon">☀️</span>
            </button>

            <div x-data="{ dropOpen: false }" style="position:relative;">
                <button @click="dropOpen = !dropOpen" @click.outside="dropOpen = false"
                        style="display:inline-flex; align-items:center; gap:8px; padding:6px 14px; background:var(--bg-card); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:14px; font-weight:500; cursor:pointer; font-family:inherit;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;color:var(--text2);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ Auth::user()->name }}
                    <svg style="width:12px;height:12px;color:var(--text3);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                <div x-show="dropOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     style="position:absolute; right:0; top:calc(100% + 8px); background:var(--bg2); border:1px solid var(--border); border-radius:12px; width:180px; padding:6px; box-shadow:0 8px 32px rgba(0,0,0,0.4); z-index:50;">
                    <a href="{{ route('profile.edit') }}"
                       style="display:block; padding:9px 14px; border-radius:8px; font-size:14px; color:var(--text2); text-decoration:none;"
                       onmouseover="this.style.background='var(--bg-card)'; this.style.color='var(--text)'"
                       onmouseout="this.style.background='transparent'; this.style.color='var(--text2)'">
                        Meu Perfil
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                style="display:block; width:100%; text-align:left; padding:9px 14px; border-radius:8px; font-size:14px; color:var(--text2); background:transparent; border:none; cursor:pointer; font-family:inherit;"
                                onmouseover="this.style.background='var(--bg-card)'; this.style.color='var(--text)'"
                                onmouseout="this.style.background='transparent'; this.style.color='var(--text2)'">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="-me-2 flex items-center sm:hidden">
            <button @click="open = !open" style="padding:8px; color:var(--text2); background:transparent; border:none; cursor:pointer;">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': !open}" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path :class="{'hidden': !open, 'inline-flex': open}" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden"
         style="background:var(--bg2); border-top:1px solid var(--border); padding:12px 16px;">
        <a href="{{ route('requests.index') }}" style="display:block; color:var(--text); padding:10px 12px; border-radius:8px; text-decoration:none; font-size:14px; margin-bottom:2px;">Minhas Requisições</a>
        @if(Auth::user()->isAdmin())
        <a href="{{ route('admin.index') }}" style="display:block; color:var(--text); padding:10px 12px; border-radius:8px; text-decoration:none; font-size:14px; margin-bottom:2px;">Admin</a>
        @endif
        <div style="border-top:1px solid var(--border); padding-top:12px; margin-top:8px;">
            <div style="color:var(--text); font-weight:600; font-size:14px; padding:0 12px 8px;">{{ Auth::user()->name }}</div>
            <a href="{{ route('profile.edit') }}" style="display:block; color:var(--text2); padding:9px 12px; font-size:14px; text-decoration:none;">Meu Perfil</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:none; border:none; color:var(--text2); padding:9px 12px; font-size:14px; cursor:pointer; width:100%; text-align:left; font-family:inherit;">Sair</button>
            </form>
        </div>
    </div>
</nav>

<script>
function toggleTheme() {
    const isLight = document.documentElement.classList.toggle('light-mode');
    localStorage.setItem('binario-theme', isLight ? 'light' : 'dark');
    const icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = isLight ? '🌙' : '☀️';
}
document.addEventListener('DOMContentLoaded', function() {
    const icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = document.documentElement.classList.contains('light-mode') ? '🌙' : '☀️';
});
</script>
