<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voucher de recordatorio</title>
    <style>
        :root {
            color-scheme: light;
            --text: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --surface: #ffffff;
            --bg: #f8fafc;
            --accent: #7c2d12;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .wrap {
            max-width: 920px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }

        .btn {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--text);
            padding: 9px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--surface);
            overflow: hidden;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: start;
            padding: 22px;
            border-bottom: 1px solid var(--line);
        }

        .title {
            font-size: 24px;
            margin: 0;
        }

        .meta {
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
        }

        .badge {
            border: 1px solid #fdba74;
            color: #9a3412;
            background: #fff7ed;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 22px;
        }

        .item {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
        }

        .label {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .value {
            font-size: 15px;
            font-weight: 600;
        }

        .foot {
            margin: 0 22px 22px;
            border: 1px dashed #fb923c;
            border-radius: 10px;
            padding: 12px;
            font-size: 13px;
            color: #9a3412;
            background: #fff7ed;
        }

        @media (max-width: 720px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
            }
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .wrap { margin: 0; max-width: none; padding: 0; }
            .card { border: 0; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="toolbar">
            <a href="{{ route('suscripciones.index') }}" class="btn">Volver a suscripciones</a>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('comprobantes.suscripciones.recordatorio', ['subscription' => $subscription, 'format' => 'pdf']) }}" class="btn">Descargar PDF</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir recordatorio</button>
            </div>
        </div>

        <section class="card">
            <header class="header">
                <div>
                    <h1 class="title">Voucher de recordatorio de pago</h1>
                    <div class="meta">Voucher: {{ $voucherNumber }} | Generado: {{ now()->format('Y-m-d H:i') }}</div>
                </div>
                <span class="badge">PENDIENTE DE PAGO</span>
            </header>

            <div class="grid">
                <div class="item">
                    <div class="label">Servicio</div>
                    <div class="value">{{ $subscription->service?->name ?: '-' }}</div>
                </div>

                <div class="item">
                    <div class="label">Suscripcion</div>
                    <div class="value">{{ $subscription->name }}</div>
                </div>

                <div class="item">
                    <div class="label">Ciclo</div>
                    <div class="value">{{ ucfirst($subscription->billing_cycle) }}</div>
                </div>

                <div class="item">
                    <div class="label">Monto esperado</div>
                    <div class="value">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</div>
                </div>

                <div class="item">
                    <div class="label">Fecha de renovacion</div>
                    <div class="value">{{ $subscription->next_renewal_at?->format('Y-m-d') ?: '-' }}</div>
                </div>

                <div class="item">
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
                </div>
            </div>

            <div class="foot">
                Este documento es un recordatorio de pago. Cuando el pago sea confirmado, se emitira el comprobante final.
            </div>
        </section>
    </div>
</body>
</html>
