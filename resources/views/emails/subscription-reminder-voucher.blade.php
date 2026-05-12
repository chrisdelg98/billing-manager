<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de pago</title>
</head>
<body style="margin:0; padding:0; background:transparent;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:transparent; margin:0; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:600px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                    <tr>
                        <td style="padding:18px 22px; background:#0f172a; border-bottom:1px solid #1f2937;">
                            <p style="margin:0; font-family:Arial, sans-serif; font-size:12px; line-height:18px; letter-spacing:1px; color:#94a3b8; text-transform:uppercase; font-weight:bold;">Billing Manager System</p>
                            <h1 style="margin:8px 0 0; font-family:Arial, sans-serif; font-size:24px; line-height:30px; color:#e5e7eb; font-weight:700;">Recordatorio de pago</h1>
                            <p style="margin:8px 0 0; font-family:Arial, sans-serif; font-size:13px; line-height:20px; color:#94a3b8;">Voucher {{ $voucherNumber }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px;">
                            <p style="margin:0 0 12px; font-family:Arial, sans-serif; font-size:16px; line-height:24px; color:#0f172a; font-weight:700;">
                                Hola{{ !empty($recipientName) ? ', '.$recipientName : '' }}
                            </p>
                            <p style="margin:0 0 18px; font-family:Arial, sans-serif; font-size:15px; line-height:24px; color:#334155;">
                                Este mensaje es un recordatorio de pago para la suscripcion <strong style="color:#0f172a;">{{ $subscription->name }}</strong>. Adjuntamos el voucher en PDF para tu control.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e2e8f0; border-radius:8px; background:#ffffff;">
                                <tr>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0; width:50%;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Servicio</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $subscription->service?->name ?: '-' }}</p>
                                    </td>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0; width:50%;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Monto</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Fecha de vencimiento</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $subscription->next_renewal_at?->format('Y-m-d') ?: '-' }}</p>
                                    </td>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Ultimo dia de pago</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $lastPaymentDate?->format('Y-m-d') ?: '-' }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px;" colspan="2">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Estado de renovacion</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">
                                            @if (is_null($daysUntilRenewal))
                                                Sin fecha de renovacion definida
                                            @elseif ($daysUntilRenewal < 0)
                                                Vencida hace {{ abs($daysUntilRenewal) }} dia(s)
                                            @elseif ($daysUntilRenewal === 0)
                                                Vence hoy
                                            @else
                                                Restan {{ $daysUntilRenewal }} dia(s) para vencer
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:22px; color:#334155;">
                                Para gestionar el pago, comunicate con <strong style="color:#0f172a;">Christian Arevalo</strong>.
                            </p>

                            <p style="margin:8px 0 0; font-family:Arial, sans-serif; font-size:13px; line-height:20px; color:#64748b;">
                                Nota: este correo no incluye enlaces de pago.
                            </p>

                            @include('emails.partials.automated-notice')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
