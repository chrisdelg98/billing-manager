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
            border: 1px solid #fdba74;
            background: #fff7ed;
            color: #9a3412;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
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

        .foot {
            border-top: 1px dashed #fdba74;
            padding: 10px 12px;
            color: #9a3412;
            line-height: 1.5;
            background: #fff7ed;
        }
    </style>
</head>
<body>
    <section class="voucher">
        <header class="header">
            <h1 class="title">Voucher de recordatorio de pago</h1>
            <div class="meta">Voucher: {{ $voucherNumber }} | Generado: {{ now()->format('Y-m-d H:i') }}</div>
            <span class="status">PENDIENTE DE PAGO</span>
        </header>

        <table class="table">
            <tr>
                <td>
                    <div class="label">Servicio</div>
                    <div class="value">{{ $subscription->service?->name ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Suscripcion</div>
                    <div class="value">{{ $subscription->name }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Ciclo</div>
                    <div class="value">{{ ucfirst($subscription->billing_cycle) }}</div>
                </td>
                <td>
                    <div class="label">Monto esperado</div>
                    <div class="value">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Fecha de renovacion</div>
                    <div class="value">{{ $subscription->next_renewal_at?->format('Y-m-d') ?: '-' }}</div>
                </td>
                <td>
                    <div class="label">Estado</div>
                    <div class="value">
                        @if (is_null($daysUntilRenewal))
                            Sin fecha de renovacion
                        @elseif ($daysUntilRenewal < 0)
                            Vencida hace {{ abs($daysUntilRenewal) }} dia(s)
                        @elseif ($daysUntilRenewal === 0)
                            Vence hoy
                        @else
                            Vence en {{ $daysUntilRenewal }} dia(s)
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="foot">
            Este documento funciona como recordatorio de pago. Una vez registrado el pago, emite el comprobante final para dejar evidencia de cobro.
        </div>
    </section>
</body>
</html>
