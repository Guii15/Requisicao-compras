<nav x-data="{ open: false }" style="background: linear-gradient(90deg, #1e3a8a 0%, #1d4ed8 50%, #dc2626 100%); border-bottom: none; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Logo -->
                <a href="{{ route('requests.index') }}" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:28px; height:28px; color:#ffffff;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <span style="color: #ffffff; font-weight: 700; font-size: 17px; letter-spacing: 0.3px;">Requisição de Compras</span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:ms-8" style="gap: 4px;">
                    <a href="{{ route('requests.index') }}"
                       style="color: {{ request()->routeIs('requests.*') ? '#ffffff' : 'rgba(255,255,255,0.75)' }};
                              background: {{ request()->routeIs('requests.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }};
                              padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s;"
                       onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.color='#ffffff';"
                       onmouseout="this.style.background='{{ request()->routeIs('requests.*') ? 'rgba(255,255,255,0.15)' : 'transparent' }}'; this.style.color='{{ request()->routeIs('requests.*') ? '#ffffff' : 'rgba(255,255,255,0.75)' }}';">
                        Minhas Requisições
                    </a>
                </div>
            </div>

            <!-- User Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; color: #ffffff; font-size: 14px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:18px; height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ Auth::user()->name }}
                            <svg style="width:14px; height:14px;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Meu Perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Sair
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (mobile) -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" style="display: inline-flex; align-items: center; justify-content: center; padding: 8px; border-radius: 6px; color: rgba(255,255,255,0.8); background: transparent; border: none; cursor: pointer;">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden" style="background: rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="pt-2 pb-3 space-y-1 px-4">
            <a href="{{ route('requests.index') }}" style="display: block; color: #ffffff; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 14px;">
                Minhas Requisições
            </a>
        </div>

        <div style="padding: 12px 16px; border-top: 1px solid rgba(255,255,255,0.1);">
            <div style="color: #ffffff; font-weight: 600; font-size: 15px;">{{ Auth::user()->name }}</div>
            <div style="color: rgba(255,255,255,0.7); font-size: 13px;">{{ Auth::user()->email }}</div>

            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}" style="display: block; color: rgba(255,255,255,0.85); padding: 8px 0; font-size: 14px; text-decoration: none;">
                    Meu Perfil
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="background: none; border: none; color: rgba(255,255,255,0.85); padding: 8px 0; font-size: 14px; cursor: pointer; text-align: left; width: 100%;">
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
