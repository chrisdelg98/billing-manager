<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenida - {{ $subscription->name }}</title>
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
            border: 1px solid #86efac;
            color: #166534;
            background: #f0fdf4;
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

        .item.full {
            grid-column: span 2;
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

        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .item.full { grid-column: span 1; }
            .header { flex-direction: column; }
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
                    <h2 class="title" style="font-size: 18px;">Enviar bienvenida por correo</h2>
                    <div class="meta">Se enviara el correo de bienvenida con los detalles de la suscripcion.</div>
                </div>
            </header>
            <form method="POST" action="{{ route('comprobantes.suscripciones.bienvenida.send', $subscription) }}" style="padding: 0 22px 22px;">
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
                    <button type="submit" class="btn btn-primary">Enviar bienvenida por correo</button>
                </div>
            </form>
        </section>

        <div class="toolbar">
            <a href="{{ route('suscripciones.index') }}" class="btn">Volver a suscripciones</a>
        </div>

        <section class="card">
            <header class="header">
                <div>
                    <h1 class="title">Bienvenido a tu suscripcion</h1>
                    <div class="meta">Voucher: {{ $voucherNumber }} | Generado: {{ now()->format('Y-m-d H:i') }}</div>
                </div>
                <span class="badge">ACTIVA</span>
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
                    <div class="label">Monto</div>
                    <div class="value">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</div>
                </div>

                <div class="item">
                    <div class="label">Fecha de vencimiento</div>
                    <div class="value">{{ $subscription->next_renewal_at?->format('Y-m-d') ?: '-' }}</div>
                </div>

                <div class="item">
                    <div class="label">Ultimo dia de pago</div>
                    <div class="value">{{ $lastPaymentDate?->format('Y-m-d') ?: '-' }}</div>
                </div>

                <div class="item full">
                    <div class="label">Estado de renovacion</div>
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
        </section>
    </div>
</body>
</html>
