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
        --bg: #000; --bg2: #0a0a0a; --bg3: #141414;
        --bg-card: rgba(255,255,255,0.05); --bg-input: rgba(255,255,255,0.07);
        --text: #f5f5f7; --text2: rgba(245,245,247,0.55); --text3: rgba(245,245,247,0.3);
        --border: rgba(255,255,255,0.1); --nav-bg: rgba(0,0,0,0.8); --accent: #0071e3;
        --success-bg: rgba(48,209,88,0.12); --success-text: #30d158;
        --warning-bg: rgba(255,214,10,0.12); --warning-text: #ffd60a;
        --danger-bg: rgba(255,69,58,0.12); --danger-text: #ff453a;
        --badge-alta-bg: rgba(255,69,58,0.15); --badge-alta-text: #ff6b6b;
        --badge-media-bg: rgba(255,214,10,0.15); --badge-media-text: #ffd60a;
        --badge-baixa-bg: rgba(48,209,88,0.15); --badge-baixa-text: #30d158;
        --badge-aprovado-bg: rgba(48,209,88,0.12); --badge-aprovado-text: #30d158;
        --badge-rejeitado-bg: rgba(255,69,58,0.12); --badge-rejeitado-text: #ff453a;
        --badge-pendente-bg: rgba(255,214,10,0.12); --badge-pendente-text: #ffd60a;
    }
    html.light-mode {
        --bg: #f5f5f7; --bg2: #fbfbfd; --bg3: #ffffff;
        --bg-card: #ffffff; --bg-input: #ffffff;
        --text: #1d1d1f; --text2: rgba(29,29,31,0.6); --text3: rgba(29,29,31,0.35);
        --border: rgba(0,0,0,0.1); --nav-bg: rgba(245,245,247,0.85); --accent: #0071e3;
        --success-bg: #dcfce7; --success-text: #166534;
        --warning-bg: #fef3c7; --warning-text: #92400e;
        --danger-bg: #fee2e2; --danger-text: #991b1b;
        --badge-alta-bg: #fee2e2; --badge-alta-text: #dc2626;
        --badge-media-bg: #fef3c7; --badge-media-text: #d97706;
        --badge-baixa-bg: #dcfce7; --badge-baixa-text: #16a34a;
        --badge-aprovado-bg: #dcfce7; --badge-aprovado-text: #166534;
        --badge-rejeitado-bg: #fee2e2; --badge-rejeitado-text: #991b1b;
        --badge-pendente-bg: #fef3c7; --badge-pendente-text: #92400e;
    }
    *, *::before, *::after { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body {
        font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
    }
    .page-main { min-height: calc(100vh - 112px); padding: 32px 0 48px; }
    .page-inner { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
    @media (max-width: 640px) { .page-inner { padding: 0 16px; } }
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
    @include('layouts.navigation')
    <main class="page-main">
        <div class="page-inner">
            @yield('content')
        </div>
    </main>
    <footer style="background:var(--bg2); border-top:1px solid var(--border); color:var(--text3); text-align:center; padding:14px 16px; font-size:12px; letter-spacing:0.3px;">
        Desenvolvido internamente pelo setor de T.I — Binário Tecnologia
    </footer>
</body>
</html>
