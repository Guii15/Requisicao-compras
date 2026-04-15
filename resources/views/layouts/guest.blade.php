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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
        * { box-sizing: border-box; }
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
        }
        .auth-left {
            width: 42%;
            background: #05018D;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px;
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }
        .auth-right {
            flex: 1;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 32px;
            overflow-y: auto;
        }
        .auth-card {
            width: 100%;
            max-width: 420px;
        }
        @media (max-width: 768px) {
            .auth-wrapper { flex-direction: column; }
            .auth-left {
                width: 100%;
                padding: 24px 20px;
                flex-direction: row;
                justify-content: center;
                align-items: center;
                gap: 16px;
            }
            .auth-left-text { display: none; }
            .auth-left img { max-width: 120px !important; max-height: 50px !important; margin: 0 !important; }
            .auth-circles { display: none; }
            .auth-right { padding: 28px 16px; }
        }
        </style>
    </head>
    <body style="margin:0; font-family:'Figtree',sans-serif;">
        <div class="auth-wrapper">

            {{-- Painel esquerdo --}}
            <div class="auth-left">
                <div class="auth-circles" style="position:absolute; bottom:-80px; right:-80px; width:300px; height:300px; border-radius:50%; background:rgba(180,0,0,0.18); pointer-events:none;"></div>
                <div class="auth-circles" style="position:absolute; top:-60px; left:-60px; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,0.04); pointer-events:none;"></div>

                <div style="position:relative; z-index:1; text-align:center; color:#fff; max-width:280px;">
                    @if(file_exists(public_path('imagens/logo.png')))
                        <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-width:190px; max-height:85px; object-fit:contain; margin:0 auto 28px; display:block;">
                    @elseif(file_exists(public_path('images/logo.png')))
                        <img src="{{ asset('images/logo.png') }}" alt="Binário" style="max-width:190px; max-height:85px; object-fit:contain; margin:0 auto 28px; display:block;">
                    @else
                        <div style="width:64px; height:64px; background:rgba(255,255,255,0.12); border-radius:18px; display:flex; align-items:center; justify-content:center; margin:0 auto 24px;">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:32px; height:32px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                    @endif

                    <div class="auth-left-text">
                        <h1 style="font-size:26px; font-weight:800; margin:0 0 10px; letter-spacing:-0.5px;">Requisição<br>de Compras</h1>
                        <p style="font-size:13px; color:rgba(255,255,255,0.6); line-height:1.7; margin:0 0 28px;">
                            Gerencie suas solicitações de compra de forma rápida e organizada.
                        </p>
                        <div style="width:48px; height:3px; background:linear-gradient(90deg,#fff,#b40000); border-radius:2px; margin:0 auto;"></div>
                    </div>
                </div>
            </div>

            {{-- Painel direito (formulário) --}}
            <div class="auth-right">
                <div class="auth-card">
                    {{ $slot }}
                </div>
            </div>

        </div>
    </body>
</html>
