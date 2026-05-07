<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Finanzas</h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
            <form method="GET" action="{{ route('finanzas.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="period" class="mb-1 block text-sm font-medium text-slate-700">Periodo</label>
                    <input type="month" id="period" name="period" value="{{ $period }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                </div>
                <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Aplicar</button>
            </form>

            <form method="POST" action="{{ route('finanzas.snapshots.generate') }}">
                @csrf
                <input type="hidden" name="period" value="{{ $period }}">
                <button type="submit" class="ui-btn rounded-lg border border-indigo-300 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">Generar snapshot</button>
            </form>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Ingreso real</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($incomeReal, 2) }} USD</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Ingreso recurrente</p>
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
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Real - costo</p>
                <p class="mt-2 text-2xl font-semibold {{ $realVsCost >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($realVsCost, 2) }} USD</p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Ingresos por servicio (periodo)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3 text-right">Ingreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($incomeByService as $row)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $row->name }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row->income_total, 2) }} USD</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-4 py-8 text-center text-sm text-slate-500">Sin datos para el periodo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Costos mensuales proyectados por categoria</h3>
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
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ ucfirst($row->category) }}</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row->amount_total, 2) }} USD</td>
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
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row->income_total, 2) }} USD</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row->shared_cost_total, 2) }} USD</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ (float) $row->net_margin >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $row->net_margin, 2) }} USD</td>
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
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row->income_total, 2) }} USD</td>
                                    <td class="px-4 py-3 text-right text-slate-700">{{ number_format((float) $row->shared_cost_total, 2) }} USD</td>
                                    <td class="px-4 py-3 text-right font-semibold {{ (float) $row->net_margin >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $row->net_margin, 2) }} USD</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">Aun no hay historico de snapshots.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
