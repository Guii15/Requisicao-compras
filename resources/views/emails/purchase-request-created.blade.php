<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Nova Requisição de Compra</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, sans-serif; color:#333333;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8; padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">

                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background:linear-gradient(90deg,#05018D,#b40000); padding:24px;">
                            <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-height:40px; max-width:150px; object-fit:contain; display:block; margin-bottom:12px;">
                            <h2 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                Nova Requisição de Compra
                            </h2>
                            <p style="margin:4px 0 0; color:rgba(255,255,255,0.75); font-size:13px;">
                                Enviada por <strong>{{ $first->requester_name }}</strong> em {{ $first->created_at->format('d/m/Y \à\s H:i') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Informações gerais --}}
                    <tr>
                        <td style="padding:20px 24px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-size:14px;">
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb; width:140px;"><strong>Vendedor</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $first->requester_name }}</td>
                                </tr>
                                @if($first->supplier)
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Fornecedor</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $first->supplier }}</td>
                                </tr>
                                @endif
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Urgência</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">
                                        @php $cor = $first->urgency === 'alta' ? '#dc2626' : ($first->urgency === 'media' ? '#f59e0b' : '#16a34a'); @endphp
                                        <span style="display:inline-block; padding:3px 10px; border-radius:6px; color:#fff; background:{{ $cor }}; font-size:13px;">
                                            {{ ucfirst($first->urgency) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Motivo</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $first->reason }}</td>
                                </tr>
                                @if($first->justification)
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb; vertical-align:top;"><strong>Obs</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $first->justification }}</td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Lista de produtos --}}
                    <tr>
                        <td style="padding:20px 24px;">
                            <p style="margin:0 0 10px; font-size:13px; font-weight:bold; color:#05018D; text-transform:uppercase; letter-spacing:0.5px;">
                                Itens ({{ count($purchaseRequests) }})
                            </p>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-size:13px;">
                                <tr style="background:#05018D;">
                                    <th style="padding:9px 12px; text-align:left; color:#fff; font-weight:600;">#</th>
                                    <th style="padding:9px 12px; text-align:left; color:#fff; font-weight:600;">Código</th>
                                    <th style="padding:9px 12px; text-align:left; color:#fff; font-weight:600;">Produto</th>
                                    <th style="padding:9px 12px; text-align:center; color:#fff; font-weight:600;">Qtd</th>
                                </tr>
                                @foreach($purchaseRequests as $i => $req)
                                <tr style="background: {{ $i % 2 === 0 ? '#fff' : '#f9fafb' }};">
                                    <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#9ca3af;">{{ $i + 1 }}</td>
                                    <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#6b7280;">{{ $req->product_code ?: '—' }}</td>
                                    <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; font-weight:500;">{{ $req->product_name }}</td>
                                    <td style="padding:9px 12px; border-bottom:1px solid #e5e7eb; text-align:center; font-weight:700;">{{ $req->quantity }}</td>
                                </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="background:#f3f4f6; padding:14px; text-align:center; font-size:12px; color:#6b7280; border-top:3px solid #05018D;">
                            Binário Tecnologia • Sistema de Requisições de Compras • Enviado automaticamente
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
