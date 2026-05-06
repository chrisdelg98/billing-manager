@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nombre del costo</label>
        <input id="name" name="name" type="text" value="{{ old('name', $costItem->name ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Categoria</label>
        @php($category = old('category', $costItem->category ?? 'other'))
        <select id="category" name="category" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="hosting" @selected($category === 'hosting')>Hosting</option>
            <option value="license" @selected($category === 'license')>Licencia</option>
            <option value="infra" @selected($category === 'infra')>Infraestructura</option>
            <option value="other" @selected($category === 'other')>Otro</option>
        </select>
        @error('category')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="cost_type" class="mb-1 block text-sm font-medium text-slate-700">Tipo de costo</label>
        @php($costType = old('cost_type', $costItem->cost_type ?? 'direct'))
        <select id="cost_type" name="cost_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="direct" @selected($costType === 'direct')>Directo</option>
            <option value="shared" @selected($costType === 'shared')>Compartido</option>
        </select>
        @error('cost_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Monto</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $costItem->amount ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda</label>
        <input id="currency" name="currency" type="text" maxlength="3" value="{{ old('currency', $costItem->currency ?? 'USD') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 uppercase text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="billing_cycle" class="mb-1 block text-sm font-medium text-slate-700">Ciclo</label>
        @php($billingCycle = old('billing_cycle', $costItem->billing_cycle ?? 'monthly'))
        <select id="billing_cycle" name="billing_cycle" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="monthly" @selected($billingCycle === 'monthly')>Mensual</option>
            <option value="yearly" @selected($billingCycle === 'yearly')>Anual</option>
            <option value="custom" @selected($billingCycle === 'custom')>Personalizado</option>
        </select>
        @error('billing_cycle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="next_renewal_at" class="mb-1 block text-sm font-medium text-slate-700">Proxima renovacion</label>
        <input id="next_renewal_at" name="next_renewal_at" type="date" value="{{ old('next_renewal_at', isset($costItem) && $costItem->next_renewal_at ? $costItem->next_renewal_at->toDateString() : '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('next_renewal_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        @php($active = (bool) old('is_active', $costItem->is_active ?? true))
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked($active) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
            Costo activo
        </label>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
        {{ $submitLabel ?? 'Guardar' }}
    </button>

    <a href="{{ route('costos.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
        Cancelar
    </a>
</div>
