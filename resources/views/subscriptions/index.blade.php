<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Suscripciones</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="mobile-filters-shell rounded-xl border border-slate-200 bg-white p-4" x-data="{ mobileFiltersOpen: false }">
            <button type="button" class="mobile-filters-toggle ui-btn mb-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="mobileFiltersOpen = !mobileFiltersOpen" :aria-expanded="mobileFiltersOpen.toString()">
                <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
                <span x-text="mobileFiltersOpen ? 'Ocultar filtros' : 'Mostrar filtros'"></span>
            </button>

            <form method="GET" action="{{ route('suscripciones.index') }}" class="space-y-3" x-show="mobileFiltersOpen" x-transition.opacity.duration.150ms x-cloak>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="w-full lg:max-w-xl">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por nombre o servicio"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filtrar</button>
                        <a href="{{ route('suscripciones.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Limpiar</a>
                        <a href="{{ route('suscripciones.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Nueva suscripcion
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
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

                    <select name="billing_cycle" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los ciclos</option>
                        <option value="monthly" @selected(request('billing_cycle') === 'monthly')>Mensual</option>
                        <option value="yearly" @selected(request('billing_cycle') === 'yearly')>Anual</option>
                    </select>

                    <select name="trial_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los periodos de prueba</option>
                        <option value="in_trial" @selected(request('trial_status') === 'in_trial')>En prueba</option>
                        <option value="trial_ended" @selected(request('trial_status') === 'trial_ended')>Prueba finalizada</option>
                        <option value="no_trial" @selected(request('trial_status') === 'no_trial')>Sin prueba</option>
                    </select>

                    <select name="renewal_risk" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los riesgos</option>
                        <option value="safe" @selected(request('renewal_risk') === 'safe')>Sin riesgo</option>
                        <option value="warning" @selected(request('renewal_risk') === 'warning')>Proximo a vencer</option>
                        <option value="danger" @selected(request('renewal_risk') === 'danger')>Urgente / Vencido</option>
                    </select>

                    <select name="renewal_window" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todas las renovaciones</option>
                        <option value="overdue" @selected(request('renewal_window') === 'overdue')>Vencidas</option>
                        <option value="next_7" @selected(request('renewal_window') === 'next_7')>Proximos 7 dias</option>
                        <option value="next_30" @selected(request('renewal_window') === 'next_30')>Proximos 30 dias</option>
                        <option value="no_date" @selected(request('renewal_window') === 'no_date')>Sin fecha</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600">
            <span class="font-medium text-slate-700">Riesgo de renovacion</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-transparent ring-1 ring-inset ring-slate-300"></span>Sin riesgo</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400/70"></span>Proximo a vencer</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-rose-500/70"></span>Urgente o vencido</span>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="mobile-table-compact min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="mobile-col-main px-4 py-3">Nombre</th>
                            <th class="mobile-col-main px-4 py-3">Servicio</th>
                            <th class="mobile-col-100 px-4 py-3">Ciclo</th>
                            <th class="mobile-col-100 px-4 py-3">Monto</th>
                            <th class="mobile-col-120 px-4 py-3">Renovacion</th>
                            <th class="mobile-col-120 px-4 py-3">Estado</th>
                            <th class="mobile-col-120 px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($subscriptions as $subscription)
                            @php
                                $riskLevel = $subscription->renewalRiskLevel();
                                $riskBarClass = match ($riskLevel) {
                                    'warning' => 'bg-amber-400/70',
                                    'danger' => 'bg-rose-500/70',
                                    default => 'bg-transparent ring-1 ring-inset ring-slate-200',
                                };
                                $daysToRenewal = $subscription->daysUntilRenewal();
                            @endphp
                            <tr>
                                <td class="px-2 py-3 align-top">
                                    <span class="mx-auto block h-12 w-1.5 rounded-full {{ $riskBarClass }}"></span>
                                </td>
                                <td class="mobile-col-main px-4 py-3 font-medium text-slate-900"><span class="mobile-clamp-2">{{ $subscription->name }}</span></td>
                                <td class="mobile-col-main px-4 py-3 text-slate-700"><span class="mobile-clamp-2">{{ $subscription->service?->name }}</span></td>
                                <td class="mobile-col-100 mobile-nowrap px-4 py-3 text-slate-700">{{ ucfirst($subscription->billing_cycle) }}</td>
                                <td class="mobile-col-100 mobile-nowrap px-4 py-3 text-slate-700">{{ number_format((float) $subscription->amount, 2) }} {{ $subscription->currency }}</td>
                                <td class="mobile-col-120 mobile-nowrap px-4 py-3 text-slate-700">{{ $subscription->next_renewal_at?->format('Y-m-d') ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if (! $subscription->is_active)
                                        Inactiva
                                    @elseif ($subscription->isInTrial())
                                        En prueba
                                    @else
                                        Activa
                                    @endif

                                    @if (! is_null($daysToRenewal))
                                        <p class="mt-1 text-xs text-slate-500">
                                            @if ($daysToRenewal < 0)
                                                Vencida hace {{ abs($daysToRenewal) }} dia(s)
                                            @elseif ($daysToRenewal === 0)
                                                Vence hoy
                                            @else
                                                Vence en {{ $daysToRenewal }} dia(s)
                                            @endif
                                        </p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div
                                        class="inline-flex"
                                        x-data="{
                                            open: false,
                                            menuStyle: '',
                                            toggleMenu() {
                                                if (this.open) {
                                                    this.closeMenu();
                                                    return;
                                                }

                                                this.open = true;
                                                this.$nextTick(() => this.positionMenu());
                                            },
                                            closeMenu() {
                                                this.open = false;
                                            },
                                            positionMenu() {
                                                const trigger = this.$refs.trigger;

                                                if (!trigger) {
                                                    return;
                                                }

                                                const rect = trigger.getBoundingClientRect();
                                                const menuWidth = 176;
                                                const menuHeight = 230;
                                                const viewportPadding = 8;
                                                const gap = 8;

                                                let left = rect.right - menuWidth;
                                                left = Math.max(viewportPadding, Math.min(left, window.innerWidth - menuWidth - viewportPadding));

                                                let top = rect.bottom + gap;
                                                const canOpenUp = (rect.top - gap - menuHeight) > viewportPadding;
                                                const overflowsBottom = (top + menuHeight) > (window.innerHeight - viewportPadding);

                                                if (overflowsBottom && canOpenUp) {
                                                    top = rect.top - gap - menuHeight;
                                                }

                                                top = Math.max(viewportPadding, top);
                                                this.menuStyle = `left:${left}px; top:${top}px; width:${menuWidth}px;`;
                                            },
                                        }"
                                        @resize.window="if (open) { positionMenu() }"
                                        @scroll.window="if (open) { positionMenu() }"
                                    >
                                        <button
                                            type="button"
                                            x-ref="trigger"
                                            class="ui-btn inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                                            @click="toggleMenu()"
                                            @keydown.escape.window="closeMenu()"
                                            :aria-expanded="open.toString()"
                                            aria-haspopup="true"
                                        >
                                            Acciones
                                            <x-heroicon-o-chevron-down class="h-3.5 w-3.5" />
                                        </button>

                                        <template x-teleport="body">
                                            <div
                                                x-cloak
                                                x-show="open"
                                                x-transition.opacity.duration.120ms
                                                class="fixed inset-0 z-[85]"
                                                @click="closeMenu()"
                                                @keydown.escape.window="closeMenu()"
                                            >
                                                <div
                                                    class="absolute rounded-xl border border-slate-200 bg-white p-2 shadow-lg"
                                                    :style="menuStyle"
                                                    @click.stop
                                                >
                                                    <div class="space-y-1 text-left">
                                                        <a
                                                            href="{{ route('comprobantes.suscripciones.recordatorio', $subscription) }}"
                                                            class="block rounded-lg border border-blue-300 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-50"
                                                            @click="closeMenu()"
                                                        >
                                                            Voucher
                                                        </a>

                                                        <a
                                                            href="{{ route('pagos.create', ['service_id' => $subscription->service_id, 'subscription_id' => $subscription->id]) }}"
                                                            class="block rounded-lg border border-indigo-300 px-3 py-2 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50"
                                                            @click="closeMenu()"
                                                        >
                                                            Generar pago
                                                        </a>

                                                        <form method="POST" action="{{ route('suscripciones.duplicate', $subscription) }}" onsubmit="return confirm('Se duplicara la suscripcion y se abrira para edicion. Continuar?')">
                                                            @csrf
                                                            <button type="submit" class="block w-full rounded-lg border border-orange-300 px-3 py-2 text-left text-xs font-medium text-orange-700 transition hover:bg-orange-50" @click="closeMenu()">Duplicar</button>
                                                        </form>

                                                        <a
                                                            href="{{ route('suscripciones.edit', $subscription) }}"
                                                            class="block rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                                            @click="closeMenu()"
                                                        >
                                                            Editar
                                                        </a>

                                                        <form method="POST" action="{{ route('suscripciones.destroy', $subscription) }}" onsubmit="return confirm('Se eliminara la suscripcion. Continuar?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="block w-full rounded-lg border border-red-300 px-3 py-2 text-left text-xs font-medium text-red-700 transition hover:bg-red-50" @click="closeMenu()">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">No hay suscripciones registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $subscriptions->links() }}
    </div>
</x-app-layout>
