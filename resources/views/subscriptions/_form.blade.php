@csrf

@php
    $billingCycle = old('billing_cycle', $subscription->billing_cycle ?? 'monthly');
    $seedAmount = (float) old('amount', $subscription->amount ?? 0);
    $seedTrialEndsAt = old('trial_ends_at', isset($subscription) && $subscription->trial_ends_at ? $subscription->trial_ends_at->toDateString() : '');
    $seedHasTrialRaw = old('has_trial', isset($subscription) && $subscription->has_trial ? '1' : '0');
    $seedHasTrial = (string) $seedHasTrialRaw === '1' || (is_string($seedTrialEndsAt) && $seedTrialEndsAt !== '');
    $seedMonthlyReference = match ($billingCycle) {
        'yearly' => $seedAmount / 12,
        'monthly' => $seedAmount,
        default => 0,
    };
@endphp

<div
    class="grid gap-5 sm:grid-cols-2"
    x-data="{
        billingCycle: @js((string) $billingCycle),
        amount: Number(@js($seedAmount)),
        hasTrial: @js((bool) $seedHasTrial),
        trialEndsAt: @js((string) $seedTrialEndsAt),
        calcOpen: false,
        monthlyReference: Number(@js(round($seedMonthlyReference, 2))),
        discountPercent: Number(0),
        discountAmount: Number(0),
        round(value) {
            return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
        },
        clamp(value, min, max) {
            return Math.min(Math.max(Number(value), min), max);
        },
        annualBase() {
            return this.round(Math.max(Number(this.monthlyReference), 0) * 12);
        },
        annualFinal() {
            return this.round(Math.max(this.annualBase() - Number(this.discountAmount), 0));
        },
        equivalentMonthly() {
            return this.round(this.annualFinal() / 12);
        },
        syncDiscountFromPercent() {
            const base = this.annualBase();

            if (base <= 0) {
                this.discountPercent = 0;
                this.discountAmount = 0;
                return;
            }

            this.monthlyReference = Math.max(Number(this.monthlyReference), 0);
            this.discountPercent = this.clamp(this.discountPercent, 0, 100);
            this.discountAmount = this.round(base * (this.discountPercent / 100));
        },
        syncDiscountFromAmount() {
            const base = this.annualBase();

            if (base <= 0) {
                this.discountPercent = 0;
                this.discountAmount = 0;
                return;
            }

            this.monthlyReference = Math.max(Number(this.monthlyReference), 0);
            this.discountAmount = this.clamp(this.discountAmount, 0, base);
            this.discountPercent = this.round((this.discountAmount / base) * 100);
        },
        useCurrentAmountAsMonthly() {
            const currentAmount = Number(this.amount || 0);

            if (currentAmount <= 0) {
                return;
            }

            this.monthlyReference = this.round(this.billingCycle === 'yearly' ? currentAmount / 12 : currentAmount);
            this.syncDiscountFromPercent();
        },
        openAnnualAssistant() {
            this.useCurrentAmountAsMonthly();
            this.calcOpen = true;
        },
        applyAnnualSuggestion() {
            this.billingCycle = 'yearly';
            this.amount = this.annualFinal();
            this.calcOpen = false;
        }
    }"
