<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Requisição de Compras') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('imagens/favicon.png') }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('imagens/favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
        html.dark body, html.dark .bg-gray-100 { background-color: #0f172a !important; }
        html.dark footer { background: #000050 !important; color: rgba(255,255,255,0.4) !important; }

        html.dark [style*="background:#fff"],
        html.dark [style*="background: #fff"] { background-color: #1e293b !important; }

        html.dark [style*="background:#f3f4f6"],
        html.dark [style*="background: #f3f4f6"],
        html.dark [style*="background:#f9fafb"],
        html.dark [style*="background: #f9fafb"] { background-color: #0f172a !important; }

        html.dark [style*="color:#111827"], html.dark [style*="color: #111827"],
        html.dark [style*="color:#374151"], html.dark [style*="color: #374151"] { color: #e2e8f0 !important; }
        html.dark [style*="color:#6b7280"], html.dark [style*="color: #6b7280"] { color: #94a3b8 !important; }
        html.dark [style*="color:#9ca3af"], html.dark [style*="color: #9ca3af"] { color: #64748b !important; }
        html.dark [style*="color:#1e3a8a"], html.dark [style*="color: #1e3a8a"] { color: #93c5fd !important; }

        html.dark [style*="border:1px solid #e5e7eb"],
        html.dark [style*="border: 1px solid #e5e7eb"] { border-color: #334155 !important; }
        html.dark [style*="border-bottom:1px solid #f3f4f6"],
        html.dark [style*="border-bottom:1px solid #e5e7eb"] { border-color: #334155 !important; }
        html.dark [style*="border-top:1px solid #f3f4f6"] { border-color: #334155 !important; }

        html.dark input[type="text"],
        html.dark input[type="date"],
        html.dark input[type="email"],
        html.dark input[type="password"],
        html.dark input[type="number"],
        html.dark textarea,
        html.dark select { background-color: #334155 !important; color: #e2e8f0 !important; border-color: #475569 !important; }

        html.dark .bg-white { background-color: #1e293b !important; }
        html.dark .text-gray-700, html.dark .text-gray-600 { color: #e2e8f0 !important; }
        html.dark .border-gray-200, html.dark .divide-gray-100 { border-color: #334155 !important; }
        html.dark [style*="box-shadow"] { box-shadow: 0 1px 4px rgba(0,0,0,0.4) !important; }
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
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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