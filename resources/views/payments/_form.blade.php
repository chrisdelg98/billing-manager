@csrf

@php
    $subscriptionMap = $subscriptions
        ->mapWithKeys(fn ($subscriptionOption) => [
            (string) $subscriptionOption->id => [
                'service_id' => (string) $subscriptionOption->service_id,
                'amount' => (float) $subscriptionOption->amount,
                'currency' => (string) $subscriptionOption->currency,
            ],
        ])
        ->toArray();

    $seedServiceId = (string) old('service_id', $payment->service_id ?? ($defaultServiceId ?? ''));
    $seedSubscriptionId = (string) old('subscription_id', $payment->subscription_id ?? ($defaultSubscriptionId ?? ''));
    $seedCurrency = old('currency', $payment->currency ?? ($defaultCurrency ?? 'USD'));
    $seedPaidAt = old('paid_at', isset($payment) && $payment->paid_at ? $payment->paid_at->toDateString() : now()->toDateString());
    $seedBaseAmount = old('base_amount', $defaultBaseAmount ?? null);

    if ($seedBaseAmount === null && $seedSubscriptionId !== '' && isset($subscriptionMap[$seedSubscriptionId])) {
        $seedBaseAmount = $subscriptionMap[$seedSubscriptionId]['amount'];
    }

    if ($seedBaseAmount === null) {
        $seedBaseAmount = old('amount', $payment->amount ?? ($defaultAmount ?? ''));
    }

    $seedAmount = old('amount', $payment->amount ?? ($defaultAmount ?? $seedBaseAmount ?? ''));
    $seedDiscountPercent = old('discount_percent', '');
    $seedDiscountAmount = old('discount_amount', '');
    $currencyOptions = collect($currencyOptions ?? ['USD'])
        ->map(fn ($code) => strtoupper((string) $code))
        ->filter(fn ($code) => $code !== '')
        ->values();

    if (! $currencyOptions->contains(strtoupper((string) $seedCurrency))) {
        $currencyOptions->push(strtoupper((string) $seedCurrency));
    }

    $currencyOptions = $currencyOptions->unique()->values();
@endphp

<div
    class="grid gap-5 sm:grid-cols-2"
    x-data="{
        serviceId: @js((string) $seedServiceId),
        subscriptionId: @js((string) $seedSubscriptionId),
        paidAt: @js((string) $seedPaidAt),
        baseAmount: Number(@js((float) $seedBaseAmount)),
        amount: Number(@js((float) $seedAmount)),
        discountPercent: Number(@js((float) $seedDiscountPercent)),
        discountAmount: Number(@js((float) $seedDiscountAmount)),
        currency: @js((string) $seedCurrency),
        subscriptions: @js($subscriptionMap),
        round(value) {
            return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
        },
        clamp(value, min, max) {
            return Math.min(Math.max(Number(value), min), max);
        },
        syncSubscription(resetAmounts = true) {
            const meta = this.subscriptions[this.subscriptionId];

            if (!meta) {
                return;
            }

            this.serviceId = String(meta.service_id);
            this.currency = String(meta.currency || this.currency);
            this.baseAmount = this.round(meta.amount || 0);

            if (resetAmounts) {
                this.discountPercent = 0;
                this.discountAmount = 0;
                this.amount = this.baseAmount;
                return;
            }

            if (!this.amount || this.amount <= 0) {
                this.amount = this.baseAmount;
            }
        },
        applyDiscountPercent() {
            const base = Number(this.baseAmount);

            if (base <= 0) {
                this.discountAmount = 0;
                return;
            }

            this.discountPercent = this.clamp(this.discountPercent, 0, 100);
            this.discountAmount = this.round(base * (this.discountPercent / 100));
            this.amount = this.round(Math.max(base - this.discountAmount, 0));
        },
        applyDiscountAmount() {
            const base = Number(this.baseAmount);

            if (base <= 0) {
                this.discountPercent = 0;
                return;
            }

            this.discountAmount = this.clamp(this.discountAmount, 0, base);
            this.discountPercent = this.round((this.discountAmount / base) * 100);
            this.amount = this.round(Math.max(base - this.discountAmount, 0));
        },
        onAmountInput() {
            const base = Number(this.baseAmount);

            if (base <= 0) {
                return;
            }

            this.amount = this.clamp(this.amount, 0, base);
            this.discountAmount = this.round(Math.max(base - this.amount, 0));
            this.discountPercent = this.round((this.discountAmount / base) * 100);
        },
        onBaseAmountInput() {
            this.baseAmount = Math.max(Number(this.baseAmount), 0);

            if (this.amount > this.baseAmount) {
                this.amount = this.baseAmount;
            }

            this.onAmountInput();
        },
        onServiceChange() {
            const meta = this.subscriptions[this.subscriptionId];

            if (meta && String(meta.service_id) !== String(this.serviceId)) {
                this.subscriptionId = '';
            }
        },
        init() {
            if (this.subscriptionId) {
                this.syncSubscription(false);
            }
        }
    }"
>
    <div>
        <label for="service_id" class="mb-1 block text-sm font-medium text-slate-700">Servicio</label>
        <select id="service_id" name="service_id" required x-model="serviceId" x-on:change="onServiceChange" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="">Selecciona un servicio</option>
            @foreach($services as $serviceOption)
                <option value="{{ $serviceOption->id }}" @selected((int) old('service_id', $payment->service_id ?? 0) === $serviceOption->id)>{{ $serviceOption->name }}</option>
            @endforeach
        </select>
        @error('service_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="subscription_id" class="mb-1 block text-sm font-medium text-slate-700">Suscripcion (opcional)</label>
        <select id="subscription_id" name="subscription_id" x-model="subscriptionId" x-on:change="syncSubscription(true)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
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
        <input id="paid_at" name="paid_at" type="date" x-model="paidAt" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('paid_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="base_amount" class="mb-1 block text-sm font-medium text-slate-700">Monto base (antes de descuento)</label>
        <input id="base_amount" name="base_amount" type="number" step="0.01" min="0" x-model.number="baseAmount" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        <p class="mt-1 text-xs text-slate-500">Se autocompleta segun la suscripcion.</p>
        @error('base_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="discount_percent" class="mb-1 block text-sm font-medium text-slate-700">Descuento (%)</label>
        <input id="discount_percent" name="discount_percent" type="number" step="0.01" min="0" max="100" x-model.number="discountPercent" x-on:input="applyDiscountPercent" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('discount_percent')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="discount_amount" class="mb-1 block text-sm font-medium text-slate-700">Descuento (monto)</label>
        <input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" x-model.number="discountAmount" x-on:input="applyDiscountAmount" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('discount_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Monto final a cobrar</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0" x-model.number="amount" readonly required class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        <p class="mt-1 text-xs text-slate-500">Se calcula automaticamente con base y descuento.</p>
        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="currency" class="mb-1 block text-sm font-medium text-slate-700">Moneda</label>
        <select id="currency" name="currency" x-model="currency" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            @foreach($currencyOptions as $currencyOption)
                <option value="{{ $currencyOption }}">{{ $currencyOption }}</option>
            @endforeach
        </select>
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

    <div class="">
        <label for="reference" class="mb-1 block text-sm font-medium text-slate-700">Referencia</label>
        <input id="reference" name="reference" type="text" value="{{ old('reference', $payment->reference ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        @error('reference')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
        <textarea id="notes" name="notes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">{{ old('notes', $payment->notes ?? ($defaultNotes ?? '')) }}</textarea>
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
