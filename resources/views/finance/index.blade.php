<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Finanzas</h2>
    </x-slot>

    <div
        class="space-y-6"
        x-data="financeSection(@js($serviceRows->values()->all()))"
        @keydown.escape.window="closeDetail(); closeFilters()"
    >
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @php
            try {
                $periodCursor = \Carbon\Carbon::createFromFormat('Y-m', $period)->startOfMonth();
            } catch (\Throwable) {
                $periodCursor = now()->startOfMonth();
            }

            $previousPeriod = $periodCursor->copy()->subMonthNoOverflow()->format('Y-m');
            $nextPeriod = $periodCursor->copy()->addMonthNoOverflow()->format('Y-m');

            $periodNavQuery = [
                'q' => $filters['q'] ?? '',
                'service_status' => $filters['service_status'] ?? 'all',
                'profitability' => $filters['profitability'] ?? 'all',
                'sort' => $filters['sort'] ?? 'margin_desc',
                'limit' => $filters['limit'] ?? 20,
            ];

            if (! empty($filters['snapshot_only'])) {
                $periodNavQuery['snapshot_only'] = '1';
            }
        @endphp

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button type="button" class="ui-btn inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="openFilters()">
                        <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
                        <span>Filtros</span>
                    </button>

                    <form method="GET" action="{{ route('finanzas.index') }}" class="inline-flex">
                        @foreach($periodNavQuery as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="period" value="{{ $previousPeriod }}">
                        <button type="submit" class="ui-btn inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-50" title="Periodo anterior" aria-label="Periodo anterior">
                            <x-heroicon-o-chevron-left class="h-4 w-4" />
                        </button>
                    </form>

                    <form method="GET" action="{{ route('finanzas.index') }}" class="inline-flex">
                        @foreach($periodNavQuery as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <input type="hidden" name="period" value="{{ $nextPeriod }}">
                        <button type="submit" class="ui-btn inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-700 transition hover:bg-slate-50" title="Periodo siguiente" aria-label="Periodo siguiente">
                            <x-heroicon-o-chevron-right class="h-4 w-4" />
                        </button>
                    </form>

                    <div class="text-xs text-slate-500">
                        <p>Periodo: <span class="font-semibold text-slate-700">{{ $period }}</span></p>
                        <p>Mostrando {{ $serviceRows->count() }} servicios para analisis detallado.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('finanzas.snapshots.generate') }}">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">
                    <button type="submit" class="ui-btn rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">Generar snapshot</button>
                </form>
            </div>
        </div>

        <template x-teleport="body">
            <div
                x-cloak
                x-show="filtersOpen"
                x-transition.opacity
                class="fixed left-0 top-0 z-[70] h-[100dvh] w-screen overflow-hidden"
                aria-modal="true"
                role="dialog"
            >
                <div class="absolute inset-0 bg-slate-900/40" @click="closeFilters()"></div>

                <aside
                    x-show="filtersOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="-translate-x-full"
                    x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0"
                    x-transition:leave-end="-translate-x-full"
                    class="absolute left-0 top-0 z-[71] flex h-[100dvh] w-full max-w-md flex-col overflow-hidden border-r border-slate-200 bg-white shadow-2xl"
                    @click.stop
                >
                    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Filtros de finanzas</h3>
                            <p class="text-xs text-slate-500">Ajusta periodos y criterios de analisis</p>
                        </div>
                        <button type="button" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50" @click="closeFilters()" aria-label="Cerrar filtros">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    <form method="GET" action="{{ route('finanzas.index') }}" class="flex-1 space-y-4 overflow-y-auto p-4">
                        <div>
                            <label for="drawer-period" class="mb-1 block text-sm font-medium text-slate-700">Periodo</label>
                            <input type="month" id="drawer-period" name="period" value="{{ $period }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        </div>

                        <div>
                            <label for="drawer-q" class="mb-1 block text-sm font-medium text-slate-700">Buscar servicio</label>
                            <input type="text" id="drawer-q" name="q" value="{{ $filters['q'] }}" placeholder="Nombre, proveedor, tipo o responsable" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        </div>

                        <div>
                            <label for="drawer-service-status" class="mb-1 block text-sm font-medium text-slate-700">Estado servicio</label>
                            <select id="drawer-service-status" name="service_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                <option value="all" @selected($filters['service_status'] === 'all')>Todos</option>
                                <option value="active" @selected($filters['service_status'] === 'active')>Activos</option>
                                <option value="paused" @selected($filters['service_status'] === 'paused')>Pausados</option>
                                <option value="archived" @selected($filters['service_status'] === 'archived')>Archivados</option>
                            </select>
                        </div>

                        <div>
                            <label for="drawer-profitability" class="mb-1 block text-sm font-medium text-slate-700">Rentabilidad</label>
                            <select id="drawer-profitability" name="profitability" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                <option value="all" @selected($filters['profitability'] === 'all')>Todos</option>
                                <option value="positive" @selected($filters['profitability'] === 'positive')>Margen positivo</option>
                                <option value="negative" @selected($filters['profitability'] === 'negative')>Margen negativo</option>
                                <option value="breakeven" @selected($filters['profitability'] === 'breakeven')>Equilibrado</option>
                                <option value="with_income" @selected($filters['profitability'] === 'with_income')>Con ingresos</option>
                                <option value="without_income" @selected($filters['profitability'] === 'without_income')>Sin ingresos</option>
                            </select>
                        </div>

                        <div>
                            <label for="drawer-sort" class="mb-1 block text-sm font-medium text-slate-700">Orden</label>
                            <select id="drawer-sort" name="sort" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                <option value="margin_desc" @selected($filters['sort'] === 'margin_desc')>Margen real (mayor a menor)</option>
                                <option value="income_desc" @selected($filters['sort'] === 'income_desc')>Ingreso real (mayor a menor)</option>
                                <option value="name_asc" @selected($filters['sort'] === 'name_asc')>Nombre (A-Z)</option>
                            </select>
                        </div>

                        <div>
                            <label for="drawer-limit" class="mb-1 block text-sm font-medium text-slate-700">Cantidad</label>
                            <select id="drawer-limit" name="limit" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                <option value="10" @selected((int) $filters['limit'] === 10)>10</option>
                                <option value="20" @selected((int) $filters['limit'] === 20)>20</option>
                                <option value="50" @selected((int) $filters['limit'] === 50)>50</option>
                                <option value="100" @selected((int) $filters['limit'] === 100)>100</option>
                            </select>
                        </div>

                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="snapshot_only" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-300" @checked($filters['snapshot_only'])>
                            Solo servicios con snapshot del periodo
                        </label>

                        <div class="flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                            <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Aplicar filtros</button>
                            <a href="{{ route('finanzas.index', ['period' => $period]) }}" class="ui-btn rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Limpiar</a>
                        </div>
                    </form>
                </aside>
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Ingreso real</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($incomeReal, 2) }} USD</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Costo real</p>
                <p class="mt-2 text-2xl font-semibold {{ $realVsCost >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($realVsCost, 2) }} USD</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Ingresos proyectados</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($projectedRecurringIncome, 2) }} USD</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Costos proyectados</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($projectedCosts, 2) }} USD</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Margen proyectado</p>
                <p class="mt-2 text-2xl font-semibold {{ $netProjected >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($netProjected, 2) }} USD</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white xl:col-span-2">
                <div class="border-b border-slate-200 px-4 py-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">Rendimiento por servicio (clic para ver detalle)</h3>
                        <div class="flex items-center gap-3 text-[11px] text-slate-500">
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-emerald-400/70"></span>Activo</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-amber-400/70"></span>Pausado</span>
                            <span class="inline-flex items-center gap-1"><span class="h-2 w-2 rounded-full bg-slate-400/70"></span>Archivado</span>
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="w-8 px-2 py-3"></th>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ingreso</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Costo</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Margen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($serviceRows as $row)
                                @php
                                    $statusBarClass = match ($row['status']) {
                                        'active' => 'bg-emerald-400/70',
                                        'paused' => 'bg-amber-400/70',
                                        default => 'bg-slate-400/70',
                                    };
                                @endphp
                                <tr class="cursor-pointer transition hover:bg-slate-50" @click="openDetail({{ $row['service_id'] }})">
                                    <td class="px-2 py-3 align-top">
                                        <span class="mx-auto block h-12 w-1.5 rounded-full {{ $statusBarClass }}"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-slate-900">{{ $row['name'] }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $row['provider'] !== '-' ? $row['provider'] : 'Sin proveedor' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row['income_real'], 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row['total_cost'], 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ (float) $row['net_real_vs_cost'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ number_format((float) $row['net_real_vs_cost'], 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No hay servicios que cumplan los filtros seleccionados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Costos proyectados por categoria</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Categoria</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($costByCategory as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ \App\Models\CostItem::categoryLabelFromValue($row->category) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row->amount_total, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-slate-500">Sin costos activos.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Snapshot por servicio ({{ $period }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3 text-right">Ingreso</th>
                                <th class="px-4 py-3 text-right">Costo compartido</th>
                                <th class="px-4 py-3 text-right">Margen neto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($currentSnapshots as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->service?->name ?? 'Servicio #'.$row->service_id }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row->income_total, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row->shared_cost_total, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ (float) $row->net_margin >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ number_format((float) $row->net_margin, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No hay snapshot generado para este periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Historico de margen neto</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Periodo</th>
                                <th class="px-4 py-3 text-right">Ingreso total</th>
                                <th class="px-4 py-3 text-right">Costo compartido</th>
                                <th class="px-4 py-3 text-right">Margen neto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($snapshotHistory as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->period }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row->income_total, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700 whitespace-nowrap">
                                        {{ number_format((float) $row->shared_cost_total, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold whitespace-nowrap {{ (float) $row->net_margin >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                                        {{ number_format((float) $row->net_margin, 2) }}
                                        <span class="hidden sm:inline">USD</span>
                                        <span class="sm:hidden" title="USD">$</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Aun no hay historico de snapshots.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            x-cloak
            x-show="detailOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4"
            @click.self="closeDetail()"
        >
            <div class="w-full max-w-4xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900" x-text="detail?.name ?? 'Detalle de servicio'"></h3>
                        <p class="mt-1 text-sm text-slate-600" x-text="detailSummary()"></p>
                    </div>
                    <button type="button" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50" @click="closeDetail()" aria-label="Cerrar">
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>

                <div class="max-h-[80vh] overflow-y-auto p-5 space-y-5" x-show="detail">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Ingreso real</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900" x-text="formatMoney(detail?.income_real)"></p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Ingresos proyectados</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900" x-text="formatMoney(detail?.income_recurring_projected)"></p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Costo total</p>
                            <p class="mt-1 text-lg font-semibold text-slate-900" x-text="formatMoney(detail?.total_cost)"></p>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Margen real</p>
                            <p class="mt-1 text-lg font-semibold" :class="Number(detail?.net_real_vs_cost || 0) >= 0 ? 'text-emerald-700' : 'text-red-700'" x-text="formatMoney(detail?.net_real_vs_cost)"></p>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <h4 class="text-sm font-semibold text-slate-900">Comparativo rapido</h4>
                        <p class="mt-1 text-xs text-slate-500">Grafico simple para visualizar relacion entre ingresos, costos y margen.</p>

                        <div class="mt-4 space-y-3">
                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                    <span>Ingreso real</span>
                                    <span x-text="formatMoney(detail?.income_real)"></span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-emerald-500" :style="`width: ${barWidth(detail?.income_real)}%`"></div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                    <span>Costo total</span>
                                    <span x-text="formatMoney(detail?.total_cost)"></span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-amber-500" :style="`width: ${barWidth(detail?.total_cost)}%`"></div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1 flex items-center justify-between text-xs text-slate-600">
                                    <span>Margen real (abs)</span>
                                    <span x-text="formatMoney(Math.abs(Number(detail?.net_real_vs_cost || 0)))"></span>
                                </div>
                                <div class="h-2 rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full" :class="Number(detail?.net_real_vs_cost || 0) >= 0 ? 'bg-indigo-500' : 'bg-red-500'" :style="`width: ${barWidth(Math.abs(Number(detail?.net_real_vs_cost || 0)))}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-2">
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 px-4 py-3">
                                <h4 class="text-sm font-semibold text-slate-900">Desglose de costos asignados</h4>
                            </div>
                            <div class="max-h-72 overflow-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3">Costo</th>
                                            <th class="px-4 py-3 text-right">Asignado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-if="!detail?.cost_items || detail.cost_items.length === 0">
                                            <tr>
                                                <td colspan="2" class="px-4 py-6 text-center text-sm text-slate-500">Sin costos asignados para este servicio.</td>
                                            </tr>
                                        </template>
                                        <template x-for="item in (detail?.cost_items || [])" :key="item.id">
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <p class="font-medium text-slate-900" x-text="item.name"></p>
                                                    <p class="text-xs text-slate-500"><span x-text="item.category"></span> · <span x-text="item.cost_type === 'shared' ? 'Compartido' : 'Directo'"></span></p>
                                                </td>
                                                <td class="px-4 py-3 text-right text-slate-700" x-text="formatMoney(item.allocated_share)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="border-b border-slate-200 px-4 py-3">
                                <h4 class="text-sm font-semibold text-slate-900">Pagos recientes del periodo</h4>
                            </div>
                            <div class="max-h-72 overflow-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-4 py-3">Fecha</th>
                                            <th class="px-4 py-3">Metodo</th>
                                            <th class="px-4 py-3 text-right">Monto</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <template x-if="!detail?.recent_payments || detail.recent_payments.length === 0">
                                            <tr>
                                                <td colspan="3" class="px-4 py-6 text-center text-sm text-slate-500">Sin pagos en este periodo.</td>
                                            </tr>
                                        </template>
                                        <template x-for="(payment, idx) in (detail?.recent_payments || [])" :key="idx">
                                            <tr>
                                                <td class="px-4 py-3 text-slate-700" x-text="payment.date"></td>
                                                <td class="px-4 py-3 text-slate-700" x-text="payment.method"></td>
                                                <td class="px-4 py-3 text-right text-slate-700" x-text="formatMoney(payment.amount)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4" x-show="detail?.snapshot">
                        <h4 class="text-sm font-semibold text-slate-900">Snapshot del periodo</h4>
                        <div class="mt-3 grid gap-3 sm:grid-cols-4">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Ingreso</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatMoney(detail?.snapshot?.income_total)"></p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Costo directo</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatMoney(detail?.snapshot?.direct_cost_total)"></p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Costo compartido</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="formatMoney(detail?.snapshot?.shared_cost_total)"></p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Margen neto</p>
                                <p class="mt-1 text-sm font-semibold" :class="Number(detail?.snapshot?.net_margin || 0) >= 0 ? 'text-emerald-700' : 'text-red-700'" x-text="formatMoney(detail?.snapshot?.net_margin)"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function financeSection(serviceRows) {
            return {
                services: Array.isArray(serviceRows) ? serviceRows : [],
                filtersOpen: false,
                detailOpen: false,
                detail: null,
                openFilters() {
                    this.filtersOpen = true;

                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },
                closeFilters() {
                    this.filtersOpen = false;

                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                },
                openDetail(serviceId) {
                    const selected = this.services.find((row) => Number(row.service_id) === Number(serviceId));
                    if (!selected) {
                        return;
                    }

                    this.detail = selected;
                    this.detailOpen = true;
                },
                closeDetail() {
                    this.detailOpen = false;
                },
                formatMoney(value) {
                    const amount = Number(value || 0);
                    return amount.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    }) + ' USD';
                },
                maxChartValue() {
                    if (!this.detail) {
                        return 1;
                    }

                    const values = [
                        Math.abs(Number(this.detail.income_real || 0)),
                        Math.abs(Number(this.detail.total_cost || 0)),
                        Math.abs(Number(this.detail.net_real_vs_cost || 0)),
                    ];

                    return Math.max(...values, 1);
                },
                barWidth(value) {
                    const max = this.maxChartValue();
                    const current = Math.abs(Number(value || 0));

                    return Math.max(Math.min((current / max) * 100, 100), 0);
                },
                detailSummary() {
                    if (!this.detail) {
                        return '';
                    }

                    return [this.detail.type, this.detail.provider, this.detail.owner_name]
                        .filter((part) => part && part !== '-')
                        .join(' · ');
                },
            };
        }
    </script>
</x-app-layout>
