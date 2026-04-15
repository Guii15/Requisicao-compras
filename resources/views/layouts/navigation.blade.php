<nav x-data="{ open: false }" style="background: #000069; box-shadow: 0 2px 8px rgba(0,0,0,0.4);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('requests.index') }}" style="text-decoration:none; display:flex; align-items:center; gap:10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:26px; height:26px; color:#ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span style="color:#ffffff; font-weight:700; font-size:16px;">Requisição de Compras</span>
                </a>

                <div class="hidden sm:flex sm:items-center sm:ms-8">
                    <a href="{{ route('requests.index') }}"
                       style="color: {{ request()->routeIs('requests.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('requests.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('requests.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('requests.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        Minhas Requisições
                    </a>
                    @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.index') }}"
                       style="color: {{ request()->routeIs('admin.*') ? '#ffffff' : 'rgba(255,255,255,0.65)' }};
                              background: {{ request()->routeIs('admin.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding:6px 14px; border-radius:6px; text-decoration:none; font-size:14px; font-weight:500; margin-left:4px;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#fff'"
                       onmouseout="this.style.background='{{ request()->routeIs('admin.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('admin.*') ? '#fff' : 'rgba(255,255,255,0.65)' }}'">
                        ⚙ Admin
                    </a>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button style="display:inline-flex; align-items:center; gap:8px; padding:6px 14px; background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2); border-radius:8px; color:#ffffff; font-size:14px; font-weight:500; cursor:pointer;"
                                onmouseover="this.style.background='rgba(255,255,255,0.2)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:17px; height:17px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ Auth::user()->name }}
                            <svg style="width:13px; height:13px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">Meu Perfil</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Sair</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" style="padding:8px; color:rgba(255,255,255,0.8); background:transparent; border:none; cursor:pointer;">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" style="background:#000050; border-top:1px solid rgba(255,255,255,0.1);">
        <div class="pt-2 pb-3 px-4">
            <a href="{{ route('requests.index') }}" style="display:block; color:#fff; padding:8px 12px; border-radius:6px; text-decoration:none; font-size:14px;">Minhas Requisições</a>
        </div>
        <div style="padding:12px 16px; border-top:1px solid rgba(255,255,255,0.1);">
            <div style="color:#fff; font-weight:600; font-size:15px;">{{ Auth::user()->name }}</div>
            <div style="color:rgba(255,255,255,0.55); font-size:13px;">{{ Auth::user()->email }}</div>
            <div class="mt-3">
                <a href="{{ route('profile.edit') }}" style="display:block; color:rgba(255,255,255,0.8); padding:8px 0; font-size:14px; text-decoration:none;">Meu Perfil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background:none; border:none; color:rgba(255,255,255,0.8); padding:8px 0; font-size:14px; cursor:pointer; width:100%; text-align:left;">Sair</button>
                </form>
            </div>
        </div>
    </div>
</nav>
