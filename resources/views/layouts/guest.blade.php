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
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        background: #000;
        color: #f5f5f7;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
        -webkit-font-smoothing: antialiased;
        overflow-x: hidden;
    }
    .auth-bg {
        position: fixed; inset: 0; z-index: 0; pointer-events: none;
        background: radial-gradient(ellipse 80% 60% at 50% -10%, rgba(0,113,227,0.18) 0%, transparent 60%),
                    radial-gradient(ellipse 60% 50% at 85% 110%, rgba(5,1,141,0.15) 0%, transparent 60%);
    }
    @keyframes floatUp {
        0%   { transform: translateY(100vh) scale(0.8); opacity: 0; }
        10%  { opacity: 0.6; }
        90%  { opacity: 0.4; }
        100% { transform: translateY(-20px) scale(1); opacity: 0; }
    }
    .auth-card {
        position: relative; z-index: 1;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 20px;
        padding: 44px 40px;
        width: 100%;
        max-width: 420px;
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        box-shadow: 0 32px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05);
    }
    @media (max-width: 480px) {
        .auth-card { padding: 32px 24px; border-radius: 16px; }
    }
    </style>
</head>
<body>
    <div class="auth-bg"></div>
    <div id="auth-particles" style="position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden;"></div>

    <div style="position:relative; z-index:1; width:100%; max-width:420px;">
        <div style="text-align:center; margin-bottom:32px;">
            @if(file_exists(public_path('imagens/logo.png')))
                <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-width:160px; max-height:68px; object-fit:contain; margin-bottom:16px;">
            @else
                <div style="font-size:30px; font-weight:800; color:#f5f5f7; letter-spacing:-0.5px; margin-bottom:8px;">
                    Binário<span style="color:#0071e3;">.</span>
                </div>
            @endif
            <p style="font-size:13px; color:rgba(245,245,247,0.4); margin:0;">Requisição de Compras</p>
        </div>

        <div class="auth-card">
            {{ $slot }}
        </div>
    </div>

    <script>
    (function() {
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
    })();
    </script>
</body>
</html>
