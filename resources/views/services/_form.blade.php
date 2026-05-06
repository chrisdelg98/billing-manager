@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $service->name ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="type" class="mb-1 block text-sm font-medium text-slate-700">Tipo</label>
        <input id="type" name="type" type="text" list="service_type_options" value="{{ old('type', $service->type ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        <datalist id="service_type_options">
            @foreach(($typeOptions ?? collect()) as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
        @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="provider" class="mb-1 block text-sm font-medium text-slate-700">Proveedor</label>
        <input id="provider" name="provider" type="text" list="provider_options" value="{{ old('provider', $service->provider ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        <datalist id="provider_options">
            @foreach(($providerOptions ?? collect()) as $option)
                <option value="{{ $option }}"></option>
            @endforeach
        </datalist>
        @error('provider')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        <p class="mt-1 text-xs text-slate-500">
            Gestiona listas de tipos y proveedores en
            <a href="{{ route('catalogos.servicios.index') }}" class="font-medium text-slate-700 underline decoration-slate-300 underline-offset-2 hover:text-slate-900">Catalogos de servicios</a>.
        </p>
    </div>

    <div>
        <label for="status" class="mb-1 block text-sm font-medium text-slate-700">Estado</label>
        <select id="status" name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            @php($statusValue = old('status', $service->status ?? 'active'))
            <option value="active" @selected($statusValue === 'active')>Activo</option>
            <option value="paused" @selected($statusValue === 'paused')>Pausado</option>
            <option value="archived" @selected($statusValue === 'archived')>Archivado</option>
        </select>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="owner_name" class="mb-1 block text-sm font-medium text-slate-700">Responsable</label>
        <input id="owner_name" name="owner_name" type="text" value="{{ old('owner_name', $service->owner_name ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('owner_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
        <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">{{ old('notes', $service->notes ?? '') }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
        {{ $submitLabel ?? 'Guardar' }}
    </button>

    <a href="{{ route('servicios.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
        Cancelar
    </a>
</div>
