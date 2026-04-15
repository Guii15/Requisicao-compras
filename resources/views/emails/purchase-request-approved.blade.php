<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Compra Aprovada</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, sans-serif; color:#333333;">

    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8; padding:20px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb;">

                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background:linear-gradient(90deg,#05018D,#b40000); padding:24px;">
                            @if(file_exists(public_path('imagens/logo.png')))
                                <img src="{{ asset('imagens/logo.png') }}" alt="Binário" style="max-height:40px; max-width:150px; object-fit:contain; display:block; margin-bottom:12px;">
                            @endif
                            <h2 style="margin:0; color:#ffffff; font-size:20px; font-weight:bold;">
                                Compra Aprovada — Aguarde a Entrega
                            </h2>
                            <p style="margin:4px 0 0; color:rgba(255,255,255,0.75); font-size:13px;">
                                Aprovado em {{ now()->format('d/m/Y \à\s H:i') }}
                            </p>
                        </td>
                    </tr>

                    {{-- Aviso --}}
                    <tr>
                        <td style="padding:20px 24px 0;">
                            <div style="background:#dbeafe; border:1px solid #93c5fd; border-radius:8px; padding:14px 16px; font-size:14px; color:#1d4ed8;">
                                <strong>Atenção, pessoal da entrada:</strong> a compra abaixo foi aprovada. O produto deve chegar em breve. Por favor, esteja pronto para receber.
                            </div>
                        </td>
                    </tr>

                    {{-- Informações do pedido --}}
                    <tr>
                        <td style="padding:20px 24px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse; font-size:14px;">
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb; width:140px;"><strong>Produto</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb; font-weight:600; color:#05018D;">{{ $purchaseRequest->product_name }}</td>
                                </tr>
                                @if($purchaseRequest->product_code)
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Código</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->product_code }}</td>
                                </tr>
                                @endif
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Quantidade</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb; font-weight:700; font-size:16px;">{{ $purchaseRequest->quantity }}</td>
                                </tr>
                                @if($purchaseRequest->supplier)
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Fornecedor</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->supplier }}</td>
                                </tr>
                                @endif
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Solicitante</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->requester_name }}</td>
                                </tr>
                                @if($purchaseRequest->justification)
                                <tr>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;"><strong>Obs</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->justification }}</td>
                                </tr>
                                @endif
                                @if($purchaseRequest->admin_note)
                                <tr style="background:#f9fafb;">
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb; vertical-align:top;"><strong>Nota do compras</strong></td>
                                    <td style="padding:10px 12px; border:1px solid #e5e7eb;">{{ $purchaseRequest->admin_note }}</td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- Rodapé --}}
                    <tr>
                        <td style="background:#f3f4f6; padding:14px; text-align:center; font-size:12px; color:#6b7280; border-top:3px solid #05018D; margin-top:20px;">
                            Binário Tecnologia • Sistema de Requisições de Compras • Enviado automaticamente
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
