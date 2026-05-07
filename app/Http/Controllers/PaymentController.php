<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceCatalogOption;
use App\Models\Subscription;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::query()->with(['service', 'subscription'])->latest('paid_at');

        if ($request->filled('service_id')) {
            $query->where('service_id', (int) $request->integer('service_id'));
        }

        $payments = $query->paginate(12)->withQueryString();
        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        return view('payments.index', compact('payments', 'services'));
    }

    public function create(Request $request): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $subscriptions = Subscription::query()->orderBy('name')->get([
            'id',
            'name',
            'service_id',
            'amount',
            'currency',
            'notes',
        ]);

        $defaultSubscription = null;

        if ($request->filled('subscription_id')) {
            $defaultSubscription = Subscription::query()->find((int) $request->integer('subscription_id'));
        }

        $defaultServiceId = (int) ($request->integer('service_id') ?: ($defaultSubscription?->service_id ?? 0));
        $defaultSubscriptionId = (int) ($defaultSubscription?->id ?? 0);
        $defaultBaseAmount = $defaultSubscription ? (float) $defaultSubscription->amount : null;
        $defaultAmount = $defaultSubscription ? (float) $defaultSubscription->amount : null;
        $defaultCurrency = $defaultSubscription ? (string) $defaultSubscription->currency : 'USD';
        $defaultNotes = $defaultSubscription ? (string) ($defaultSubscription->notes ?? '') : '';
        $currencyOptions = $this->activeCurrencyOptions();

        return view('payments.create', compact(
            'services',
            'subscriptions',
            'defaultServiceId',
            'defaultSubscriptionId',
            'defaultBaseAmount',
            'defaultAmount',
            'defaultCurrency',
            'defaultNotes',
            'currencyOptions',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $payment = Payment::query()->create($data);

        $this->advanceSubscriptionRenewal($payment);

        AuditLogger::log('created', 'payment', $payment->id, ['amount' => $payment->amount]);

        return redirect()->route('pagos.index')->with('status', 'Pago registrado correctamente.');
    }

    public function edit(Payment $payment): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $subscriptions = Subscription::query()->orderBy('name')->get([
            'id',
            'name',
            'service_id',
            'amount',
            'currency',
            'notes',
        ]);
        $defaultNotes = (string) ($payment->notes ?? '');
        $currencyOptions = $this->activeCurrencyOptions();

        return view('payments.edit', compact('payment', 'services', 'subscriptions', 'defaultNotes', 'currencyOptions'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $data = $this->validatedData($request);
        $payment->update($data);

        AuditLogger::log('updated', 'payment', $payment->id, ['amount' => $payment->amount]);

        return redirect()->route('pagos.index')->with('status', 'Pago actualizado correctamente.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $id = $payment->id;
        $amount = $payment->amount;
        $payment->delete();

        AuditLogger::log('deleted', 'payment', $id, ['amount' => $amount]);

        return redirect()->route('pagos.index')->with('status', 'Pago eliminado correctamente.');
    }

    private function validatedData(Request $request): array
    {
        $request->merge([
            'currency' => strtoupper((string) $request->input('currency', '')),
        ]);

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'paid_at' => ['required', 'date'],
            'base_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => [
                'required',
                'string',
                'size:3',
                Rule::exists('service_catalog_options', 'name')->where(fn ($query) => $query
                    ->where('catalog_type', ServiceCatalogOption::TYPE_CURRENCY)
                    ->where('is_active', true)),
            ],
            'method' => ['required', 'in:transfer,cash,other'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['currency'] = strtoupper((string) $data['currency']);
        $subscription = null;

        if (! empty($data['subscription_id'])) {
            $subscription = Subscription::query()
                ->select(['id', 'service_id', 'billing_cycle'])
                ->find((int) $data['subscription_id']);

            $belongsToService = $subscription && (int) $subscription->service_id === (int) $data['service_id'];

            if (! $belongsToService) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'La suscripcion seleccionada no pertenece al servicio indicado.',
                ]);
            }
        }

        $data['covered_period_start'] = Carbon::parse((string) $data['paid_at'])
            ->startOfMonth()
            ->toDateString();

        $baseAmount = (float) ($data['base_amount'] ?? 0);
        $discountPercent = (float) ($data['discount_percent'] ?? 0);
        $discountAmount = (float) ($data['discount_amount'] ?? 0);

        if ($baseAmount > 0 && ($discountPercent > 0 || $discountAmount > 0)) {
            if ($discountAmount <= 0) {
                $discountAmount = round($baseAmount * ($discountPercent / 100), 2);
            } elseif ($discountPercent <= 0) {
                $discountPercent = round(($discountAmount / $baseAmount) * 100, 2);
            }

            $discountAmount = min($discountAmount, $baseAmount);
            $data['amount'] = round($baseAmount - $discountAmount, 2);

            $discountDetail = sprintf(
                'Descuento aplicado: %.2f%% (%.2f %s) sobre base %.2f %s.',
                $discountPercent,
                $discountAmount,
                $data['currency'],
                $baseAmount,
                $data['currency']
            );

            $data['notes'] = trim(($data['notes'] ?? '')."\n".$discountDetail);
        }

        unset($data['base_amount'], $data['discount_percent'], $data['discount_amount']);

        return $data;
    }

    private function advanceSubscriptionRenewal(Payment $payment): void
    {
        if (! $payment->subscription_id) {
            return;
        }

        $subscription = Subscription::query()->find($payment->subscription_id);

        if (! $subscription || ! $subscription->is_active) {
            return;
        }

        if (! in_array($subscription->billing_cycle, ['monthly', 'yearly'], true)) {
            return;
        }

        $coveredStart = $payment->covered_period_start
            ? $payment->covered_period_start->copy()->startOfMonth()
            : $payment->paid_at->copy()->startOfMonth();

        $nextRenewal = $subscription->next_renewal_at
            ? $subscription->next_renewal_at->copy()->startOfDay()
            : $coveredStart->copy()->endOfMonth();

        if ($coveredStart->lt($nextRenewal->copy()->startOfMonth())) {
            return;
        }

        while ($nextRenewal->copy()->startOfMonth()->lte($coveredStart)) {
            $nextRenewal = $subscription->billing_cycle === 'yearly'
                ? $nextRenewal->addYearNoOverflow()
                : $nextRenewal->addMonthNoOverflow();
        }

        $subscription->update([
            'next_renewal_at' => $nextRenewal->toDateString(),
        ]);
    }

    private function activeCurrencyOptions(): Collection
    {
        return ServiceCatalogOption::query()
            ->ofType(ServiceCatalogOption::TYPE_CURRENCY)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');
    }
}
