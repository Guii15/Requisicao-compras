<x-guest-layout>
    @php
    $inputStyle = "width:100%; border:1.5px solid #e5e7eb; border-radius:8px; padding:10px 13px; font-size:14px; color:#374151; box-sizing:border-box; outline:none; background:#fff; font-family:inherit;";
    $labelStyle = "display:block; font-size:11px; font-weight:700; color:#6b7280; margin-bottom:5px; text-transform:uppercase; letter-spacing:0.5px;";

    $intended = session('url.intended', '');
    $area = null;
    if (str_contains($intended, '/conferencia')) {
        $area = 'conferencia';
    } elseif (str_contains($intended, '/entrada')) {
        $area = 'entrada';
    } elseif (str_contains($intended, '/admin')) {
        $area = 'admin';
    } elseif (str_contains($intended, '/requisicoes/nova')) {
        $area = 'vendedor';
    }

    $textos = [
        'vendedor'    => ['Bem-vindo, Vendedor!', 'Faça login para criar sua requisição'],
        'conferencia' => ['Bem-vindo, Conferente!', 'Faça login para acessar a conferência'],
        'entrada'     => ['Bem-vindo à Entrada!', 'Faça login para registrar entradas'],
        'admin'       => ['Bem-vindo, Administrador!', 'Faça login para acessar o painel administrativo'],
    ];
    [$titulo, $subtitulo] = $textos[$area] ?? ['Bem-vindo!', 'Faça login para acessar o sistema'];

    $tabs = [
        'vendedor'    => ['label' => 'Vendedor',    'route' => route('requests.create')],
        'conferencia' => ['label' => 'Conferência', 'route' => route('conferencia.index')],
        'entrada'     => ['label' => 'Entrada',     'route' => route('entrada.index')],
        'admin'       => ['label' => 'Admin',       'route' => route('admin.index')],
    ];
    @endphp

    <div style="display:flex; gap:4px; margin-bottom:24px; background:#f3f4f6; border-radius:12px; padding:5px;">
        @foreach($tabs as $chave => $tab)
            <a href="{{ $tab['route'] }}"
               style="flex:1; text-align:center; padding:10px 3px; border-radius:9px; text-decoration:none; font-size:11.5px; font-weight:700; transition:transform 0.1s;
                      background:{{ $area === $chave ? 'linear-gradient(90deg, #05018D 0%, #b40000 100%)' : 'transparent' }};
                      color:{{ $area === $chave ? '#fff' : '#6b7280' }};
                      box-shadow:{{ $area === $chave ? '0 3px 10px rgba(5,1,141,0.35)' : 'none' }};"
               @if($area !== $chave) onmouseover="this.style.background='#e5e7eb'; this.style.color='#05018D'" onmouseout="this.style.background='transparent'; this.style.color='#6b7280'" @endif>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>

    <h2 style="font-size:22px; font-weight:800; color:#05018D; margin:0 0 4px;">{{ $titulo }}</h2>
    <p style="font-size:13px; color:#9ca3af; margin:0 0 28px;">{{ $subtitulo }}</p>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->any())
        <div style="background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; padding:11px 15px; border-radius:8px; margin-bottom:18px; font-size:13px;">
            <ul style="margin:0; padding-left:18px;">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div style="margin-bottom:16px;">
            <label style="{{ $labelStyle }}">E-mail <span style="color:#ef4444;">*</span></label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   style="{{ $inputStyle }}"
                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>

        <div style="margin-bottom:16px;">
            <label style="{{ $labelStyle }}">Senha <span style="color:#ef4444;">*</span></label>
            <input type="password" name="password" required autocomplete="current-password"
                   style="{{ $inputStyle }}"
                   onfocus="this.style.borderColor='#05018D'; this.style.boxShadow='0 0 0 3px rgba(5,1,141,0.08)'"
                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#6b7280; cursor:pointer;">
                <input type="checkbox" name="remember" style="width:15px; height:15px; accent-color:#05018D;">
                Lembrar de mim
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="font-size:13px; color:#05018D; text-decoration:none; font-weight:600;">
                    Esqueceu a senha?
                </a>
            @endif
        </div>

        <button type="submit"
                style="width:100%; padding:11px; border-radius:8px; background:linear-gradient(90deg, #05018D 0%, #b40000 100%); color:#fff; font-size:15px; font-weight:700; border:none; cursor:pointer; box-shadow:0 3px 12px rgba(5,1,141,0.35); font-family:inherit;">
            Entrar
        </button>

    </form>
</x-guest-layout>
