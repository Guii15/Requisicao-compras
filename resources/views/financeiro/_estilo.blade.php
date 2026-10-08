{{-- Cores e regras dos gráficos do painel (claro e escuro). Azul da Binário: comprado escuro, pago claro; rampa do mesmo azul para ordem (idade). --}}
<style>
    .fin-viz {
        --fin-s1: #05018D; --fin-s2: #8b89d4;
        --fin-r1: #c9c8ee; --fin-r2: #8b89d4; --fin-r3: #4a47b3; --fin-r4: #05018D;
        --fin-grid: #e5e7eb; --fin-ink: #111827; --fin-muted: #6b7280; --fin-track: #ececf7; --fin-surface: #ffffff;
    }
    html.dark .fin-viz {
        --fin-s1: #e2e3e9; --fin-s2: #6f72a8;
        --fin-r1: #3a3d55; --fin-r2: #5e6190; --fin-r3: #9a9cc8; --fin-r4: #e2e3e9;
        --fin-grid: #334155; --fin-ink: #e2e8f0; --fin-muted: #94a3b8; --fin-track: #1e3a5f; --fin-surface: #1e293b;
    }

    .fin-grade { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr)); gap: 16px; margin-bottom: 16px; }
    .fin-kpis { display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    @media (max-width: 1100px) { .fin-kpis { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 560px) { .fin-kpis { grid-template-columns: 1fr; } }
    .fin-span2 { grid-column: span 2; }
    @media (max-width: 900px) { .fin-span2 { grid-column: auto; } }

    .fin-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 20px; }
    .fin-card h2 { margin: 0; font-size: 15px; font-weight: 700; color: #111827; }
    .fin-card .fin-sub { margin: 3px 0 14px; font-size: 12.5px; color: #6b7280; }

    .fin-rotulo { font-size: 11.5px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; }
    .fin-hero { margin-top: 6px; font-size: 28px; line-height: 1.1; font-weight: 700; color: #111827; }
    .fin-num { margin-top: 6px; font-size: 24px; font-weight: 700; color: #111827; }
    .fin-nota { margin-top: 4px; font-size: 12.5px; color: #6b7280; }

    .fin-legenda { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 8px; font-size: 12.5px; color: var(--fin-muted); }
    .fin-legenda i { display: inline-block; width: 10px; height: 10px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }

    .fin-svg { width: 100%; height: auto; display: block; overflow: visible; }
    .fin-svg text { font-family: inherit; font-size: 11px; fill: var(--fin-muted); }
    .fin-svg .fin-linha { stroke: var(--fin-grid); stroke-width: 1; }
    .fin-a { fill: var(--fin-s1); }
    .fin-b { fill: var(--fin-s2); }
    .fin-grupo .fin-hit { fill: transparent; }
    .fin-grupo:hover .fin-hit { fill: var(--fin-grid); fill-opacity: 0.35; }
    .fin-grupo:hover path { opacity: 0.88; }

    .fin-barra-linha { display: grid; grid-template-columns: minmax(80px, 140px) 1fr auto; gap: 10px; align-items: center; padding: 5px 0; font-size: 13px; }
    .fin-barra-linha a, .fin-barra-linha .fin-nome { color: #111827; text-decoration: none; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fin-barra-linha a:hover { text-decoration: underline; }
    .fin-trilho { height: 14px; background: transparent; border-radius: 0 4px 4px 0; }
    .fin-fio { height: 14px; min-width: 3px; border-radius: 0 4px 4px 0; transition: opacity .15s; }
    .fin-barra-linha:hover .fin-fio { opacity: 0.85; }
    .fin-valor { font-variant-numeric: tabular-nums; font-weight: 600; color: #111827; white-space: nowrap; }

    .fin-medidor { height: 10px; background: var(--fin-track); border-radius: 999px; overflow: hidden; margin-top: 10px; }
    .fin-medidor > div { height: 100%; background: var(--fin-s1); border-radius: 999px; }

    .fin-tabela-alt { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-top: 8px; }
    .fin-tabela-alt th, .fin-tabela-alt td { padding: 5px 8px; text-align: right; border-top: 1px solid var(--fin-grid); }
    .fin-tabela-alt th:first-child, .fin-tabela-alt td:first-child { text-align: left; }
    .fin-detalhes summary { cursor: pointer; font-size: 12.5px; color: #6b7280; margin-top: 10px; }

    .fin-pagamento { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid #f3f4f6; font-size: 13px; }
    .fin-pagamento:first-child { border-top: 0; }
</style>
