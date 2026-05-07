<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $isPending ? 'Orden de pago' : 'Comprobante de pago' }}</title>
    <style>
        :root {
            color-scheme: light;
            --text: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --surface: #ffffff;
            --bg: #f1f5f9;
            --accent: #0f172a;
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
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge.pending {
            border: 1px solid #fcd34d;
            color: #92400e;
            background: #fffbeb;
        }

        .badge.confirmed {
            border: 1px solid #86efac;
            color: #166534;
            background: #f0fdf4;
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

        .notes {
            margin: 0 22px 22px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 12px;
            color: var(--muted);
            font-size: 13px;
            white-space: pre-line;
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
            <a href="{{ route('pagos.index') }}" class="btn">Volver a pagos</a>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('comprobantes.pagos.show', ['payment' => $payment, 'format' => 'pdf']) }}" class="btn">Descargar PDF</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">{{ $isPending ? 'Imprimir orden' : 'Imprimir comprobante' }}</button>
            </div>
        </div>

        <section class="card">
            <header class="header">
                <div>
                    <h1 class="title">{{ $isPending ? 'Orden de pago' : 'Comprobante de pago' }}</h1>
                    <div class="meta">{{ $isPending ? 'Orden' : 'Comprobante' }}: {{ $voucherNumber }} | Generado: {{ now()->format('Y-m-d H:i') }}</div>
                </div>
                <span class="badge {{ $isPending ? 'pending' : 'confirmed' }}">{{ $isPending ? 'PAGO PENDIENTE' : 'PAGO CONFIRMADO' }}</span>
            </header>

            <div class="grid">
                <div class="item">
                    <div class="label">Servicio</div>
                    <div class="value">{{ $payment->service?->name ?: '-' }}</div>
                </div>

                <div class="item">
                    <div class="label">Suscripcion</div>
                    <div class="value">{{ $payment->subscription?->name ?: '-' }}</div>
                </div>

                <div class="item">
                    <div class="label">{{ $isPending ? 'Fecha de orden' : 'Fecha de pago' }}</div>
                    <div class="value">{{ $payment->paid_at?->format('Y-m-d') ?: '-' }}</div>
                </div>

                <div class="item">
                    <div class="label">Monto</div>
                    <div class="value">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</div>
                </div>

                <div class="item">
                    <div class="label">Metodo</div>
                    <div class="value">{{ $isPending ? 'Por confirmar' : $payment->methodLabel() }}</div>
                </div>

                <div class="item">
                    <div class="label">Referencia</div>
                    <div class="value">{{ $payment->reference ?: '-' }}</div>
                </div>
            </div>

            @if(! empty($payment->notes))
                <div class="notes">
                    <strong>Notas:</strong>
                    {{ $payment->notes }}
                </div>
            @endif
        </section>
    </div>
</body>
</html>
