<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-900">Servicios</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="mobile-filters-shell rounded-xl border border-slate-200 bg-white p-4" x-data="{ mobileFiltersOpen: false }">
            <button type="button" class="mobile-filters-toggle ui-btn mb-3 inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="mobileFiltersOpen = !mobileFiltersOpen" :aria-expanded="mobileFiltersOpen.toString()">
                <x-heroicon-o-adjustments-horizontal class="h-4 w-4" />
                <span x-text="mobileFiltersOpen ? 'Ocultar filtros' : 'Mostrar filtros'"></span>
            </button>

            <form method="GET" action="{{ route('servicios.index') }}" class="space-y-3" x-show="mobileFiltersOpen" x-transition.opacity.duration.150ms x-cloak>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                    <div class="w-full lg:max-w-xl">
                        <input
                            type="text"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Buscar por nombre, tipo, proveedor o responsable"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit" class="ui-btn rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Filtrar</button>
                        <a href="{{ route('servicios.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">Limpiar</a>
                        <a href="{{ route('catalogos.servicios.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                            Gestionar listas
                        </a>
                        <a href="{{ route('servicios.create') }}" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Nuevo servicio
                        </a>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los estados</option>
                        <option value="active" @selected(request('status') === 'active')>Activos</option>
                        <option value="paused" @selected(request('status') === 'paused')>Pausados</option>
                        <option value="archived" @selected(request('status') === 'archived')>Archivados</option>
                    </select>

                    <select name="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los tipos</option>
                        @foreach($typeOptions as $typeOption)
                            <option value="{{ $typeOption }}" @selected(request('type') === $typeOption)>{{ $typeOption }}</option>
                        @endforeach
                    </select>

                    <select name="provider" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                        <option value="">Todos los proveedores</option>
                        @foreach($providerOptions as $providerOption)
                            <option value="{{ $providerOption }}" @selected(request('provider') === $providerOption)>{{ $providerOption }}</option>
                        @endforeach
                    </select>

                    <input
                        type="text"
                        name="owner_name"
                        value="{{ request('owner_name') }}"
                        placeholder="Responsable"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
                    >
                </div>
            </form>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600">
            <span class="font-medium text-slate-700">Estado de servicio</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-400/70"></span>Activo</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400/70"></span>Pausado</span>
            <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-400/70"></span>Archivado</span>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @error('delete')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="overflow-x-auto">
                <table class="mobile-table-compact min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-8 px-2 py-3"></th>
                            <th class="mobile-col-main px-4 py-3">Nombre</th>
                            <th class="mobile-col-main px-4 py-3">Tipo</th>
                            <th class="mobile-col-main px-4 py-3">Proveedor</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="mobile-col-main px-4 py-3">Responsable</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($services as $service)
                            @php
                                [$statusLabel, $statusBarClass] = match ($service->status) {
                                    'active' => ['Activo', 'bg-emerald-400/70'],
                                    'paused' => ['Pausado', 'bg-amber-400/70'],
                                    default => ['Archivado', 'bg-slate-400/70'],
                                };
                            @endphp
                            <tr>
                                <td class="px-2 py-3 align-top">
                                    <span class="mx-auto block h-12 w-1.5 rounded-full {{ $statusBarClass }}"></span>
                                </td>
                                <td class="mobile-col-main px-4 py-3 font-medium text-slate-900"><span class="mobile-clamp-2">{{ $service->name }}</span></td>
                                <td class="mobile-col-main px-4 py-3 text-slate-700"><span class="mobile-clamp-2">{{ $service->type ?: '-' }}</span></td>
                                <td class="mobile-col-main px-4 py-3 text-slate-700"><span class="mobile-clamp-2">{{ $service->provider ?: '-' }}</span></td>
                                <td class="px-4 py-3 text-slate-700">{{ $statusLabel }}</td>
                                <td class="mobile-col-main px-4 py-3 text-slate-700"><span class="mobile-clamp-2">{{ $service->owner_name ?: '-' }}</span></td>
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
                                                const menuHeight = 150;
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
                                                            href="{{ route('servicios.edit', $service) }}"
                                                            class="block rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-50"
                                                            @click="closeMenu()"
                                                        >
                                                            Editar
                                                        </a>

                                                        <form method="POST" action="{{ route('servicios.destroy', $service) }}" onsubmit="return confirm('Se eliminara el servicio. Continuar?')">
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
                                <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No hay servicios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{ $services->links() }}
    </div>
</x-app-layout>