>
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
        <select id="billing_cycle" name="billing_cycle" required x-model="billingCycle" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
            <option value="monthly" @selected($billingCycle === 'monthly')>Mensual</option>
            <option value="yearly" @selected($billingCycle === 'yearly')>Anual</option>
        </select>
        @error('billing_cycle')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <div class="mb-1 flex items-center justify-between gap-2">
            <label for="amount" class="block text-sm font-medium text-slate-700">Monto</label>
            <button
                type="button"
                class="inline-flex items-center rounded-md border border-slate-300 px-2.5 py-1 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
                @click="openAnnualAssistant()"
            >
                Asistente anual
            </button>
        </div>
        <input id="amount" name="amount" type="number" step="0.01" min="0" value="{{ old('amount', $subscription->amount ?? '') }}" x-model.number="amount" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
        <p class="mt-1 text-xs text-slate-500">Usa el asistente para convertir mensual a anual y aplicar descuento sugerido.</p>
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

    <div class="sm:col-span-2 rounded-lg border border-slate-200 bg-slate-50/60 p-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input
                type="checkbox"
                name="has_trial"
                value="1"
                x-model="hasTrial"
                x-on:change="if (!hasTrial) { trialEndsAt = '' }"
                class="rounded border-slate-300 text-slate-900 focus:ring-slate-300"
            >
            Tiene periodo de prueba (opcional)
        </label>
        <p class="mt-1 text-xs text-slate-500">Si esta activo, la suscripcion no cuenta como ingreso recurrente hasta finalizar la prueba.</p>

        <div class="mt-3" x-show="hasTrial" x-cloak>
            <label for="trial_ends_at" class="mb-1 block text-sm font-medium text-slate-700">Periodo de prueba hasta</label>
            <input
                id="trial_ends_at"
                name="trial_ends_at"
                type="date"
                x-model="trialEndsAt"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200"
            >
            <p class="mt-1 text-xs text-slate-500">Al vencer esta fecha, la suscripcion pasa automaticamente a estado normal y se conserva el historico de prueba.</p>
        </div>
        @error('trial_ends_at')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="mb-1 block text-sm font-medium text-slate-700">Notas</label>
        <textarea id="notes" name="notes" rows="3" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200" placeholder="Ejemplo: Mes de prueba / Descuento 20% por onboarding">{{ old('notes', $subscription->notes ?? '') }}</textarea>
        <p class="mt-1 text-xs text-slate-500">Guarda aqui acuerdos comerciales para reutilizarlos al momento de cobrar.</p>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="sm:col-span-2">
        @php($active = (bool) old('is_active', $subscription->is_active ?? true))
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="is_active" value="1" @checked($active) class="rounded border-slate-300 text-slate-900 focus:ring-slate-300">
            Suscripcion activa
        </label>
    </div>

    <div
        x-cloak
        x-show="calcOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 px-4"
        @click.self="calcOpen = false"
        @keydown.escape.window="calcOpen = false"
    >
        <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white shadow-xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Asistente de precio anual</h3>
                    <p class="mt-1 text-sm text-slate-600">Simula el cobro anual segun precio mensual y descuento.</p>
                </div>
                <button type="button" class="rounded-lg border border-slate-300 p-2 text-slate-600 hover:bg-slate-50" @click="calcOpen = false" aria-label="Cerrar">
                    <x-heroicon-o-x-mark class="h-4 w-4" />
                </button>
            </div>

            <div class="grid gap-4 px-5 py-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Precio mensual de referencia</label>
                    <input type="number" step="0.01" min="0" x-model.number="monthlyReference" x-on:input="syncDiscountFromPercent" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Total anual sin descuento</label>
                    <input type="number" step="0.01" min="0" :value="annualBase()" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Descuento (%)</label>
                    <input type="number" step="0.01" min="0" max="100" x-model.number="discountPercent" x-on:input="syncDiscountFromPercent" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Descuento (monto)</label>
                    <input type="number" step="0.01" min="0" x-model.number="discountAmount" x-on:input="syncDiscountFromAmount" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-4 focus:ring-slate-200">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Total anual sugerido</label>
                    <input type="number" step="0.01" min="0" :value="annualFinal()" readonly class="w-full rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Equivalente mensual final</label>
                    <input type="number" step="0.01" min="0" :value="equivalentMonthly()" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900">
                </div>
            </div>

            <div class="border-t border-slate-200 px-5 py-4 text-xs text-slate-500">
                Tip: aplica el sugerido y luego ajusta manualmente si necesitas redondear una oferta comercial.
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="useCurrentAmountAsMonthly()">
                    Usar monto actual
                </button>
                <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50" @click="calcOpen = false">
                    Cerrar
                </button>
                <button type="button" class="ui-btn rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800" @click="applyAnnualSuggestion()">
                    Aplicar anual sugerido
                </button>
            </div>
        </div>
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
