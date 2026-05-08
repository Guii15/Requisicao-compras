<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Requisição #{{ str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; color: #111827; }

        .toolbar {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 24px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .document {
            background: #fff;
            max-width: 820px;
            margin: 32px auto;
            padding: 52px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.10);
            border-radius: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 28px;
        }

        .info-box {
            background: #f9fafb;
            border-radius: 8px;
            padding: 14px 16px;
        }

        .info-label {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
        }

        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .products-table thead tr {
            background: linear-gradient(90deg, #05018D, #1d4ed8);
        }

        .products-table th {
            padding: 10px 16px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
        }

        .products-table th:last-child { text-align: center; }

        .products-table td {
            padding: 14px 16px;
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
            color: #374151;
        }

        .products-table td:last-child {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            color: #05018D;
        }

        .sign-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 32px;
            margin-top: 52px;
        }

        .sign-line {
            border-top: 1px solid #9ca3af;
            padding-top: 8px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        @media print {
            .toolbar { display: none !important; }
            body { background: #fff; }
            .document {
                margin: 0;
                padding: 28px 32px;
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .document { margin: 0; border-radius: 0; padding: 24px 16px; box-shadow: none; }
            .info-grid { grid-template-columns: 1fr; }
            .sign-grid { grid-template-columns: 1fr; gap: 20px; }
        }
    </style>
</head>
<body>

    {{-- Barra de ações (some ao imprimir) --}}
    <div class="toolbar">
        <button onclick="window.print()"
                style="background:#05018D; color:#fff; border:none; border-radius:7px; padding:10px 20px; font-size:14px; font-weight:700; cursor:pointer;">
            🖨️ Imprimir / Salvar PDF
        </button>
        <a href="{{ $waLink }}" target="_blank"
           style="background:#25D366; color:#fff; border-radius:7px; padding:10px 20px; font-size:14px; font-weight:700; cursor:pointer; text-decoration:none; display:inline-block;">
            📱 Enviar pelo WhatsApp
        </a>
        <button onclick="window.close()"
                style="background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; border-radius:7px; padding:10px 18px; font-size:14px; font-weight:600; cursor:pointer; margin-left:auto;">
            Fechar
        </button>
    </div>

    {{-- Documento --}}
    <div class="document">

        {{-- Cabeçalho --}}
        <div style="border-bottom: 3px solid #05018D; padding-bottom: 20px; margin-bottom: 28px; display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="font-size:26px; font-weight:800; color:#05018D; letter-spacing:-0.5px;">REQUISIÇÃO DE COMPRA</h1>
                <p style="font-size:13px; color:#9ca3af; margin-top:5px;">Binárioftec Tecnologia</p>
            </div>
            <div style="text-align:right;">
                <div style="font-size:22px; font-weight:800; color:#374151;">#{{ str_pad($req->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div style="font-size:13px; color:#9ca3af; margin-top:2px;">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Informações gerais --}}
        <div class="info-grid">
            <div class="info-box">
                <div class="info-label">Vendedor</div>
                <div class="info-value">{{ $req->requester_name }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Fornecedor</div>
                <div class="info-value">{{ $req->supplier ?: '—' }}</div>
            </div>
            <div class="info-box">
                <div class="info-label">Urgência</div>
                <div class="info-value" style="color:
                    @if($req->urgency === 'alta') #dc2626
                    @elseif($req->urgency === 'media') #d97706
                    @else #16a34a @endif;">
                    {{ $req->urgency === 'alta' ? 'Alta' : ($req->urgency === 'media' ? 'Média' : 'Baixa') }}
                </div>
            </div>
            <div class="info-box">
                <div class="info-label">Data do Pedido</div>
                <div class="info-value">{{ $req->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        {{-- Tabela de produtos --}}
        <div class="section-title">Produto(s) Solicitado(s)</div>
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width:140px;">Código</th>
                    <th>Descrição do Produto</th>
                    <th style="width:120px; text-align:center;">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="color:#9ca3af;">{{ $req->product_code ?: '—' }}</td>
                    <td style="font-weight:600; color:#111827;">{{ $req->product_name }}</td>
                    <td>{{ number_format($req->quantity, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Motivo e Observação --}}
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px;">
            <div>
                <div class="section-title">Motivo</div>
                <div style="background:#f9fafb; border-radius:8px; padding:12px 14px; font-size:14px; color:#374151; min-height:52px; line-height:1.5;">{{ $req->reason }}</div>
            </div>
            <div>
                <div class="section-title">Observação</div>
                <div style="background:#f9fafb; border-radius:8px; padding:12px 14px; font-size:14px; color:#374151; min-height:52px; line-height:1.5;">{{ $req->justification }}</div>
            </div>
        </div>

        {{-- Status / Nota admin (se houver) --}}
        @if($req->status !== 'pendente' || $req->admin_note)
            <div style="border-top:1px solid #f3f4f6; padding-top:18px; margin-bottom:8px; display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                <div class="section-title" style="margin-bottom:0;">Status</div>
                @if($req->status === 'aprovado')
                    <span style="background:#dcfce7; color:#16a34a; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700;">Aprovado</span>
                @elseif($req->status === 'rejeitado')
                    <span style="background:#fee2e2; color:#dc2626; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700;">Rejeitado</span>
                @else
                    <span style="background:#fef3c7; color:#d97706; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700;">Pendente</span>
                @endif
                @if($req->admin_note)
                    <span style="font-size:13px; color:#6b7280;">{{ $req->admin_note }}</span>
                @endif
            </div>
        @endif

        {{-- Assinaturas --}}
        <div class="sign-grid">
            <div class="sign-line">Solicitante</div>
            <div class="sign-line">Aprovação</div>
            <div class="sign-line">Compras</div>
        </div>

        {{-- Rodapé --}}
        <div style="text-align:center; margin-top:36px; padding-top:16px; border-top:1px solid #f3f4f6; font-size:11px; color:#d1d5db;">
            Documento gerado em {{ now()->format('d/m/Y \à\s H:i') }} — Sistema de Requisição de Compras
        </div>

    </div>

</body>
</html>
