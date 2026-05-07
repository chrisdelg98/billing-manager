<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Pagos</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('pagos.index') }}" class="flex w-full max-w-md items-center gap-2">
                <select name="service_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    <option value="">Todos los servicios</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @selected((int) request('service_id') === $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filtrar</button>
            </form>

            <a href="{{ route('pagos.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Registrar pago
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
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
                            <tr>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->paid_at?->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $payment->service?->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->subscription?->name ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($payment->method) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $payment->reference ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('comprobantes.pagos.show', $payment) }}" class="ui-btn rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 transition hover:bg-emerald-50">Comprobante</a>
                                        <a href="{{ route('pagos.edit', $payment) }}" class="ui-btn rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Editar</a>
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
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No hay pagos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $payments->links() }}
    </div>
</x-app-layout>
