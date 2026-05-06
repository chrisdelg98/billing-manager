<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Catalogos de servicios</h2>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <div x-data="{ openPanel: 'types' }" class="space-y-4">
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                    @click="openPanel = openPanel === 'types' ? '' : 'types'"
                    :aria-expanded="openPanel === 'types'"
                >
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Tipos de servicio</h3>
                        <p class="mt-1 text-sm text-slate-600">Lista reutilizable para el campo Tipo en servicios.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $typeOptions->count() }} items</span>
                        <x-heroicon-o-chevron-down class="h-5 w-5 text-slate-500 transition" x-bind:class="openPanel === 'types' ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="openPanel === 'types'" x-transition.opacity.duration.150ms class="space-y-4 border-t border-slate-200 p-5">
                    <form method="POST" action="{{ route('catalogos.servicios.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                        @csrf
                        <input type="hidden" name="catalog_type" value="service_type">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700" for="new_type_name">Nuevo tipo</label>
                            <input id="new_type_name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700" for="new_type_order">Orden</label>
                            <input id="new_type_order" name="sort_order" type="number" min="0" value="0" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
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
                                        <th class="px-4 py-3">Nombre</th>
                                        <th class="px-4 py-3">Orden</th>
                                        <th class="px-4 py-3">Activo</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($typeOptions as $option)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <form method="POST" action="{{ route('catalogos.servicios.update', $option) }}" class="grid gap-2 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="name" type="text" value="{{ old('name', $option->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                    <input name="sort_order" type="number" min="0" value="{{ $option->sort_order }}" class="w-20 rounded-lg border border-slate-300 px-2 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                        <input type="checkbox" name="is_active" value="1" @checked($option->is_active) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                                        <span>Si</span>
                                                    </label>
                                                    <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Guardar</button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ $option->sort_order }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $option->is_active ? 'Si' : 'No' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" action="{{ route('catalogos.servicios.destroy', $option) }}" onsubmit="return confirm('Se eliminara este valor del catalogo. Continuar?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ui-btn rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No hay tipos cargados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                    @click="openPanel = openPanel === 'providers' ? '' : 'providers'"
                    :aria-expanded="openPanel === 'providers'"
                >
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Proveedores</h3>
                        <p class="mt-1 text-sm text-slate-600">Lista reutilizable para el campo Proveedor en servicios.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ $providerOptions->count() }} items</span>
                        <x-heroicon-o-chevron-down class="h-5 w-5 text-slate-500 transition" x-bind:class="openPanel === 'providers' ? 'rotate-180' : ''" />
                    </div>
                </button>

                <div x-show="openPanel === 'providers'" x-transition.opacity.duration.150ms class="space-y-4 border-t border-slate-200 p-5">
                    <form method="POST" action="{{ route('catalogos.servicios.store') }}" class="grid gap-3 sm:grid-cols-[1fr_auto_auto_auto] sm:items-end">
                        @csrf
                        <input type="hidden" name="catalog_type" value="provider">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700" for="new_provider_name">Nuevo proveedor</label>
                            <input id="new_provider_name" name="name" type="text" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700" for="new_provider_order">Orden</label>
                            <input id="new_provider_order" name="sort_order" type="number" min="0" value="0" class="w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
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
                                        <th class="px-4 py-3">Nombre</th>
                                        <th class="px-4 py-3">Orden</th>
                                        <th class="px-4 py-3">Activo</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($providerOptions as $option)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <form method="POST" action="{{ route('catalogos.servicios.update', $option) }}" class="grid gap-2 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center">
                                                    @csrf
                                                    @method('PUT')
                                                    <input name="name" type="text" value="{{ $option->name }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                    <input name="sort_order" type="number" min="0" value="{{ $option->sort_order }}" class="w-20 rounded-lg border border-slate-300 px-2 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                                                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                        <input type="checkbox" name="is_active" value="1" @checked($option->is_active) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
                                                        <span>Si</span>
                                                    </label>
                                                    <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Guardar</button>
                                                </form>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">{{ $option->sort_order }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ $option->is_active ? 'Si' : 'No' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" action="{{ route('catalogos.servicios.destroy', $option) }}" onsubmit="return confirm('Se eliminara este valor del catalogo. Continuar?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ui-btn rounded-lg border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50">Eliminar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No hay proveedores cargados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
