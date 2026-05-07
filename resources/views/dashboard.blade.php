<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">
            Dashboard
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Servicios</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $servicesCount }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Suscripciones activas</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $activeSubscriptionsCount }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Ingresos del mes</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($incomeThisMonth, 2) }} USD</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Margen proyectado</p>
                <p class="mt-2 text-2xl font-semibold {{ $netProjectionMonth >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($netProjectionMonth, 2) }} USD</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Proximas renovaciones de suscripciones</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($upcomingSubscriptions as $subscription)
                        <div class="flex items-center justify-between px-4 py-3 text-sm">
                            <div>
                                <p class="font-medium text-slate-900">{{ $subscription->name }}</p>
                                <p class="text-slate-500">{{ $subscription->service?->name ?: 'Sin servicio' }}</p>
                            </div>
                            <p class="text-slate-700">{{ $subscription->next_renewal_at?->format('Y-m-d') }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">Sin renovaciones próximas.</p>
                    @endforelse
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Proximas renovaciones de costos</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($upcomingCosts as $cost)
                        <div class="flex items-center justify-between px-4 py-3 text-sm">
                            <div>
                                <p class="font-medium text-slate-900">{{ $cost->name }}</p>
                                <p class="text-slate-500">{{ ucfirst($cost->category) }} · {{ number_format((float) $cost->amount, 2) }} {{ $cost->currency }} · {{ $cost->billingFrequencyLabel() }}</p>
                            </div>
                            <p class="text-slate-700">{{ $cost->next_renewal_at?->format('Y-m-d') }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-500">Sin renovaciones próximas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
