<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Costos</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="mobile-filters-shell rounded-xl border border-slate-200 bg-white p-4" x-data="{ mobileFiltersOpen: false }">
            <button type="button" class="mobile-filters-toggle ui-btn mb-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="mobileFiltersOpen = !mobileFiltersOpen" :aria-expanded="mobileFiltersOpen.toString()">
                <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
                <span x-text="mobileFiltersOpen ? 'Ocultar filtros' : 'Mostrar filtros'"></span>
            </button>

            <form method="GET" action="{{ route('costos.index') }}" class="space-y-3" x-show="mobileFiltersOpen" x-transition.opacity.duration.150ms x-cloak>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="w-full lg:max-w-xl">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por nombre, categoria o tipo"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filtrar</button>
                        <a href="{{ route('costos.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Limpiar</a>
                        <a href="{{ route('costos.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Nuevo costo
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
                    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los estados</option>
                        <option value="active" @selected(request('status') === 'active')>Activos</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
                    </select>

                    <select name="category" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todas las categorias</option>
                        @foreach(($categoryOptions ?? collect()) as $categoryOption)
                            <option value="{{ $categoryOption }}" @selected(request('category') === $categoryOption)>{{ $categoryOption }}</option>
                        @endforeach
                    </select>

                    <select name="cost_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los tipos</option>
                        <option value="direct" @selected(request('cost_type') === 'direct')>Directo</option>
                        <option value="shared" @selected(request('cost_type') === 'shared')>Compartido</option>
                    </select>

                    <select name="billing_cycle" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los ciclos</option>
                        <option value="monthly" @selected(request('billing_cycle') === 'monthly')>Mensual</option>
                        <option value="yearly" @selected(request('billing_cycle') === 'yearly')>Anual</option>
                        <option value="custom" @selected(request('billing_cycle') === 'custom')>Personalizado</option>
                    </select>

                    <select name="renewal_window" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todas las renovaciones</option>
                        <option value="overdue" @selected(request('renewal_window') === 'overdue')>Vencidas</option>
                        <option value="next_7" @selected(request('renewal_window') === 'next_7')>Proximos 7 dias</option>
                        <option value="next_30" @selected(request('renewal_window') === 'next_30')>Proximos 30 dias</option>
                        <option value="no_date" @selected(request('renewal_window') === 'no_date')>Sin fecha</option>
                    </select>

                    <input
                        type="date"
                        name="next_from"
                        value="{{ request('next_from') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >

                    <input
                        type="date"
                        name="next_to"
                        value="{{ request('next_to') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600">
            <span class="font-medium text-slate-700">Estado de costo</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-400/70"></span>Activo</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-400/70"></span>Inactivo</span>
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
                            <th class="mobile-col-main px-4 py-3">Categoria</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="mobile-col-100 px-4 py-3">Monto</th>
                            <th class="mobile-col-100 px-4 py-3">Ciclo</th>
                            <th class="mobile-col-120 px-4 py-3">Renovacion</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($costItems as $costItem)
                            @php
                                $statusBarClass = $costItem->is_active ? 'bg-emerald-400/70' : 'bg-slate-400/70';
                            @endphp
                            <tr>
                                <td class="px-2 py-3 align-top">
                                    <span class="mx-auto block h-12 w-1.5 rounded-full {{ $statusBarClass }}"></span>
                                </td>
                                <td class="mobile-col-main px-4 py-3 font-medium text-slate-900"><span class="mobile-clamp-2">{{ $costItem->name }}</span></td>
                                <td class="mobile-col-main px-4 py-3 text-slate-700"><span class="mobile-clamp-2">{{ $costItem->categoryLabel() }}</span></td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($costItem->cost_type) }}</td>
                                <td class="mobile-col-100 mobile-nowrap px-4 py-3 text-slate-700">{{ number_format((float) $costItem->amount, 2) }} {{ $costItem->currency }}</td>
                                <td class="mobile-col-100 mobile-nowrap px-4 py-3 text-slate-700">{{ $costItem->billingFrequencyLabel() }}</td>
                                <td class="mobile-col-120 mobile-nowrap px-4 py-3 text-slate-700">{{ $costItem->next_renewal_at?->format('Y-m-d') ?: '-' }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $costItem->is_active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        @if ($costItem->cost_type === 'shared')
                                            <a href="{{ route('costos.asignaciones.edit', $costItem) }}" class="ui-btn rounded-lg border border-indigo-300 px-3 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-50">Asignar</a>
                                        @endif
                                        <a href="{{ route('costos.edit', $costItem) }}" class="ui-btn rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50">Editar</a>
                                        <form method="POST" action="{{ route('costos.destroy', $costItem) }}" onsubmit="return confirm('Se eliminara el costo. Continuar?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ui-btn rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 transition hover:bg-red-50">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">No hay costos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $costItems->links() }}
    </div>
</x-app-layout>
