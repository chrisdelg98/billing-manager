<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Pagos</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <form method="GET" action="{{ route('pagos.index') }}" class="space-y-3">
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="w-full lg:max-w-xl">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por servicio, suscripcion o referencia"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filtrar</button>
                        <a href="{{ route('pagos.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Limpiar</a>
                        <a href="{{ route('pagos.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Registrar pago
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
                    <select name="service_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los servicios</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" @selected((int) request('service_id') === $service->id)>{{ $service->name }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los estados</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendientes</option>
                        <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmados</option>
                    </select>

                    <select name="method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los metodos</option>
                        <option value="other" @selected(request('method') === 'other')>Por confirmar</option>
                        <option value="transfer" @selected(request('method') === 'transfer')>Transferencia</option>
                        <option value="cash" @selected(request('method') === 'cash')>Efectivo</option>
                        <option value="paypal" @selected(request('method') === 'paypal')>PayPal</option>
                    </select>

                    <select name="subscription_scope" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los cobros</option>
                        <option value="with_subscription" @selected(request('subscription_scope') === 'with_subscription')>Con suscripcion</option>
                        <option value="without_subscription" @selected(request('subscription_scope') === 'without_subscription')>Sin suscripcion</option>
                    </select>

                    <select name="paid_window" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todas las fechas</option>
                        <option value="today" @selected(request('paid_window') === 'today')>Hoy</option>
                        <option value="last_7" @selected(request('paid_window') === 'last_7')>Ultimos 7 dias</option>
                        <option value="last_30" @selected(request('paid_window') === 'last_30')>Ultimos 30 dias</option>
                        <option value="this_month" @selected(request('paid_window') === 'this_month')>Este mes</option>
                    </select>

                    <input
                        type="date"
                        name="paid_from"
                        value="{{ request('paid_from') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >

                    <input
                        type="date"
                        name="paid_to"
                        value="{{ request('paid_to') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600">
            <span class="font-medium text-slate-700">Estado de cobro</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400/70"></span>Pendiente</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-400/70"></span>Confirmado</span>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Servicio</th>
                            <th class="px-4 py-3">Suscripcion</th>
                            <th class="px-4 py-3">Monto</th>
                            <th class="px-4 py-3">Metodo</th>
                            <th class="px-4 py-3">Referencia</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($payments as $payment)
                            @php
                                $statusBarClass = $payment->isPending()
                                    ? 'bg-amber-400/70'
                                    : 'bg-emerald-400/70';
                            @endphp
                            <tr>
                                <td class="px-2 py-3 align-top">
                                    <span class="mx-auto block h-12 w-1.5 rounded-full {{ $statusBarClass }}"></span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if ($payment->isPending())
                                        Pendiente
                                        <p class="mt-1 text-xs text-slate-500">Orden de pago</p>
                                    @else
                                        Confirmado
                                        <p class="mt-1 text-xs text-slate-500">Pago aplicado</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->paid_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->service?->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->subscription?->name ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->isPending() ? 'Por confirmar' : $payment->methodLabel() }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->reference ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('comprobantes.pagos.show', $payment) }}" class="ui-btn rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-50">{{ $payment->isPending() ? 'Orden' : 'Comprobante' }}</a>
                                        <a href="{{ route('pagos.edit', $payment) }}" class="ui-btn rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">{{ $payment->isPending() ? 'Confirmar pago' : 'Editar' }}</a>
                                        <form method="POST" action="{{ route('pagos.destroy', $payment) }}" onsubmit="return confirm('Se eliminara el pago. Continuar?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-btn rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-50">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">No hay pagos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $payments->links() }}
    </div>
</x-app-layout>
