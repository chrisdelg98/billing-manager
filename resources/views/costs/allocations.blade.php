<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Asignacion de costo compartido</h2>
    </x-slot>

    <div class="mx-auto w-full max-w-5xl space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="text-sm text-slate-500">Costo compartido</p>
            <h3 class="mt-1 text-lg font-semibold text-slate-900">{{ $costItem->name }}</h3>
            <p class="mt-2 text-sm text-slate-700">Monto mensual estimado: {{ number_format($costItem->billing_cycle === 'yearly' ? ((float) $costItem->amount / 12) : (float) $costItem->amount, 2) }} {{ $costItem->currency }}</p>
        </div>

        <form method="POST" action="{{ route('costos.asignaciones.update', $costItem) }}" x-data="{ allocationMode: '{{ $allocationMode }}' }" class="rounded-xl border border-slate-200 bg-white p-5">
            @csrf
            @method('PUT')

            <div>
                <label for="allocation_mode" class="mb-1 block text-sm font-medium text-slate-700">Modo de distribucion</label>
                <select id="allocation_mode" name="allocation_mode" x-model="allocationMode" class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    <option value="equal">Igualitario</option>
                    <option value="weight">Por peso</option>
                </select>
                @error('allocation_mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Asignar</th>
                                <th class="px-4 py-3">Servicio</th>
                                <th class="px-4 py-3">Peso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($services as $service)
                                @php($isSelected = in_array($service->id, old('service_ids', $selectedServiceIds), true))
                                <tr>
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked($isSelected) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-900">{{ $service->name }}</td>
                                    <td class="px-4 py-3">
                                        @php($weightValue = old("weights.{$service->id}", $allocations[$service->id]->weight ?? 1))
                                        <input type="number" name="weights[{{ $service->id }}]" step="0.0001" min="0" value="{{ $weightValue }}" x-bind:disabled="allocationMode !== 'weight'" class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 disabled:cursor-not-allowed disabled:bg-slate-100 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                        @error("weights.{$service->id}")<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-500">No hay servicios disponibles para asignar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @error('service_ids')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

            <div class="mt-6 flex items-center gap-3">
                <button type="submit" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Guardar asignaciones
                </button>

                <a href="{{ route('costos.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Volver
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
