<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Subscription;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Subscription::query()->with('service')->latest();

        if ($request->filled('service_id')) {
            $query->where('service_id', (int) $request->integer('service_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->toString() === 'active');
        }

        $subscriptions = $query->paginate(10)->withQueryString();
        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        return view('subscriptions.index', compact('subscriptions', 'services'));
    }

    public function create(): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);

        return view('subscriptions.create', compact('services'));
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

        return view('subscriptions.edit', compact('subscription', 'services'));
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
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'name' => ['required', 'string', 'max:120'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'next_renewal_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_ends_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['currency'] = strtoupper((string) $data['currency']);
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
            $trialEnd = Carbon::parse((string) $data['trial_ends_at'])->toDateString();
            $nextRenewal = ! empty($data['next_renewal_at'])
                ? Carbon::parse((string) $data['next_renewal_at'])->toDateString()
                : null;

            if (! $nextRenewal || $nextRenewal < $trialEnd) {
                $data['next_renewal_at'] = $trialEnd;
            }
        }

        if (! $data['has_trial']) {
            $data['trial_ends_at'] = null;
        }

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
