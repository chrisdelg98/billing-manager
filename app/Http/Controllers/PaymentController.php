<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $subscriptions = Subscription::query()->orderBy('name')->get(['id', 'name', 'service_id']);

        return view('payments.create', compact('services', 'subscriptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $payment = Payment::query()->create($data);

        AuditLogger::log('created', 'payment', $payment->id, ['amount' => $payment->amount]);

        return redirect()->route('pagos.index')->with('status', 'Pago registrado correctamente.');
    }

    public function edit(Payment $payment): View
    {
        $services = Service::query()->orderBy('name')->get(['id', 'name']);
        $subscriptions = Subscription::query()->orderBy('name')->get(['id', 'name', 'service_id']);

        return view('payments.edit', compact('payment', 'services', 'subscriptions'));
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
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'subscription_id' => ['nullable', 'exists:subscriptions,id'],
            'paid_at' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'method' => ['required', 'in:transfer,cash,other'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['currency'] = strtoupper((string) $data['currency']);

        if (! empty($data['subscription_id'])) {
            $belongsToService = Subscription::query()
                ->whereKey($data['subscription_id'])
                ->where('service_id', $data['service_id'])
                ->exists();

            if (! $belongsToService) {
                throw ValidationException::withMessages([
                    'subscription_id' => 'La suscripcion seleccionada no pertenece al servicio indicado.',
                ]);
            }
        }

        return $data;
    }
}
