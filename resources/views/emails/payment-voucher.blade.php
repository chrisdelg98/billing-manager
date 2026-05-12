<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isPending ? 'Orden de pago' : 'Comprobante de pago' }}</title>
</head>
<body style="margin:0; padding:0; background:transparent;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:transparent; margin:0; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:600px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                    <tr>
                        <td style="padding:18px 22px; background:#0f172a; border-bottom:1px solid #1f2937;">
                            <p style="margin:0; font-family:Arial, sans-serif; font-size:12px; line-height:18px; letter-spacing:1px; color:#94a3b8; text-transform:uppercase; font-weight:bold;">Billing Manager System</p>
                            <h1 style="margin:8px 0 0; font-family:Arial, sans-serif; font-size:24px; line-height:30px; color:#e5e7eb; font-weight:700;">
                                {{ $isPending ? 'Orden de pago' : 'Comprobante de pago' }}
                            </h1>
                            <p style="margin:8px 0 0; font-family:Arial, sans-serif; font-size:13px; line-height:20px; color:#94a3b8;">Voucher {{ $voucherNumber }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px;">
                            <p style="margin:0 0 12px; font-family:Arial, sans-serif; font-size:16px; line-height:24px; color:#0f172a; font-weight:700;">
                                Hola{{ !empty($recipientName) ? ', '.$recipientName : (!empty($payment->recipient_name) ? ', '.$payment->recipient_name : '') }}
                            </p>
                            @if ($isPending)
                                <p style="margin:0 0 18px; font-family:Arial, sans-serif; font-size:15px; line-height:24px; color:#334155;">
                                    Te compartimos la <strong style="color:#0f172a;">orden de pago {{ $voucherNumber }}</strong> correspondiente a tu suscripcion <strong style="color:#0f172a;">{{ $payment->subscription?->name ?: 'N/A' }}</strong>. Aun se encuentra pendiente de pago.
                                </p>
                            @else
                                <p style="margin:0 0 18px; font-family:Arial, sans-serif; font-size:15px; line-height:24px; color:#334155;">
                                    Confirmamos que tu pago <strong style="color:#0f172a;">{{ $voucherNumber }}</strong> de la suscripcion <strong style="color:#0f172a;">{{ $payment->subscription?->name ?: 'N/A' }}</strong> se registro correctamente. No necesitas hacer nada mas.
                                </p>
                            @endif

                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e2e8f0; border-radius:8px; background:#ffffff;">
                                <tr>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0; width:50%;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Servicio</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $payment->service?->name ?: '-' }}</p>
                                    </td>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0; width:50%;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Suscripcion</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $payment->subscription?->name ?: '-' }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Ciclo</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ ucfirst((string) ($payment->subscription?->billing_cycle ?? '-')) }}</p>
                                    </td>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Monto</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Estado</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:{{ $isPending ? '#9a3412' : '#166534' }}; font-weight:700;">{{ $isPending ? 'Pago pendiente' : 'Pagado' }}</p>
                                    </td>
                                    <td style="padding:12px 14px; border-bottom:1px solid #e2e8f0;">
                                        <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Fecha de emision</p>
                                        <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $payment->paid_at?->format('Y-m-d') ?: now()->format('Y-m-d') }}</p>
                                    </td>
                                </tr>
                                @if (! $isPending && $payment->subscription?->next_renewal_at)
                                    <tr>
                                        <td style="padding:12px 14px;" colspan="2">
                                            <p style="margin:0; font-family:Arial, sans-serif; font-size:11px; line-height:16px; text-transform:uppercase; letter-spacing:.6px; color:#64748b;">Proxima fecha de renovacion</p>
                                            <p style="margin:4px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:20px; color:#0f172a; font-weight:700;">{{ $payment->subscription->next_renewal_at->format('Y-m-d') }}</p>
                                        </td>
                                    </tr>
                                @endif
                            </table>

                            @if ($isPending)
                                <p style="margin:18px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:22px; color:#334155;">
                                    Adjuntamos la orden de pago en PDF. Para gestionar el pago, comunicate con <strong style="color:#0f172a;">Christian Arevalo</strong>.
                                </p>
                            @else
                                <p style="margin:18px 0 0; font-family:Arial, sans-serif; font-size:14px; line-height:22px; color:#334155;">
                                    Adjuntamos el comprobante en PDF para tu control. Gracias por mantener tu suscripcion al dia.
                                </p>
                            @endif

                            @include('emails.partials.automated-notice')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
