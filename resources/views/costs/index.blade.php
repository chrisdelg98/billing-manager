<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Costos</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('costos.index') }}" class="flex w-full max-w-md items-center gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Buscar</button>
            </form>

            <a href="{{ route('costos.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Nuevo costo
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
                            <th class="px-4 py-3">Categoria</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Monto</th>
                            <th class="px-4 py-3">Ciclo</th>
                            <th class="px-4 py-3">Renovacion</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($costItems as $costItem)
                            <tr>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $costItem->name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($costItem->category) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($costItem->cost_type) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ number_format((float) $costItem->amount, 2) }} {{ $costItem->currency }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ucfirst($costItem->billing_cycle) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $costItem->next_renewal_at?->format('Y-m-d') ?: '-' }}</td>
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
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">No hay costos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $costItems->links() }}
    </div>
</x-app-layout>
