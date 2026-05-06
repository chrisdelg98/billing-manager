@csrf

<div class="grid gap-5 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="service_id" class="mb-1 block text-sm font-medium text-slate-700">Servicio</label>
        <select id="service_id" name="service_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="">Selecciona un servicio</option>
            @foreach($services as $serviceOption)
                <option value="{{ $serviceOption->id }}" @selected((int) old('service_id', $subscription->service_id ?? 0) === $serviceOption->id)>{{ $serviceOption->name }}</option>
            @endforeach
        </select>
        @error('service_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="name" class="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
        <input id="name" name="name" type="text" value="{{ old('name', $subscription->name ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="billing_cycle" class="mb-1 block text-sm font-medium text-slate-700">Ciclo de cobro</label>
        @php($billingCycle = old('billing_cycle', $subscription->billing_cycle ?? 'monthly'))
        <select id="billing_cycle" name="billing_cycle" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="monthly" @selected($billingCycle === 'monthly')>Mensual</option>
            <option value="yearly" @selected($billingCycle === 'yearly')>Anual</option>
            <option value="custom" @selected($billingCycle === 'custom')>Personalizado</option>
        </select>
        @error('billing_cycle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Monto</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $subscription->amount ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda</label>
        <input id="currency" name="currency" type="text" maxlength="3" value="{{ old('currency', $subscription->currency ?? 'USD') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 uppercase text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="next_renewal_at" class="mb-1 block text-sm font-medium text-slate-700">Proxima renovacion</label>
        <input id="next_renewal_at" name="next_renewal_at" type="date" value="{{ old('next_renewal_at', isset($subscription) && $subscription->next_renewal_at ? $subscription->next_renewal_at->toDateString() : '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('next_renewal_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        @php($active = (bool) old('is_active', $subscription->is_active ?? true))
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked($active) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
            Suscripcion activa
        </label>
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
        {{ $submitLabel ?? 'Guardar' }}
    </button>

    <a href="{{ route('suscripciones.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
        Cancelar
    </a>
</div>
