<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
        }

        .voucher {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            margin: 8px;
        }

        .header {
            padding: 14px 16px;
            border-bottom: 1px solid #cbd5e1;
        }

        .title {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
        }

        .meta {
            margin-top: 5px;
            color: #475569;
            font-size: 11px;
        }

        .status {
            margin-top: 8px;
            display: inline-block;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
        }

        .status.pending {
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #92400e;
        }

        .status.confirmed {
            border: 1px solid #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table td {
            width: 50%;
            border: 1px solid #e2e8f0;
            padding: 10px 12px;
            vertical-align: top;
        }

        .label {
            color: #64748b;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .value {
            font-size: 12px;
            font-weight: 600;
            word-break: break-word;
        }

        .notes {
            border-top: 1px solid #e2e8f0;
            padding: 10px 12px;
            color: #475569;
            white-space: pre-line;
            line-height: 1.5;
            font-size: 11px;
        }

        .notes strong {
            color: #0f172a;
        }
    </style>
</head>
<body>
    <section class="voucher">
        <header class="header">
            <h1 class="title">{{ $isPending ? 'Orden de pago' : 'Comprobante de pago' }}</h1>
            <div class="meta">{{ $isPending ? 'Orden' : 'Comprobante' }}: {{ $voucherNumber }} | Generado: {{ now()->format('Y-m-d H:i') }}</div>
            <span class="status {{ $isPending ? 'pending' : 'confirmed' }}">{{ $isPending ? 'PAGO PENDIENTE' : 'PAGO CONFIRMADO' }}</span>
        </header>

        <table class="table">
            <tr>
                <td>
                    <div class="label">Servicio</div>
                    <div class="value">{{ $payment->service?->name ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Suscripcion</div>
                    <div class="value">{{ $payment->subscription?->name ?: '-' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Ciclo</div>
                    <div class="value">{{ ucfirst((string) ($payment->subscription?->billing_cycle ?? '-')) }}</div>
                </td>
                <td>
                    <div class="label">Monto</div>
                    <div class="value">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Estado</div>
                    <div class="value">{{ $isPending ? 'Pago pendiente' : 'Pagado' }}</div>
                </td>
                <td>
                    <div class="label">Fecha de emision</div>
                    <div class="value">{{ $payment->paid_at?->format('Y-m-d') ?: now()->format('Y-m-d') }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Metodo</div>
                    <div class="value">{{ $isPending ? 'Por confirmar' : $payment->methodLabel() }}</div>
                </td>
                <td>
                    <div class="label">Referencia</div>
                    <div class="value">{{ $payment->reference ?: '-' }}</div>
                </td>
            </tr>
            @if (! $isPending && $payment->subscription?->next_renewal_at)
                <tr>
                    <td colspan="2">
                        <div class="label">Proxima fecha de renovacion</div>
                        <div class="value">{{ $payment->subscription->next_renewal_at->format('Y-m-d') }}</div>
                    </td>
                </tr>
            @endif
        </table>

        @if (! empty($payment->notes))
            <div class="notes"><strong>Notas:</strong> {{ $payment->notes }}</div>
        @endif
    </section>
</body>
</html>
