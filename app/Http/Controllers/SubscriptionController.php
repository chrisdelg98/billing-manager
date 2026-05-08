<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCatalogOption;
use App\Models\Subscription;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->startOfDay();

        $query = Subscription::query()
            ->with('service')
            ->orderByRaw('CASE WHEN next_renewal_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('next_renewal_at')
            ->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->string('q'));

            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('service', function ($serviceQuery) use ($search): void {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', (int) $request->integer('service_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', (string) $request->string('billing_cycle'));
        }

        if ($request->filled('trial_status')) {
            $trialStatus = (string) $request->string('trial_status');

            if ($trialStatus === 'in_trial') {
                $query
                    ->where('has_trial', true)
                    ->whereNotNull('trial_ends_at')
                    ->whereDate('trial_ends_at', '>=', $today->toDateString());
            }

            if ($trialStatus === 'trial_ended') {
                $query
                    ->where('has_trial', true)
                    ->whereNotNull('trial_ends_at')
                    ->whereDate('trial_ends_at', '<', $today->toDateString());
            }

            if ($trialStatus === 'no_trial') {
                $query->where('has_trial', false);
            }
        }

        if ($request->filled('renewal_window')) {
            $window = (string) $request->string('renewal_window');

            if ($window === 'overdue') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereDate('next_renewal_at', '<', $today->toDateString());
            }

            if ($window === 'next_7') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereBetween('next_renewal_at', [
                        $today->toDateString(),
                        $today->copy()->addDays(7)->toDateString(),
                    ]);
            }

            if ($window === 'next_30') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereBetween('next_renewal_at', [
                        $today->toDateString(),
                        $today->copy()->addDays(30)->toDateString(),
                    ]);
            }

            if ($window === 'no_date') {
                $query->whereNull('next_renewal_at');
            }
        }

        if ($request->filled('renewal_risk')) {
            $risk = (string) $request->string('renewal_risk');

            if ($risk === 'danger') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereDate('next_renewal_at', '<=', $today->copy()->addDays(2)->toDateString());
            }

            if ($risk === 'warning') {
                $query
                    ->whereNotNull('next_renewal_at')
                    ->whereBetween('next_renewal_at', [
                        $today->copy()->addDays(3)->toDateString(),
                        $today->copy()->addDays(10)->toDateString(),
                    ]);
            }

            if ($risk === 'safe') {
                $query->where(function ($builder) use ($today): void {
                    $builder
                        ->whereNull('next_renewal_at')
                        ->orWhereDate('next_renewal_at', '>', $today->copy()->addDays(10)->toDateString());
                });
            }
        }

        $subscriptions = $query->paginate(10)->withQueryString();
        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        return view('subscriptions.index', compact('subscriptions', 'services'));
    }

    public function create(): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $currencyOptions = $this->activeCurrencyOptions();

        return view('subscriptions.create', compact('services', 'currencyOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $subscription = Subscription::query()->create($this->validatedData($request));
        AuditLogger::log('created', 'subscription', $subscription->id, ['name' => $subscription->name]);

        return redirect()->route('suscripciones.index')->with('status', 'Suscripcion creada correctamente.');
    }

    public function edit(Subscription $subscription): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $currencyOptions = $this->activeCurrencyOptions();

        return view('subscriptions.edit', compact('subscription', 'services', 'currencyOptions'));
    }

    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $subscription->update($this->validatedData($request));
        AuditLogger::log('updated', 'subscription', $subscription->id, ['name' => $subscription->name]);

        return redirect()->route('suscripciones.index')->with('status', 'Suscripcion actualizada correctamente.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        $id = $subscription->id;
        $name = $subscription->name;
        $subscription->delete();

        AuditLogger::log('deleted', 'subscription', $id, ['name' => $name]);

        return redirect()->route('suscripciones.index')->with('status', 'Suscripcion eliminada correctamente.');
    }

    public function duplicate(Subscription $subscription): RedirectResponse
    {
        $duplicate = $subscription->replicate();
        $duplicate->name = $subscription->name.' (copia)';
        $duplicate->license_api_enabled = false;
        $duplicate->license_code = null;
        $duplicate->license_secret_hash = null;
        $duplicate->license_secret_encrypted = null;
        $duplicate->license_secret_hint = null;
        $duplicate->license_key_rotated_at = null;
        $duplicate->license_key_revoked_at = null;
        $duplicate->license_last_used_at = null;
        $duplicate->save();

        AuditLogger::log('duplicated', 'subscription', $duplicate->id, [
            'source_subscription_id' => $subscription->id,
            'source_name' => $subscription->name,
        ]);

        return redirect()
            ->route('suscripciones.edit', $duplicate)
            ->with('status', 'Suscripcion duplicada. Revisa y ajusta los datos antes de guardar.');
    }

    private function validatedData(Request $request): array
    {
        $request->merge([
            'currency' => strtoupper((string) $request->input('currency', '')),
        ]);

        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'name' => ['required', 'string', 'max:120'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => [
                'required',
                'string',
                'size:3',
                Rule::exists('service_catalog_options', 'name')->where(fn ($query) => $query
                    ->where('catalog_type', ServiceCatalogOption::TYPE_CURRENCY)
                    ->where('is_active', true)),
            ],
            'next_renewal_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'billing_contact_name' => ['nullable', 'string', 'max:120'],
            'billing_contact_email' => ['nullable', 'email', 'max:190'],
            'billing_contact_whatsapp' => ['nullable', 'string', 'max:30'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'license_api_enabled' => ['nullable', 'boolean'],
        ]);

        $data['currency'] = strtoupper((string) $data['currency']);
        $data['billing_contact_whatsapp'] = $this->normalizePhone((string) ($data['billing_contact_whatsapp'] ?? ''));
        $data['has_trial'] = (bool) ($data['has_trial'] ?? false);

        if (! empty($data['trial_ends_at'])) {
            $data['has_trial'] = true;
        }

        if ($data['has_trial'] && empty($data['trial_ends_at'])) {
            throw ValidationException::withMessages([
                'trial_ends_at' => 'Indica hasta cuando llega el periodo de prueba.',
            ]);
        }

        if ($data['has_trial']) {
            $trialEnd = Carbon::parse((string) $data['trial_ends_at'])->startOfDay();

            if (! empty($data['next_renewal_at'])) {
                $manualNextRenewal = Carbon::parse((string) $data['next_renewal_at'])->startOfDay();

                if ($manualNextRenewal->lt($trialEnd->copy()->addDay())) {
                    throw ValidationException::withMessages([
                        'next_renewal_at' => 'La renovacion manual debe ser posterior al fin del periodo de prueba.',
                    ]);
                }

                $data['next_renewal_at'] = $manualNextRenewal->toDateString();
            } else {
                $data['next_renewal_at'] = $this->automaticRenewalAfterTrial($trialEnd, (string) $data['billing_cycle'])->toDateString();
            }
        }

        if (! $data['has_trial']) {
            $data['trial_ends_at'] = null;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['license_api_enabled'] = (bool) ($data['license_api_enabled'] ?? false);

        return $data;
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    private function automaticRenewalAfterTrial(Carbon $trialEnd, string $billingCycle): Carbon
    {
        $startAfterTrial = $trialEnd->copy()->addDay()->startOfDay();

        return $billingCycle === 'yearly'
            ? $startAfterTrial->copy()->addYearNoOverflow()->subDay()->startOfDay()
            : $startAfterTrial->copy()->addMonthNoOverflow()->subDay()->startOfDay();
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
