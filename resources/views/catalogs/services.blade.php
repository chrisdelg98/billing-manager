<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Catalogos de servicios</h2>
    </x-slot>

    @php
        $sections = [
            [
                'panel_key' => 'types',
                'title' => 'Tipos de servicio',
                'subtitle' => 'Lista reutilizable para el campo Tipo en servicios.',
                'catalog_type' => 'service_type',
                'items' => $typeOptions,
                'new_name_id' => 'new_type_name',
                'new_order_id' => 'new_type_order',
                'new_label' => 'Nuevo tipo',
            ],
            [
                'panel_key' => 'providers',
                'title' => 'Proveedores',
                'subtitle' => 'Lista reutilizable para el campo Proveedor en servicios.',
                'catalog_type' => 'provider',
                'items' => $providerOptions,
                'new_name_id' => 'new_provider_name',
                'new_order_id' => 'new_provider_order',
                'new_label' => 'Nuevo proveedor',
            ],
        ];
    @endphp

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div x-data="{ openPanel: @js(old('catalog_type') === 'provider' ? 'providers' : 'types') }" class="space-y-4">
            @foreach ($sections as $section)
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                        @click="openPanel = openPanel === '{{ $section['panel_key'] }}' ? '' : '{{ $section['panel_key'] }}'"
                        :aria-expanded="openPanel === '{{ $section['panel_key'] }}'"
                    >
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">{{ $section['title'] }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $section['subtitle'] }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $section['items']->count() }} items</span>
                            <x-heroicon-o-chevron-down class="h-5 w-5 text-slate-500 transition" x-bind:class="openPanel === '{{ $section['panel_key'] }}' ? 'rotate-180' : ''" />
                        </div>
                    </button>

                    <div x-show="openPanel === '{{ $section['panel_key'] }}'" x-transition.opacity.duration.150ms class="space-y-4 border-t border-slate-200 p-5">
                        <p class="text-xs text-slate-500">Tip: Puedes arrastrar filas desde el icono para reordenar rapidamente.</p>

                        <form method="POST" action="{{ route('catalogos.servicios.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                            @csrf
                            <input type="hidden" name="catalog_type" value="{{ $section['catalog_type'] }}">

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700" for="{{ $section['new_name_id'] }}">{{ $section['new_label'] }}</label>
                                <input id="{{ $section['new_name_id'] }}" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700" for="{{ $section['new_order_id'] }}">Orden</label>
                                <input id="{{ $section['new_order_id'] }}" name="sort_order" type="number" min="0" value="0" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            </div>

                            <label class="inline-flex items-center gap-2 pb-2 text-sm text-slate-700">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                Activo
                            </label>

                            <button type="submit" class="ui-btn rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Agregar</button>
                        </form>

                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="w-12 px-4 py-3 text-center">Mover</th>
                                            <th class="px-4 py-3">Nombre</th>
                                            <th class="w-28 px-4 py-3">Orden</th>
                                            <th class="w-24 px-4 py-3">Activo</th>
                                            <th class="w-44 px-4 py-3 text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100" data-sortable-table data-catalog-type="{{ $section['catalog_type'] }}">
                                        @forelse($section['items'] as $option)
                                            <tr class="bg-white transition hover:bg-slate-50" data-draggable-row data-row-id="{{ $option->id }}">
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" draggable="true" class="drag-handle inline-flex cursor-move items-center rounded-lg border border-slate-300 px-2 py-1 text-slate-600 hover:bg-slate-100" title="Arrastrar para reordenar">
                                                        <x-heroicon-o-bars-3 class="h-4 w-4" />
                                                    </button>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input form="update-option-{{ $option->id }}" name="name" type="text" value="{{ $option->name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input form="update-option-{{ $option->id }}" name="sort_order" type="number" min="0" value="{{ $option->sort_order }}" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                        <input form="update-option-{{ $option->id }}" type="checkbox" name="is_active" value="1" @checked($option->is_active) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                                        <span>Si</span>
                                                    </label>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="flex justify-end gap-2">
                                                        <form id="update-option-{{ $option->id }}" method="POST" action="{{ route('catalogos.servicios.update', $option) }}" class="hidden">
                                                            @csrf
                                                            @method('PUT')
                                                        </form>

                                                        <button type="submit" form="update-option-{{ $option->id }}" class="ui-btn rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Guardar</button>

                                                        <form method="POST" action="{{ route('catalogos.servicios.destroy', $option) }}" onsubmit="return confirm('Se eliminara este valor del catalogo. Continuar?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="ui-btn rounded-lg border border-red-300 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">No hay registros cargados.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const reorderUrl = '{{ route('catalogos.servicios.reorder') }}';
            const csrfToken = '{{ csrf_token() }}';
            let draggingRow = null;

            const saveOrder = async (tableBody) => {
                const catalogType = tableBody.dataset.catalogType;
                const orderedIds = Array.from(tableBody.querySelectorAll('[data-draggable-row]')).map((row) => Number(row.dataset.rowId));

                if (orderedIds.length === 0) {
                    return;
                }

                const response = await fetch(reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        catalog_type: catalogType,
                        ordered_ids: orderedIds,
                    }),
                });

                if (!response.ok) {
                    throw new Error('No se pudo guardar el nuevo orden.');
                }

                tableBody.querySelectorAll('[data-draggable-row]').forEach((row, index) => {
                    const orderInput = row.querySelector('input[name="sort_order"]');

                    if (orderInput) {
                        orderInput.value = (index + 1) * 10;
                    }
                });
            };

            document.querySelectorAll('[data-sortable-table]').forEach((tableBody) => {
                tableBody.addEventListener('dragover', function (event) {
                    event.preventDefault();

                    if (!draggingRow) {
                        return;
                    }

                    const targetRow = event.target.closest('[data-draggable-row]');

                    if (!targetRow || targetRow === draggingRow) {
                        return;
                    }

                    const targetRect = targetRow.getBoundingClientRect();
                    const insertBefore = event.clientY < targetRect.top + targetRect.height / 2;

                    tableBody.insertBefore(draggingRow, insertBefore ? targetRow : targetRow.nextSibling);
                });

                tableBody.addEventListener('drop', function (event) {
                    event.preventDefault();

                    if (!draggingRow) {
                        return;
                    }

                    saveOrder(tableBody).catch(function (error) {
                        console.error(error);
                        alert('No se pudo guardar el orden. Intenta nuevamente.');
                    });
                });

                tableBody.querySelectorAll('.drag-handle').forEach((handle) => {
                    handle.addEventListener('dragstart', function (event) {
                        draggingRow = handle.closest('[data-draggable-row]');

                        if (!draggingRow) {
                            return;
                        }

                        draggingRow.classList.add('opacity-60');
                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', draggingRow.dataset.rowId || '');
                    });

                    handle.addEventListener('dragend', function () {
                        if (draggingRow) {
                            draggingRow.classList.remove('opacity-60');
                        }

                        draggingRow = null;
                    });
                });
            });
        });
    </script>
</x-app-layout>
