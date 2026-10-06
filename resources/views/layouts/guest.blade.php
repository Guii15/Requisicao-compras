<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Requisição de Compras') }}</title>

        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <meta name="theme-color" content="#05018D">

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('imagens/favicon.png') }}">
        <link rel="shortcut icon" type="image/x-icon" href="{{ asset('imagens/favicon.ico') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|inter:400,500,600|playfair-display:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
        html { color-scheme: light; }
        * { box-sizing: border-box; }

        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            -webkit-text-fill-color: #374151 !important;
            caret-color: #374151 !important;
            transition: background-color 9999s ease-in-out 0s;
        }
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
            flex-direction: column;
            align-items: center;
            padding: 40px 32px 0;
            overflow-y: auto;
        }
        /* O formulário fica centralizado no espaço que sobra acima do rodapé */
        .auth-centro { flex: 1; width: 100%; display: flex; align-items: center; justify-content: center; padding-bottom: 32px; }
        .auth-rodape {
            width: 100%; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 6px 24px;
            padding: 16px 0 18px; border-top: 1px solid #e4e4e8; font-size: 12.5px; color: #6a6f7b;
        }
        .auth-rodape strong { color: #05018D; font-weight: 600; }
        .auth-card {
            width: 100%;
            max-width: 420px;
        }
        /* Fundo animado do painel: botão da Binário com trilhas de circuito (desenhado no canvas) */
        .auth-circuito { position: absolute; inset: 0; width: 100%; height: 100%; display: block; pointer-events: none; }
        .auth-left-conteudo { margin-top: clamp(150px, 30vh, 280px); }

        /* Mesmo visual das telas internas (tema claro do layouts/app): fundo, serifa no título, pílula azul, linhas finas */
        body { font-family: 'Inter', 'Figtree', ui-sans-serif, system-ui, sans-serif !important; }
        .auth-right { background: #f6f6f7; }
        .auth-card h2 { font-family: 'Playfair Display', Georgia, serif; font-weight: 400 !important; font-size: 28px !important; letter-spacing: 0.01em; line-height: 1.15; color: #05018D !important; }
        .auth-card [style*="color:#9ca3af"] { color: #6a6f7b !important; }
        .auth-card button[style*="linear-gradient"], .auth-card a[style*="linear-gradient"],
        .auth-card button[style*="background:#05018D"], .auth-card a[style*="background:#05018D"] {
            background: #05018D !important; color: #fff !important; border-radius: 9999px !important; box-shadow: none !important;
        }
        .auth-card input[type="text"], .auth-card input[type="email"], .auth-card input[type="password"] { border-color: #d4d4da !important; border-radius: 8px !important; }
        .auth-card a[style*="border:1.5px solid #e5e7eb"] { border-width: 1px !important; border-color: #e4e4e8 !important; background: #fff; }
        .auth-card input[type="checkbox"] { accent-color: #05018D; }
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
            .auth-circuito { display: none; }
            .auth-left-conteudo { margin-top: 0; }
            .auth-right { padding: 28px 16px 0; }
            .auth-rodape { flex-direction: column; align-items: center; text-align: center; }
        }
        </style>
    </head>
    <body style="margin:0; font-family:'Figtree',sans-serif;">
        <div class="auth-wrapper">

            {{-- Painel esquerdo --}}
            <div class="auth-left">
                <canvas class="auth-circuito" id="auth-circuito" aria-hidden="true"></canvas>

                <div class="auth-left-conteudo" style="position:relative; z-index:1; text-align:center; color:#fff; max-width:280px;">
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
                <div class="auth-centro">
                    <div class="auth-card">
                        {{ $slot }}
                    </div>
                </div>

                <footer class="auth-rodape">
                    <span><strong>Binário Tecnologia</strong> · desde 1997</span>
                    <span>Problemas para entrar? Fale com o setor de T.I.</span>
                    <span>Desenvolvido internamente pelo setor de T.I</span>
                </footer>
            </div>

        </div>

        {{-- Botão da Binário com trilhas de circuito no painel esquerdo. Não roda no celular (o canvas fica oculto). --}}
        <script>
        (function () {
            var canvas = document.getElementById('auth-circuito');
            if (!canvas || !canvas.getContext) return;
            var c = canvas.getContext('2d'), w = 0, h = 0, u = 1;
            var VERM = '#ff3333', RO = 1, RI = 0.55, FENDA = 0.44, BW = 0.17, BTOP = -1.26, BBOT = 0.12, DY = 0.08;
            var TOPO = -Math.PI / 2, A0 = TOPO + FENDA, A1 = TOPO - FENDA + 2 * Math.PI;
            var parado = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function medir() {
                var r = canvas.getBoundingClientRect(), dpr = Math.min(window.devicePixelRatio || 1, 2);
                w = r.width; h = r.height;
                canvas.width = Math.max(1, Math.round(w * dpr)); canvas.height = Math.max(1, Math.round(h * dpr));
                c.setTransform(dpr, 0, 0, dpr, 0, 0);
                u = Math.max(34, Math.min(w, h) / 9);   // raio do botão em px
            }

            // Trilhas: saem do anel, andam na diagonal e depois seguem retas até a borda. Sorteio fixo para o desenho não mudar a cada visita.
            var trilhas = [], semente = 7;
            function sorteio() { semente = (semente * 16807) % 2147483647; return semente / 2147483647; }
            for (var k = 0; k < 16; k++) {
                var a = k / 16 * Math.PI * 2 + 0.2, d1 = Math.round(a / (Math.PI / 4)) * (Math.PI / 4), d2 = Math.round(a / (Math.PI / 2)) * (Math.PI / 2);
                var p0 = [Math.cos(a) * 1.18, Math.sin(a) * 1.18 + DY], l1 = 0.5 + sorteio() * 0.9, l2 = 30;
                var p1 = [p0[0] + Math.cos(d1) * l1, p0[1] + Math.sin(d1) * l1];
                trilhas.push({ p0: p0, p1: p1, p2: [p1[0] + Math.cos(d2) * l2, p1[1] + Math.sin(d2) * l2], l1: l1, l2: l2, fase: sorteio(), vel: 0.035 + sorteio() * 0.03 });
            }

            function quadro(agora) {
                if (!w || !h) { medir(); }
                var t = agora / 1000;
                c.clearRect(0, 0, w, h);
                c.save(); c.translate(w / 2, h * 0.3); c.scale(u, u);
                c.lineWidth = 0.025; c.lineJoin = 'round';
                for (var i = 0; i < trilhas.length; i++) {
                    var tr = trilhas[i];
                    c.strokeStyle = 'rgba(255,255,255,0.16)';
                    c.beginPath(); c.moveTo(tr.p0[0], tr.p0[1]); c.lineTo(tr.p1[0], tr.p1[1]); c.lineTo(tr.p2[0], tr.p2[1]); c.stroke();
                    c.fillStyle = 'rgba(255,255,255,0.4)'; c.beginPath(); c.arc(tr.p0[0], tr.p0[1], 0.05, 0, 6.3); c.fill();
                    var total = tr.l1 + tr.l2, d = (parado ? tr.fase * 0.3 : ((t * tr.vel + tr.fase) % 1)) * total, x, y, q;
                    if (d < tr.l1) { q = d / tr.l1; x = tr.p0[0] + (tr.p1[0] - tr.p0[0]) * q; y = tr.p0[1] + (tr.p1[1] - tr.p0[1]) * q; }
                    else { q = (d - tr.l1) / tr.l2; x = tr.p1[0] + (tr.p2[0] - tr.p1[0]) * q; y = tr.p1[1] + (tr.p2[1] - tr.p1[1]) * q; }
                    c.save(); c.shadowColor = VERM; c.shadowBlur = 12; c.fillStyle = '#ff8a8a'; c.beginPath(); c.arc(x, y, 0.06, 0, 6.3); c.fill(); c.restore();
                }
                c.fillStyle = VERM;
                c.beginPath(); c.arc(0, DY, RO, A0, A1, false); c.arc(0, DY, RI, A1, A0, true); c.closePath(); c.fill();
                c.beginPath(); c.arc(0, DY + BTOP + BW, BW, Math.PI, 0, false); c.arc(0, DY + BBOT - BW, BW, 0, Math.PI, false); c.closePath(); c.fill();
                c.restore();
                if (!parado) requestAnimationFrame(quadro);
            }

            medir();
            window.addEventListener('resize', function () { medir(); if (parado) quadro(0); });
            requestAnimationFrame(quadro);
        })();
        </script>
    </body>
</html>
