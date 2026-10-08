<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Requisição de Compras') }}</title>

        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#05018D">
        @if(config('services.webpush.public_key'))
            <meta name="vapid-public-key" content="{{ config('services.webpush.public_key') }}">
        @endif

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('imagens/favicon.png') }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('imagens/favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|inter:300,400,500,600|playfair-display:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
        html, body { overflow-x: hidden; max-width: 100%; }
        html { color-scheme: light; }
        html.dark { color-scheme: dark; }

        /* ===== Tema escuro "Slash": midnight + cobre. Cores só como tokens; telas herdam por seletor de atributo. ===== */
        html.dark {
            --sl-void: #08080a;     /* canvas */
            --sl-card: #040406;     /* cards */
            --sl-panel: #121317;    /* painéis, inputs, cabeçalhos de tabela */
            --sl-line: #1c1d22;     /* hairline */
            --sl-line-2: #2e3038;   /* borda secundária */
            --sl-steel: #9a9dab;   /* texto terciário (era #777a88, fraco demais p/ 11-12px) */
            --sl-fog: #b6b9c4;     /* texto secundário (era #9194a1) */
            --sl-bone: #e2e3e9;     /* texto padrão */
            --sl-copper: #cc9166;   /* único acento cromático */
        }
        html.dark body { font-family: 'Inter', 'Figtree', ui-sans-serif, system-ui, sans-serif; color: var(--sl-bone); }
        html.dark body, html.dark .bg-gray-100 { background-color: var(--sl-void) !important; }
        /* Título da página: serifa, nunca abaixo de 28px */
        html.dark main h1 { font-family: 'Playfair Display', Georgia, serif; font-weight: 400 !important; font-size: 28px !important; letter-spacing: 0.01em; line-height: 1.15; color: #fff !important; }
        html.dark nav { background: var(--sl-void) !important; box-shadow: none !important; border-bottom: 1px solid var(--sl-line); }
        html.dark footer { background: var(--sl-void) !important; color: var(--sl-fog) !important; border-top: 1px solid var(--sl-line); }

        /* Superfícies */
        html.dark [style*="background:#fff"], html.dark [style*="background: #fff"],
        html.dark [style*="background:#ffffff"], html.dark [style*="background: #ffffff"],
        html.dark .bg-white { background-color: var(--sl-card) !important; }
        html.dark [style*="background:#f3f4f6"], html.dark [style*="background: #f3f4f6"],
        html.dark [style*="background:#f9fafb"], html.dark [style*="background: #f9fafb"],
        html.dark [style*="background:#f8fafc"], html.dark [style*="background:#f1f5f9"],
        html.dark [style*="background:#fafafa"] { background-color: var(--sl-panel) !important; }
        html.dark [style*="background:#05018D"], html.dark [style*="background: #05018D"],
        html.dark [style*="background:#000069"] { background: var(--sl-panel) !important; color: var(--sl-bone) !important; }

        /* Texto */
        html.dark [style*="color:#111827"], html.dark [style*="color: #111827"],
        html.dark [style*="color:#374151"], html.dark [style*="color: #374151"],
        html.dark [style*="color:#0f172a"], html.dark [style*="color:#1e293b"],
        html.dark [style*="color:#334155"], html.dark [style*="color:#1e3a8a"], html.dark [style*="color: #1e3a8a"],
        html.dark .text-gray-700, html.dark .text-gray-600 { color: var(--sl-bone) !important; }
        html.dark [style*="color:#6b7280"], html.dark [style*="color: #6b7280"],
        html.dark [style*="color:#475569"], html.dark [style*="color:#64748b"] { color: var(--sl-fog) !important; }
        html.dark [style*="color:#9ca3af"], html.dark [style*="color: #9ca3af"],
        html.dark [style*="color:#94a3b8"], html.dark [style*="color:#d1d5db"] { color: var(--sl-steel) !important; }

        /* Bordas: hairlines, sem sombra */
        html.dark [style*="border:1px solid #e5e7eb"], html.dark [style*="border: 1px solid #e5e7eb"],
        html.dark [style*="border:1px solid #e2e8f0"], html.dark [style*="border:1.5px solid #e5e7eb"],
        html.dark [style*="border-bottom:1px solid #f3f4f6"], html.dark [style*="border-bottom:1px solid #e5e7eb"],
        html.dark [style*="border-bottom:1px solid #f1f5f9"], html.dark [style*="border-bottom:1px solid #e2e8f0"],
        html.dark [style*="border-top:1px solid #f3f4f6"], html.dark [style*="border-top:1px solid #e5e7eb"],
        html.dark .border-gray-200, html.dark .divide-gray-100 { border-color: var(--sl-line) !important; }
        html.dark [style*="border:1px solid #cbd5e1"]:not([style*="border-left:6px"]), html.dark [style*="border:1px solid #d1d5db"]:not([style*="border-left:6px"]) { border-color: var(--sl-line-2) !important; }
        html.dark [style*="border:1px solid #cbd5e1"][style*="border-left:6px"] { border-top-color: var(--sl-line-2) !important; border-right-color: var(--sl-line-2) !important; border-bottom-color: var(--sl-line-2) !important; }
        html.dark [style*="box-shadow"] { box-shadow: none !important; }

        /* Campos */
        html.dark input[type="text"], html.dark input[type="date"], html.dark input[type="datetime-local"], html.dark input[type="time"], html.dark input[type="month"], html.dark input[type="tel"], html.dark input[type="search"], html.dark input[type="email"],
        html.dark input[type="password"], html.dark input[type="number"], html.dark input[type="url"],
        html.dark textarea, html.dark select, html.dark .cr-input {
            background-color: var(--sl-panel) !important; color: var(--sl-bone) !important;
            border-top-color: var(--sl-line-2) !important; border-right-color: var(--sl-line-2) !important; border-bottom-color: var(--sl-line-2) !important; border-radius: 8px;
        }
        /* A borda esquerda só recebe a cor do tema se o campo não declarar tarja de status (border-left:6px) */
        html.dark :is(input[type="text"], input[type="date"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], textarea, select, .cr-input):not([style*="border-left:6px"]) { border-left-color: var(--sl-line-2) !important; }
        html.dark input::placeholder, html.dark textarea::placeholder { color: var(--sl-steel) !important; }
        html.dark .cr-input:focus, html.dark input:focus, html.dark textarea:focus, html.dark select:focus {
            border-top-color: var(--sl-steel) !important; border-right-color: var(--sl-steel) !important; border-bottom-color: var(--sl-steel) !important; box-shadow: 0 0 0 1px var(--sl-steel) !important; outline: none;
        }
        html.dark :is(input[type="text"], input[type="date"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], textarea, select, .cr-input):focus:not([style*="border-left:6px"]) { border-left-color: var(--sl-steel) !important; }

        /* Status: sem preenchimento saturado — fundo translúcido, tom dessaturado, borda fina */
        html.dark [style*="background:#dcfce7"], html.dark [style*="background:#f0fdf4"], html.dark [style*="background:#d1fae5"] { background: rgba(134,211,160,.08) !important; box-shadow: inset 0 0 0 1px rgba(134,211,160,.28) !important; }
        html.dark [style*="background:#fee2e2"], html.dark [style*="background:#fef2f2"] { background: rgba(229,154,154,.08) !important; box-shadow: inset 0 0 0 1px rgba(229,154,154,.28) !important; }
        html.dark [style*="background:#fef3c7"], html.dark [style*="background:#fffbeb"] { background: rgba(217,179,107,.08) !important; box-shadow: inset 0 0 0 1px rgba(217,179,107,.28) !important; }
        html.dark [style*="background:#dbeafe"], html.dark [style*="background:#eff6ff"] { background: rgba(169,184,217,.08) !important; box-shadow: inset 0 0 0 1px rgba(169,184,217,.28) !important; }
        html.dark [style*="background:#ffedd5"], html.dark [style*="background:#fff7ed"] { background: rgba(224,160,112,.08) !important; box-shadow: inset 0 0 0 1px rgba(224,160,112,.28) !important; }
        html.dark [style*="color:#16a34a"], html.dark [style*="color:#15803d"], html.dark [style*="color:#166534"], html.dark [style*="color:#14532d"], html.dark [style*="color:#059669"] { color: #86d3a0 !important; }
        html.dark [style*="color:#dc2626"], html.dark [style*="color:#b91c1c"], html.dark [style*="color:#991b1b"], html.dark [style*="color:#7f1d1d"] { color: #e59a9a !important; }
        html.dark [style*="color:#d97706"], html.dark [style*="color:#b45309"], html.dark [style*="color:#92400e"] { color: #d9b36b !important; }
        html.dark [style*="color:#1d4ed8"], html.dark [style*="color:#1e40af"], html.dark [style*="color:#2563eb"] { color: #a9b8d9 !important; }
        html.dark [style*="color:#c2410c"], html.dark [style*="color:#9a3412"], html.dark [style*="color:#7c2d12"] { color: #e0a070 !important; }

        /* Paleta de status pensada p/ fundo claro (#17794a, #b8301a, #8a5a00): clarear no escuro p/ manter contraste */
        /* Linhas de item expandido, divisórias dos cartões e trilha de etapas (componentes novos) */
        html.dark [style*="background:#f7f8fa"], html.dark tr[class*="grupo-item-"] { background-color: var(--sl-void) !important; }
        html.dark [style*="#eef0f3"] { border-color: var(--sl-line) !important; }
        html.dark .m-rolagem[style*="border-bottom:2px solid #e5e7eb"] { border-bottom-color: var(--sl-line-2) !important; }
        html.dark [style*="background:#111827"] { background: var(--sl-bone) !important; }
        html.dark [style*="background:#111827"][style*="color:#fff"], html.dark [style*="background:#111827"][style*="color: #fff"] { color: var(--sl-void) !important; }
        /* Elementos que o JS mexe (style.display etc.) perdem os seletores por atributo: usam classe */
        html.dark .jc-caixa { background: var(--sl-panel) !important; border-color: var(--sl-line) !important; color: var(--sl-fog) !important; }
        html.dark .jc-caixa a { color: var(--sl-copper) !important; }
        /* Texto sem espaço (nome de produto colado, link longo) quebra em vez de empurrar a tela para o lado */
        body { overflow-wrap:anywhere; }
        td, th, .lista-resp, .jan-corpo > * { min-width:0; }
        .cr-prod-row { border-bottom:1px solid #f1f5f9; background:#fff; }
        .cr-prod-row.par { background:#fafafa; }
        html.dark .cr-prod-row, html.dark .cr-prod-row.par { background: var(--sl-card) !important; border-bottom-color: var(--sl-line) !important; }
        html.dark [style*="background:#d7dbe2"] { background: var(--sl-line-2) !important; }
        html.dark [style*="border:2px solid #c5cbd6"] { border-color: var(--sl-steel) !important; }

        /* Links em azul-marinho (#05018D) somem no preto (1,4:1): cobre é o acento de link do Slash */
        html.dark a[style*="color:#05018D"], html.dark a[style*="color: #05018D"],
        html.dark [style*="color:#05018D"], html.dark [style*="color: #05018D"] { color: var(--sl-copper) !important; }
        html.dark [style*="color:#7a4f00"] { color: #d9b36b !important; }
        html.dark [style*="border:1px solid #c98a00"] { border-color: rgba(217,179,107,.45) !important; }
        html.dark [style*="color:#17794a"] { color: #86d3a0 !important; }
        html.dark [style*="color:#b8301a"] { color: #e59a9a !important; }
        html.dark [style*="color:#8a5a00"] { color: #d9b36b !important; }
        html.dark [style*="border:1px solid #17794a"] { border-color: rgba(134,211,160,.45) !important; }
        html.dark [style*="border:1px solid #b8301a"] { border-color: rgba(229,154,154,.45) !important; }
        html.dark [style*="border:1px solid #d99a00"] { border-color: rgba(217,179,107,.45) !important; }

        /* Ação principal: pílula branca (a única cor "alta" do sistema) */
        html.dark button[style*="background:#2563eb"], html.dark a[style*="background:#2563eb"],
        html.dark button[style*="background:#05018D"], html.dark a[style*="background:#05018D"],
        html.dark button[style*="linear-gradient"], html.dark a[style*="linear-gradient"],
        html.dark button[style*="background:#0f172a"] {
            background: #fff !important; color: #000 !important; border-radius: 9999px !important; box-shadow: none !important;
        }

        /* Cabeçalhos de tabela em degradê azul, trilhas e barras de gráfico, hover de linha */
        html.dark tr[style*="linear-gradient"], html.dark thead[style*="linear-gradient"],
        html.dark div[style*="linear-gradient"]:not([style*="border-radius:9999px"]) { background: var(--sl-panel) !important; border-bottom: 1px solid var(--sl-line-2); }
        html.dark tr[style*="linear-gradient"] th { color: var(--sl-fog) !important; font-weight: 500; }
        html.dark [style*="background:#e5e7eb"], html.dark [style*="background:#e2e8f0"] { background: var(--sl-line) !important; }
        html.dark div[style*="background:#2563eb"], html.dark div[style*="background:#3b82f6"], html.dark div[style*="background:#1d4ed8"] { background: var(--sl-fog) !important; }
        html.dark [style*="border-bottom:0.5px solid #e5e7eb"] { border-color: var(--sl-line) !important; }
        html.dark table tr, html.dark table td, html.dark table th, html.dark tbody { border-color: var(--sl-line) !important; }
        html.dark tbody tr:hover { background-color: var(--sl-panel) !important; }

        /* ===== Slash claro: mesma estrutura do escuro (hairlines, serifa, pílula), sobre papel claro ===== */
        html:not(.dark) body, html:not(.dark) .bg-gray-100 { background-color: #f6f6f7 !important; font-family: 'Inter', 'Figtree', ui-sans-serif, system-ui, sans-serif; }
        html:not(.dark) nav { background: #05018D !important; box-shadow: none !important; border-bottom: 1px solid #05018D; }
        html:not(.dark) footer { background: #05018D !important; color: rgba(255,255,255,0.6) !important; }
        html:not(.dark) main h1 { font-family: 'Playfair Display', Georgia, serif; font-weight: 400 !important; font-size: 28px !important; letter-spacing: 0.01em; line-height: 1.15; color: #121317 !important; }

        /* Texto azul de título/link vira quase-preto; azul não é cor do sistema */
        html:not(.dark) [style*="color:#1e3a8a"], html:not(.dark) [style*="color:#05018D"] { color: #121317 !important; }
        html:not(.dark) [style*="color:#374151"], html:not(.dark) [style*="color:#111827"] { color: #121317 !important; }
        html:not(.dark) [style*="color:#6b7280"] { color: #5e616e !important; }
        /* #9ca3af tem 2,5:1 sobre branco; #059669 tem 3,8:1 — subir p/ passar de 4,5:1 */
        html:not(.dark) [style*="color:#9ca3af"], html:not(.dark) [style*="color: #9ca3af"] { color: #6a6f7b !important; }
        html:not(.dark) [style*="color:#059669"] { color: #047857 !important; }

        /* Bordas e cartões: hairline, sem sombra */
        html:not(.dark) [style*="border:1px solid #e5e7eb"], html:not(.dark) [style*="border:1.5px solid #e5e7eb"],
        html:not(.dark) [style*="border:1px solid #e2e8f0"] { border-color: #e4e4e8 !important; }
        html:not(.dark) [style*="box-shadow:0 1px 3px"], html:not(.dark) [style*="box-shadow: 0 1px 3px"],
        html:not(.dark) [style*="box-shadow:0 1px 4px"] { box-shadow: none !important; }

        /* Campos */
        html:not(.dark) input[type="text"], html:not(.dark) input[type="date"], html:not(.dark) input[type="email"],
        html:not(.dark) input[type="password"], html:not(.dark) input[type="number"], html:not(.dark) input[type="url"],
        html:not(.dark) textarea, html:not(.dark) select, html:not(.dark) .cr-input { border-top-color: #d4d4da !important; border-right-color: #d4d4da !important; border-bottom-color: #d4d4da !important; border-radius: 8px; }
        html:not(.dark) :is(input[type="text"], input[type="date"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], textarea, select, .cr-input):not([style*="border-left:6px"]) { border-left-color: #d4d4da !important; }
        html:not(.dark) .cr-input:focus, html:not(.dark) input:focus, html:not(.dark) textarea:focus, html:not(.dark) select:focus {
            border-top-color: #121317 !important; border-right-color: #121317 !important; border-bottom-color: #121317 !important; box-shadow: 0 0 0 1px #121317 !important; outline: none;
        }
        html:not(.dark) :is(input[type="text"], input[type="date"], input[type="email"], input[type="password"], input[type="number"], input[type="url"], textarea, select, .cr-input):focus:not([style*="border-left:6px"]) { border-left-color: #121317 !important; }

        /* Cabeçalhos de tabela em degradê azul → painel claro com texto discreto */
        html:not(.dark) tr[style*="linear-gradient"], html:not(.dark) thead[style*="linear-gradient"],
        html:not(.dark) div[style*="linear-gradient"]:not([style*="border-radius:9999px"]):not(a):not(button) { background: #f0f0f2 !important; border-bottom: 1px solid #e4e4e8; }
        html:not(.dark) tr[style*="linear-gradient"] th { color: #5e616e !important; font-weight: 500; }
        html:not(.dark) div[style*="background:#2563eb"], html:not(.dark) div[style*="background:#3b82f6"], html:not(.dark) div[style*="background:#1d4ed8"] { background: #777a88 !important; }

        /* Ação principal: pílula no azul da Binário (um destaque por tela) */
        html:not(.dark) button[style*="background:#2563eb"], html:not(.dark) a[style*="background:#2563eb"],
        html:not(.dark) button[style*="background:#05018D"], html:not(.dark) a[style*="background:#05018D"],
        html:not(.dark) button[style*="linear-gradient"], html:not(.dark) a[style*="linear-gradient"],
        html:not(.dark) button[style*="background:#0f172a"] {
            background: #05018D !important; color: #fff !important; border-radius: 9999px !important; box-shadow: none !important;
        }

        /* Cards mobile (regras do app.css usavam slate azulado) */
        html.dark .m-card { background: var(--sl-card) !important; border-color: var(--sl-line) !important; }
        html.dark .m-card-titulo, html.dark .m-card-linha > strong { color: var(--sl-bone) !important; }
        html.dark .m-card-linha > span { color: var(--sl-fog) !important; }
        html.dark .m-pilulas > a:not(.ativo), html.dark .m-pilulas > button:not(.ativo) { background: var(--sl-panel) !important; color: var(--sl-fog) !important; }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            -webkit-text-fill-color: #374151 !important;
            caret-color: #374151 !important;
            transition: background-color 9999s ease-in-out 0s;
        }
        html.dark input:-webkit-autofill,
        html.dark input:-webkit-autofill:hover,
        html.dark input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #121317 inset !important;
            -webkit-text-fill-color: #e2e3e9 !important;
            caret-color: #e2e3e9 !important;
        }
        
        /* Listagens no celular: a MESMA tabela do PC vira cartões (cada linha de requisição é um bloco com rótulos). */
        @media (max-width: 768px) {
            .lista-resp { border: none !important; background: transparent !important; overflow: visible !important; }
            .lista-resp > div { overflow: visible !important; }
            .lista-resp table, .lista-resp tbody { display: block; width: 100%; }
            .lista-resp thead { display: none; }
            .lista-resp tr { display: block; }
            .lista-resp tr.grupo-cabecalho { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 14px; padding: 14px 16px; margin-top: 10px; background: #fff; border: 1px solid #e5e7eb !important; border-radius: 10px; }
            .lista-resp tr.grupo-cabecalho > td { display: block; padding: 0 !important; text-align: left !important; max-width: none !important; white-space: normal !important; min-width: 0; }
            .lista-resp tr.grupo-cabecalho > td[data-rotulo]::before { content: attr(data-rotulo); display: block; font-size: 10.5px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
            .lista-resp tr.grupo-cabecalho > td.lr-num { font-size: 16px !important; }
            .lista-resp tr.grupo-cabecalho > td.lr-larga, .lista-resp tr.grupo-cabecalho > td.lr-acao { grid-column: 1 / -1; }
            .lista-resp tr.grupo-cabecalho > td.lr-larga > div { white-space: normal !important; }
            .lista-resp tr.grupo-cabecalho > td.lr-acao { display: flex; gap: 8px; }
            .lista-resp tr.grupo-cabecalho > td.lr-acao > button { flex: 1; margin: 0 !important; min-height: 42px; }
            .lista-resp tr[class*="grupo-item-"] { background: transparent !important; }
            .lista-resp tr[class*="grupo-item-"] > td { display: block; padding: 8px 0 0 !important; border: none !important; }
            .lista-resp tr:not(.grupo-cabecalho):not([class*="grupo-item-"]) > td { display: block; }
            .ir-colunas { grid-template-columns: minmax(0, 1fr) !important; }
            .ir-colunas > div { border-left: none !important; border-top: 1px solid #eef0f3; }
            .ir-colunas > div:first-child { border-top: none; }
            .jan-corpo { grid-template-columns: minmax(0, 1fr) !important; gap: 18px !important; padding: 16px !important; }
            .jan-4 { grid-template-columns: 1fr 1fr !important; }
        }
        html.dark .lista-resp tr.grupo-cabecalho { border-color: var(--sl-line) !important; }
        @media (max-width: 768px) { html.dark .lista-resp tr.grupo-cabecalho { background: var(--sl-card); } }
    </style>

        <script>
        (function() {
            if (localStorage.getItem('darkMode') === '1') {
                document.documentElement.classList.add('dark');
            }
        })();
        </script>
    </head>
    <body class="font-sans antialiased">
        <div style="min-height:100vh; display:flex; flex-direction:column;" class="bg-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            @if(View::hasSection('fullcontent'))
                <main style="flex:1;">
                    @yield('fullcontent')
                </main>
            @else
                <main class="py-6" style="flex:1;">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" @if(View::hasSection('tela_cheia')) style="max-width:1560px;" @endif>
                        @yield('content')
                    </div>
                </main>
            @endif

            <footer style="background:#05018D; color:rgba(255,255,255,0.5); text-align:center; padding:12px 16px; font-size:12px; letter-spacing:0.3px;">
                Desenvolvido internamente pelo setor de T.I — Binário Tecnologia
            </footer>
        </div>
    </body>
</html>