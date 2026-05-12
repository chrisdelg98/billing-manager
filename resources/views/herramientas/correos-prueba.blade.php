<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Pruebas de correo</h2>
            <p class="text-sm text-slate-600">Envia plantillas reales a tu propio correo ({{ $recipientEmail ?: 'sin correo configurado' }}) sin afectar a clientes.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @error('template')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="rounded-xl border border-slate-200 bg-white p-5"
             x-data="{
                template: 'orden_pago',
                payment_id: '{{ optional($pendingPayments->first())->id }}',
                paid_payment_id: '{{ optional($paidPayments->first())->id }}',
                subscription_id: '{{ optional($subscriptions->first())->id }}',
                welcome_subscription_id: '{{ optional($subscriptions->first())->id }}',
             }">
            <form method="POST" action="{{ route('herramientas.correos-prueba.send') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Plantilla</label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-3">
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm transition hover:border-slate-400" :class="template === 'orden_pago' ? 'border-slate-900 bg-white ring-2 ring-slate-900/10' : ''">
                            <input type="radio" name="template" value="orden_pago" x-model="template" class="mt-1">
                            <span>
                                <span class="block font-semibold text-slate-900">Orden de pago</span>
                                <span class="block text-xs text-slate-500">Pago pendiente, sin adjuntar PDF aqui.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm transition hover:border-slate-400" :class="template === 'comprobante_pago' ? 'border-slate-900 bg-white ring-2 ring-slate-900/10' : ''">
                            <input type="radio" name="template" value="comprobante_pago" x-model="template" class="mt-1">
                            <span>
                                <span class="block font-semibold text-slate-900">Comprobante de pago</span>
                                <span class="block text-xs text-slate-500">Confirmacion de pago realizado.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm transition hover:border-slate-400" :class="template === 'recordatorio' ? 'border-slate-900 bg-white ring-2 ring-slate-900/10' : ''">
                            <input type="radio" name="template" value="recordatorio" x-model="template" class="mt-1">
                            <span>
                                <span class="block font-semibold text-slate-900">Recordatorio</span>
                                <span class="block text-xs text-slate-500">Aviso de renovacion proxima/vencida.</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm transition hover:border-slate-400" :class="template === 'bienvenida' ? 'border-slate-900 bg-white ring-2 ring-slate-900/10' : ''">
                            <input type="radio" name="template" value="bienvenida" x-model="template" class="mt-1">
                            <span>
                                <span class="block font-semibold text-slate-900">Bienvenida</span>
                                <span class="block text-xs text-slate-500">Cuenta activa, detalles de suscripcion.</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div x-show="template === 'orden_pago'" x-cloak>
                    <label class="block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Pago pendiente</label>
                    @if ($pendingPayments->isEmpty())
                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">No hay pagos en estado pendiente. Crea uno desde Pagos.</p>
                    @else
                        <select name="payment_id" x-model="payment_id" :disabled="template !== 'orden_pago'" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                            @foreach ($pendingPayments as $p)
                                <option value="{{ $p->id }}">ORD-{{ sprintf('%06d', $p->id) }} · {{ $p->service?->name ?: 'N/A' }} · {{ $p->subscription?->name ?: 'N/A' }} · {{ number_format((float) $p->amount, 2) }} {{ $p->currency }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div x-show="template === 'comprobante_pago'" x-cloak>
                    <label class="block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Pago confirmado</label>
                    @if ($paidPayments->isEmpty())
                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">No hay pagos confirmados. Confirma uno desde Pagos.</p>
                    @else
                        <select name="payment_id" x-model="paid_payment_id" :disabled="template !== 'comprobante_pago'" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                            @foreach ($paidPayments as $p)
                                <option value="{{ $p->id }}">PAGO-{{ sprintf('%06d', $p->id) }} · {{ $p->service?->name ?: 'N/A' }} · {{ $p->subscription?->name ?: 'N/A' }} · {{ number_format((float) $p->amount, 2) }} {{ $p->currency }} · {{ $p->paid_at?->format('Y-m-d') }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div x-show="template === 'recordatorio'" x-cloak>
                    <label class="block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Suscripcion</label>
                    @if ($subscriptions->isEmpty())
                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">No hay suscripciones registradas.</p>
                    @else
                        <select name="subscription_id" x-model="subscription_id" :disabled="template !== 'recordatorio'" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                            @foreach ($subscriptions as $s)
                                <option value="{{ $s->id }}">RMD-{{ sprintf('%06d', $s->id) }} · {{ $s->service?->name ?: 'N/A' }} · {{ $s->name }} · {{ ucfirst((string) $s->billing_cycle) }} · vence {{ $s->next_renewal_at?->format('Y-m-d') ?: '-' }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div x-show="template === 'bienvenida'" x-cloak>
                    <label class="block text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Suscripcion</label>
                    @if ($subscriptions->isEmpty())
                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">No hay suscripciones registradas.</p>
                    @else
                        <select name="subscription_id" x-model="welcome_subscription_id" :disabled="template !== 'bienvenida'" class="mt-2 w-full rounded-lg border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900">
                            @foreach ($subscriptions as $s)
                                <option value="{{ $s->id }}">BNV-{{ sprintf('%06d', $s->id) }} · {{ $s->service?->name ?: 'N/A' }} · {{ $s->name }} · {{ ucfirst((string) $s->billing_cycle) }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                    <p class="text-xs text-slate-500">Destinatario: <span class="font-semibold text-slate-700">{{ $recipientEmail ?: '-' }}</span></p>
                    <button type="submit" class="ui-btn rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" @if (! $recipientEmail) disabled @endif>
                        Enviar correo de prueba
                    </button>
                </div>
            </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
            <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Notas</h3>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                <li>El correo solo se envia a tu propio email de usuario admin.</li>
                <li>Los datos usados son reales de la base de datos (servicios, suscripciones y pagos).</li>
                <li>El asunto se prefija con <strong>[PRUEBA]</strong> para identificarlo facilmente en la bandeja.</li>
                <li>No se adjunta PDF en estas pruebas; el contenido del cuerpo es identico al correo real.</li>
            </ul>
        </div>
    </div>
</x-app-layout>
