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
            border: 1px solid #cbd5e1;
            color: #334155;
            background: #f8fafc;
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
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            font-size: 12px;
            color: #334155;
            background: #f8fafc;
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
        @if (session('status'))
            <div style="margin-bottom: 12px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; padding: 10px 12px; border-radius: 8px; font-size: 13px;">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="margin-bottom: 12px; border: 1px solid #fca5a5; background: #fef2f2; color: #991b1b; padding: 10px 12px; border-radius: 8px; font-size: 13px;">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="card" style="margin-bottom: 12px;">
            <header class="header" style="padding-bottom: 14px;">
                <div>
                    <h2 class="title" style="font-size: 18px;">Enviar recordatorio por correo</h2>
                    <div class="meta">Se adjuntara automaticamente el voucher en PDF.</div>
                </div>
            </header>
            <form method="POST" action="{{ route('comprobantes.suscripciones.recordatorio.send', $subscription) }}" style="padding: 0 22px 22px;">
                @csrf
                <div class="grid" style="padding: 0;">
                    <div class="item">
                        <div class="label">Nombre destinatario (opcional)</div>
                        <input
                            type="text"
                            name="recipient_name"
                            value="{{ old('recipient_name', $subscription->billing_contact_name) }}"
                            style="width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 9px 10px; font-size: 14px;"
                            placeholder="Nombre del contacto"
                        >
                    </div>

                    <div class="item">
                        <div class="label">Correo destinatario</div>
                        <input
                            type="email"
                            name="recipient_email"
                            value="{{ old('recipient_email', $subscription->billing_contact_email) }}"
                            style="width: 100%; border: 1px solid var(--line); border-radius: 8px; padding: 9px 10px; font-size: 14px;"
                            placeholder="correo@empresa.com"
                            required
                        >
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                    <button type="submit" class="btn btn-primary">Enviar recordatorio por correo</button>
                </div>
            </form>
        </section>

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
                    <div class="label">Ultimo dia de pago</div>
                    <div class="value">{{ $lastPaymentDate?->format('Y-m-d') ?: '-' }}</div>
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
                Este documento es un recordatorio de pago. No incluye enlaces de pago. Para gestionar el pago, comunicarte con Christian Arevalo.
            </div>
        </section>
    </div>
</body>
</html>
