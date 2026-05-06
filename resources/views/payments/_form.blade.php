@csrf

<div class="grid gap-5 sm:grid-cols-2" x-data="{ serviceId: '{{ old('service_id', $payment->service_id ?? '') }}' }">
    <div>
        <label for="service_id" class="mb-1 block text-sm font-medium text-slate-700">Servicio</label>
        <select id="service_id" name="service_id" required x-model="serviceId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="">Selecciona un servicio</option>
            @foreach($services as $serviceOption)
                <option value="{{ $serviceOption->id }}" @selected((int) old('service_id', $payment->service_id ?? 0) === $serviceOption->id)>{{ $serviceOption->name }}</option>
            @endforeach
        </select>
        @error('service_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="subscription_id" class="mb-1 block text-sm font-medium text-slate-700">Suscripcion (opcional)</label>
        <select id="subscription_id" name="subscription_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="">Sin suscripcion</option>
            @foreach($subscriptions as $subscriptionOption)
                <option value="{{ $subscriptionOption->id }}"
                    x-show="!serviceId || serviceId == '{{ $subscriptionOption->service_id }}'"
                    @selected((int) old('subscription_id', $payment->subscription_id ?? 0) === $subscriptionOption->id)>
                    {{ $subscriptionOption->name }}
                </option>
            @endforeach
        </select>
        @error('subscription_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="paid_at" class="mb-1 block text-sm font-medium text-slate-700">Fecha de pago</label>
        <input id="paid_at" name="paid_at" type="date" value="{{ old('paid_at', isset($payment) && $payment->paid_at ? $payment->paid_at->toDateString() : now()->toDateString()) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('paid_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Monto</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $payment->amount ?? '') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda</label>
        <input id="currency" name="currency" type="text" maxlength="3" value="{{ old('currency', $payment->currency ?? 'USD') }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 uppercase text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('currency')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="method" class="mb-1 block text-sm font-medium text-slate-700">Metodo</label>
        @php($method = old('method', $payment->method ?? 'transfer'))
        <select id="method" name="method" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="transfer" @selected($method === 'transfer')>Transferencia</option>
            <option value="cash" @selected($method === 'cash')>Efectivo</option>
            <option value="other" @selected($method === 'other')>Otro</option>
        </select>
        @error('method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="reference" class="mb-1 block text-sm font-medium text-slate-700">Referencia</label>
        <input id="reference" name="reference" type="text" value="{{ old('reference', $payment->reference ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
        <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">{{ old('notes', $payment->notes ?? '') }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="ui-btn inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
        {{ $submitLabel ?? 'Guardar' }}
    </button>

    <a href="{{ route('pagos.index') }}" class="ui-btn inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
        Cancelar
    </a>
</div>
