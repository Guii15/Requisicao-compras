<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Requisição de Compras') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('imagens/favicon.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('imagens/favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
    :root {
        --g-bg: #09090b;
        --g-glow1: rgba(0,113,227,0.22);
        --g-glow2: rgba(5,1,141,0.18);
        --g-card: #18181b;
        --g-card-border: rgba(255,255,255,0.1);
        --g-card-shadow: 0 32px 80px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,255,255,0.06);
        --g-input: #27272a;
        --g-input-border: rgba(255,255,255,0.12);
        --g-input-text: #f5f5f7;
        --g-input-placeholder: rgba(245,245,247,0.3);
        --g-autofill: #1e1e21;
        --g-text: #f5f5f7;
        --g-text2: rgba(245,245,247,0.45);
        --g-text3: rgba(245,245,247,0.3);
        --g-label: rgba(245,245,247,0.45);
        --g-accent: #0071e3;
        --g-danger: #ff453a;
        --g-danger-bg: rgba(255,69,58,0.12);
        --g-danger-border: rgba(255,69,58,0.25);
        --g-logo-filter: none;
        --g-toggle-bg: rgba(255,255,255,0.08);
        --g-toggle-border: rgba(255,255,255,0.12);
        --g-toggle-text: rgba(245,245,247,0.6);
    }
    html.light-mode {
        --g-bg: #f0f2f5;
        --g-glow1: rgba(0,113,227,0.12);
        --g-glow2: rgba(5,1,141,0.08);
        --g-card: #ffffff;
        --g-card-border: rgba(0,0,0,0.08);
        --g-card-shadow: 0 8px 40px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.06);
        --g-input: #f4f4f6;
        --g-input-border: rgba(0,0,0,0.12);
        --g-input-text: #1d1d1f;
        --g-input-placeholder: rgba(29,29,31,0.35);
        --g-autofill: #eeeef0;
        --g-text: #1d1d1f;
        --g-text2: rgba(29,29,31,0.5);
        --g-text3: rgba(29,29,31,0.35);
        --g-label: rgba(29,29,31,0.5);
        --g-accent: #0071e3;
        --g-danger: #d93025;
        --g-danger-bg: rgba(217,48,37,0.08);
        --g-danger-border: rgba(217,48,37,0.2);
        --g-logo-filter: brightness(0) invert(0);
        --g-toggle-bg: rgba(0,0,0,0.06);
        --g-toggle-border: rgba(0,0,0,0.1);
        --g-toggle-text: rgba(29,29,31,0.6);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        background: var(--g-bg);
        color: var(--g-text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
        transition: background 0.3s;
    }
    .auth-bg {
        position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background: radial-gradient(ellipse 80% 60% at 50% -10%, var(--g-glow1) 0%, transparent 60%),
                    radial-gradient(ellipse 60% 50% at 85% 110%, var(--g-glow2) 0%, transparent 60%);
        transition: background 0.3s;
    }
    @keyframes floatUp {
        0%   { transform: translateY(100vh) scale(0.8); opacity: 0; }
        10%  { opacity: 0.6; }
        90%  { opacity: 0.4; }
        100% { transform: translateY(-20px) scale(1); opacity: 0; }
    }
    .auth-card {
        position: relative; z-index: 1;
        background: var(--g-card);
        border: 1px solid var(--g-card-border);
        border-radius: 20px;
        padding: 44px 40px;
        width: 100%;
        max-width: 420px;
        box-shadow: var(--g-card-shadow);
        transition: background 0.3s, border-color 0.3s, box-shadow 0.3s;
    }
    @media (max-width: 480px) {
        .auth-card { padding: 32px 24px; border-radius: 16px; }
    }
    .theme-toggle {
        position: fixed; top: 16px; right: 20px; z-index: 100;
        background: var(--g-toggle-bg);
        border: 1px solid var(--g-toggle-border);
        border-radius: 50%;
        width: 38px; height: 38px;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        color: var(--g-toggle-text);
        transition: background 0.2s, border-color 0.2s, transform 0.2s;
        padding: 0;
        font-size: 17px;
        line-height: 1;
    }
    .theme-toggle:hover { transform: scale(1.1); }
    </style>
    <script>
    (function() {
        if (localStorage.getItem('binario-theme') === 'light') {
            document.documentElement.classList.add('light-mode');
        }
    })();
    </script>
</head>
<body>
    <div class="auth-bg"></div>
    <div id="auth-particles" style="position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden;"></div>

    <button class="theme-toggle" id="themeToggle" title="Alternar tema" onclick="toggleTheme()">
        <span id="themeIcon">☀️</span>
    </button>

    <div style="position:relative; z-index:1; width:100%; max-width:420px;">
        <div style="text-align:center; margin-bottom:32px;">
            @if(file_exists(public_path('imagens/logo.png')))
                <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-width:160px; max-height:68px; object-fit:contain; margin-bottom:16px; filter:var(--g-logo-filter);">
            @else
                <div style="font-size:30px; font-weight:800; color:var(--g-text); letter-spacing:-0.5px; margin-bottom:8px;">
                    Binário<span style="color:#0071e3;">.</span>
                </div>
            @endif
            <p style="font-size:13px; color:var(--g-text2); margin:0;">Requisição de Compras</p>
        </div>

        <div class="auth-card">
            {{ $slot }}
        </div>
    </div>

    <script>
    function updateIcon() {
        var isLight = document.documentElement.classList.contains('light-mode');
        document.getElementById('themeIcon').textContent = isLight ? '🌙' : '☀️';
    }
    function toggleTheme() {
        var isLight = document.documentElement.classList.toggle('light-mode');
        localStorage.setItem('binario-theme', isLight ? 'light' : 'dark');
        updateIcon();
    }
    updateIcon();

    (function() {
        var isLight = document.documentElement.classList.contains('light-mode');
        if (!isLight) {
            var c = document.getElementById('auth-particles');
            var colors = ['#0071e3','#05018D','#1d4ed8','#3b82f6','#60a5fa'];
            for (var i = 0; i < 20; i++) {
                var p = document.createElement('div');
                var size = Math.random() * 5 + 2;
                var x = Math.random() * 100;
                var delay = Math.random() * 12;
                var dur = 8 + Math.random() * 10;
                var color = colors[Math.floor(Math.random() * colors.length)];
                p.style.cssText = 'position:absolute;width:'+size+'px;height:'+size+'px;border-radius:50%;background:'+color+';left:'+x+'%;bottom:-10px;opacity:0;animation:floatUp '+dur+'s linear '+delay+'s infinite;';
                c.appendChild(p);
            }
        }
    })();
    </script>
</body>
</html>
