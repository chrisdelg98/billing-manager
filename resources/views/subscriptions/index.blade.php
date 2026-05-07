<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Suscripciones</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <form method="GET" action="{{ route('suscripciones.index') }}" class="grid w-full max-w-2xl gap-2 sm:grid-cols-3">
                <select name="service_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    <option value="">Todos los servicios</option>
                    @foreach($services as $service)
                        <option value="{{ $service->id }}" @selected((int) request('service_id') === $service->id)>{{ $service->name }}</option>
                    @endforeach
                </select>

                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    <option value="">Todos los estados</option>
                    <option value="active" @selected(request('status') === 'active')>Activas</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Inactivas</option>
                </select>

                <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filtrar</button>
            </form>

            <a href="{{ route('suscripciones.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Nueva suscripcion
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
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Servicio</th>
                            <th class="px-4 py-3">Ciclo</th>
                            <th class="px-4 py-3">Monto</th>
                            <th class="px-4 py-3">Renovacion</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($subscriptions as $subscription)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $subscription->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $subscription->service?->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($subscription->billing_cycle) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $subscription->next_renewal_at?->format('Y-m-d') ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if (! $subscription->is_active)
                                        Inactiva
                                    @elseif ($subscription->isInTrial())
                                        En prueba
                                    @else
                                        Activa
                                    @endif

                                    @if ($subscription->has_trial)
                                        <p class="mt-1 text-xs text-slate-500">{{ $subscription->trialStatusLabel() }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('pagos.create', ['service_id' => $subscription->service_id, 'subscription_id' => $subscription->id]) }}" class="ui-btn rounded-lg border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50">Generar pago</a>

                                        <form method="POST" action="{{ route('suscripciones.duplicate', $subscription) }}" onsubmit="return confirm('Se duplicara la suscripcion y se abrira para edicion. Continuar?')">
                                            @csrf
                                            <button type="submit" class="ui-btn rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50">Duplicar</button>
                                        </form>

                                        <a href="{{ route('suscripciones.edit', $subscription) }}" class="ui-btn rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Editar</a>
                                        <form method="POST" action="{{ route('suscripciones.destroy', $subscription) }}" onsubmit="return confirm('Se eliminara la suscripcion. Continuar?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-btn rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-50">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No hay suscripciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $subscriptions->links() }}
    </div>
</x-app-layout>
