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

                    <tr>
                        <td style="background:#1f2937; padding:20px 24px;">
                            <h2 style="margin:0; color:#ffffff; font-size:22px; font-weight:bold;">
                                Nova Requisição de Compra
                            </h2>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Uma nova requisição foi cadastrada no sistema.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-size:14px;">
                                <tr style="background:#f9fafb;">
                                    <td style="padding:12px; border:1px solid #e5e7eb; width:180px;"><strong>Vendedor</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->user->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Produto</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->product_name }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Código</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->product_code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Quantidade</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->quantity }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Motivo</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->reason }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Urgência</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">
                                        @php
                                            $urgencyColor = '#16a34a';

                                            if ($purchaseRequest->urgency === 'alta') {
                                                $urgencyColor = '#dc2626';
                                            } elseif ($purchaseRequest->urgency === 'media') {
                                                $urgencyColor = '#f59e0b';
                                            }
                                        @endphp

                                        <span style="display:inline-block; padding:6px 12px; border-radius:6px; color:#ffffff; background:{{ $urgencyColor }};">
                                            {{ ucfirst($purchaseRequest->urgency) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="padding:12px; border:1px solid #e5e7eb; vertical-align:top;"><strong>Justificativa</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->justification }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Status</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ ucfirst($purchaseRequest->status) }}</td>
                                </tr>
                                <tr style="background:#f9fafb;">
                                    <td style="padding:12px; border:1px solid #e5e7eb;"><strong>Data</strong></td>
                                    <td style="padding:12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f3f4f6; padding:15px; text-align:center; font-size:12px; color:#6b7280;">
                            Sistema de Requisições • Enviado automaticamente
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>