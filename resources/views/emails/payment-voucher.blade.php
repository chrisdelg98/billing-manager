<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.5;">
    <p>Hola{{ !empty($payment->recipient_name) ? ' '.$payment->recipient_name : '' }},</p>

    <p>
        {{ $isPending ? 'Te compartimos tu orden de pago' : 'Te compartimos tu comprobante de pago' }}
        <strong>{{ $voucherNumber }}</strong>
        del servicio <strong>{{ $payment->service?->name ?: 'N/A' }}</strong>.
    </p>

    <p>
        Monto: <strong>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</strong><br>
        Fecha: <strong>{{ $payment->paid_at?->format('Y-m-d') ?: '-' }}</strong>
    </p>

    <p>Adjuntamos el documento en PDF para tu control.</p>

    <p>Saludos.</p>
</body>
</html>
