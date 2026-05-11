@csrf

@php
    $billingCycle = old('billing_cycle', $costItem->billing_cycle ?? 'monthly');
    $intervalMonths = (int) old('billing_interval_months', $costItem->billing_interval_months ?? ($billingCycle === 'yearly' ? 12 : 1));
    $intervalMonths = max($intervalMonths, 1);
    $defaultCustomUnit = $intervalMonths % 12 === 0 ? 'year' : 'month';
    $customEveryBase = $defaultCustomUnit === 'year' ? max((int) ($intervalMonths / 12), 1) : $intervalMonths;
    $customEvery = (int) old('billing_custom_every', $customEveryBase);
    $customUnit = old('billing_custom_unit', $defaultCustomUnit);
    $selectedCurrency = strtoupper((string) old('currency', $costItem->currency ?? 'USD'));
    $selectedCategory = (string) old('category', $costItem->category ?? 'Otro');
    $currencyOptions = collect($currencyOptions ?? ['USD'])
        ->map(fn ($code) => strtoupper((string) $code))
        ->filter(fn ($code) => $code !== '')
        ->values();

    $categoryOptions = collect($categoryOptions ?? ['Hosting', 'Licencia', 'Infraestructura', 'Otro'])
        ->map(fn ($category) => trim((string) $category))
        ->filter(fn ($category) => $category !== '')
        ->values();

    if (! $currencyOptions->contains($selectedCurrency)) {
        $currencyOptions->push($selectedCurrency);
    }

    if (! $categoryOptions->contains($selectedCategory)) {
        $categoryOptions->push($selectedCategory);
    }

    $currencyOptions = $currencyOptions->unique()->values();
    $categoryOptions = $categoryOptions->unique()->values();
@endphp

<div
    x-data="{ billingCycle: @js($billingCycle), customEvery: {{ max($customEvery, 1) }}, customUnit: @js($customUnit) }"
    class="grid gap-5 sm:grid-cols-2"
>
    <div class="sm:col-span-2">
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nombre del costo</label>
        <input id="name" name="name" type="text" value="{{ old('name', $costItem->name ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="category" class="mb-1 block text-sm font-medium text-slate-700">Categoria</label>
        <select id="category" name="category" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            @foreach($categoryOptions as $categoryOption)
                <option value="{{ $categoryOption }}" @selected($selectedCategory === $categoryOption)>{{ $categoryOption }}</option>
            @endforeach
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
        <select id="currency" name="currency" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            @foreach($currencyOptions as $currencyOption)
                <option value="{{ $currencyOption }}" @selected($selectedCurrency === $currencyOption)>{{ $currencyOption }}</option>
            @endforeach
        </select>
        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="billing_cycle" class="mb-1 block text-sm font-medium text-slate-700">Ciclo</label>
        <select id="billing_cycle" name="billing_cycle" x-model="billingCycle" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="monthly" @selected($billingCycle === 'monthly')>Mensual</option>
            <option value="yearly" @selected($billingCycle === 'yearly')>Anual</option>
            <option value="custom" @selected($billingCycle === 'custom')>Personalizado</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Mensual = cada 1 mes, Anual = cada 12 meses.</p>
        @error('billing_cycle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div x-show="billingCycle === 'custom'" x-cloak class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <p class="mb-3 text-sm font-medium text-slate-700">Frecuencia personalizada</p>

        <div class="grid gap-3 sm:grid-cols-[auto_auto] sm:items-end">
            <div>
                <label for="billing_custom_every" class="mb-1 block text-sm font-medium text-slate-700">Se cobra cada</label>
                <input id="billing_custom_every" name="billing_custom_every" type="number" min="1" x-model.number="customEvery" class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            </div>

            <div>
                <label for="billing_custom_unit" class="mb-1 block text-sm font-medium text-slate-700">Unidad</label>
                <select id="billing_custom_unit" name="billing_custom_unit" x-model="customUnit" class="w-36 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    <option value="month">Meses</option>
                    <option value="year">Años</option>
                </select>
            </div>
        </div>

        <p class="mt-2 text-xs text-slate-500">Ejemplo: 4 años = se cobra cada 48 meses.</p>
        @error('billing_interval_months')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('billing_custom_every')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        @error('billing_custom_unit')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <input
        type="hidden"
        name="billing_interval_months"
        x-bind:value="billingCycle === 'monthly' ? 1 : (billingCycle === 'yearly' ? 12 : (customUnit === 'year' ? (customEvery * 12) : customEvery))"
    >

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
