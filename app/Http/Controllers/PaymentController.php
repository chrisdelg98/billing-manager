<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceCatalogOption;
use App\Models\Subscription;
use App\Support\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::query()->with(['service', 'subscription'])->latest('id');

        if ($request->filled('q')) {
            $search = trim((string) $request->string('q'));

            $query->where(function ($innerQuery) use ($search): void {
                $innerQuery
                    ->where('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subscription', fn ($subscriptionQuery) => $subscriptionQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', (int) $request->integer('service_id'));
        }

        $status = (string) $request->string('status');
        if (in_array($status, [Payment::STATUS_PENDING, Payment::STATUS_CONFIRMED], true)) {
            $query->where('status', $status);
        }

        $method = (string) $request->string('method');
        if (in_array($method, [Payment::METHOD_TRANSFER, Payment::METHOD_CASH, Payment::METHOD_PAYPAL, Payment::METHOD_OTHER], true)) {
            $query->where('method', $method);
        }

        $subscriptionScope = (string) $request->string('subscription_scope');
        if ($subscriptionScope === 'with_subscription') {
            $query->whereNotNull('subscription_id');
        }

        if ($subscriptionScope === 'without_subscription') {
            $query->whereNull('subscription_id');
        }

        if ($request->filled('paid_from')) {
            $query->whereDate('paid_at', '>=', (string) $request->input('paid_from'));
        }

        if ($request->filled('paid_to')) {
            $query->whereDate('paid_at', '<=', (string) $request->input('paid_to'));
        }

        $paidWindow = (string) $request->string('paid_window');
        if ($paidWindow !== '') {
            $today = now()->startOfDay();

            match ($paidWindow) {
                'today' => $query->whereDate('paid_at', $today->toDateString()),
                'last_7' => $query->whereDate('paid_at', '>=', $today->copy()->subDays(6)->toDateString()),
                'last_30' => $query->whereDate('paid_at', '>=', $today->copy()->subDays(29)->toDateString()),
                'this_month' => $query
                    ->whereDate('paid_at', '>=', $today->copy()->startOfMonth()->toDateString())
                    ->whereDate('paid_at', '<=', $today->copy()->endOfMonth()->toDateString()),
                default => null,
            };
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
            'billing_contact_name',
            'billing_contact_email',
            'billing_contact_whatsapp',
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
        $delivery = $this->validatedDeliveryData($request);
        $data = array_merge($data, $this->recipientPayload($delivery));
        $payment = Payment::query()->create($data);

        $this->advanceSubscriptionRenewal($payment);

        AuditLogger::log('created', 'payment', $payment->id, ['amount' => $payment->amount]);

        return $this->finalizeWithDelivery($payment, 'Pago registrado correctamente.', $delivery);
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
            'billing_contact_name',
            'billing_contact_email',
            'billing_contact_whatsapp',
        ]);
        $defaultNotes = (string) ($payment->notes ?? '');
        $currencyOptions = $this->activeCurrencyOptions();

        return view('payments.edit', compact('payment', 'services', 'subscriptions', 'defaultNotes', 'currencyOptions'));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $previousStatus = (string) $payment->status;
        $data = $this->validatedData($request);
        $delivery = $this->validatedDeliveryData($request);
        $data = array_merge($data, $this->recipientPayload($delivery));
        $payment->update($data);

        if ($previousStatus !== Payment::STATUS_CONFIRMED && (string) $payment->status === Payment::STATUS_CONFIRMED) {
            $this->advanceSubscriptionRenewal($payment->fresh());
        }

        AuditLogger::log('updated', 'payment', $payment->id, ['amount' => $payment->amount]);

        return $this->finalizeWithDelivery($payment, 'Pago actualizado correctamente.', $delivery);
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
            'status' => ['nullable', 'in:'.Payment::STATUS_PENDING.','.Payment::STATUS_CONFIRMED],
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
            'method' => ['nullable', 'in:transfer,cash,paypal,other'],
            'reference' => ['nullable', 'string', 'max:120'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['nullable', 'email', 'max:190'],
            'recipient_whatsapp' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['status'] = (string) ($data['status'] ?? Payment::STATUS_CONFIRMED);
        $data['currency'] = strtoupper((string) $data['currency']);
        $data['recipient_whatsapp'] = $this->normalizePhone((string) ($data['recipient_whatsapp'] ?? ''));

        if ($data['status'] === Payment::STATUS_PENDING) {
            $data['method'] = 'other';
        }

        if ($data['status'] === Payment::STATUS_CONFIRMED && empty($data['method'])) {
            throw ValidationException::withMessages([
                'method' => 'Selecciona el metodo de pago al confirmar el cobro.',
            ]);
        }

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

    private function validatedDeliveryData(Request $request): array
    {
        $data = $request->validate([
            'send_method' => ['nullable', 'in:none,email,whatsapp'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['nullable', 'email', 'max:190'],
            'recipient_whatsapp' => ['nullable', 'string', 'max:30'],
        ]);

        $data['send_method'] = (string) ($data['send_method'] ?? 'none');
        $data['recipient_whatsapp'] = $this->normalizePhone((string) ($data['recipient_whatsapp'] ?? ''));

        if ($data['send_method'] === 'email' && empty($data['recipient_email'])) {
            throw ValidationException::withMessages([
                'recipient_email' => 'Indica el correo destino para enviar el comprobante.',
            ]);
        }

        if ($data['send_method'] === 'whatsapp' && empty($data['recipient_whatsapp'])) {
            throw ValidationException::withMessages([
                'recipient_whatsapp' => 'Indica el numero de WhatsApp destino para compartir el cobro.',
            ]);
        }

        return $data;
    }

    private function recipientPayload(array $delivery): array
    {
        return [
            'recipient_name' => $delivery['recipient_name'] ?? null,
            'recipient_email' => $delivery['recipient_email'] ?? null,
            'recipient_whatsapp' => $delivery['recipient_whatsapp'] ?? null,
        ];
    }

    private function finalizeWithDelivery(Payment $payment, string $baseStatus, array $delivery): RedirectResponse
    {
        $statusMessage = $baseStatus;

        if ($delivery['send_method'] === 'email') {
            try {
                $this->sendPaymentVoucherEmail(
                    $payment->fresh(['service', 'subscription']),
                    (string) $delivery['recipient_email'],
                    (string) ($delivery['recipient_name'] ?? '')
                );

                $statusMessage .= ' Comprobante enviado por correo.';
            } catch (Throwable $exception) {
                report($exception);
                $statusMessage .= ' No se pudo enviar el correo; revisa la configuracion SMTP.';
            }

            return redirect()->route('pagos.index')->with('status', $statusMessage);
        }

        if ($delivery['send_method'] === 'whatsapp') {
            $statusMessage .= ' Mensaje listo para enviar por WhatsApp.';

            return redirect()
                ->route('comprobantes.pagos.show', $payment)
                ->with('status', $statusMessage)
                ->with('whatsapp_share_url', $this->buildWhatsAppShareUrl($payment, (string) $delivery['recipient_whatsapp']));
        }

        return redirect()->route('pagos.index')->with('status', $statusMessage);
    }

    private function sendPaymentVoucherEmail(Payment $payment, string $recipientEmail, string $recipientName = ''): void
    {
        $isPending = $payment->isPending();
        $voucherNumber = sprintf($isPending ? 'ORD-%06d' : 'PAGO-%06d', (int) $payment->id);
        $pdfFileName = sprintf('%s-%s.pdf', $isPending ? 'orden-pago' : 'comprobante', $voucherNumber);
        $pdfOutput = $this->buildPaymentVoucherPdf($payment, $voucherNumber, $isPending);

        $subject = $isPending
            ? "Orden de pago {$voucherNumber}"
            : "Comprobante de pago {$voucherNumber}";

        Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $subject, $payment, $voucherNumber, $isPending, $pdfOutput, $pdfFileName): void {
            $toName = trim($recipientName);

            if ($toName !== '') {
                $message->to($recipientEmail, $toName);
            } else {
                $message->to($recipientEmail);
            }

            $message->subject($subject);
            $message->html(view('emails.payment-voucher', compact('payment', 'voucherNumber', 'isPending'))->render());
            $message->attachData($pdfOutput, $pdfFileName, ['mime' => 'application/pdf']);
        });
    }

    private function buildPaymentVoucherPdf(Payment $payment, string $voucherNumber, bool $isPending): string
    {
        $html = view('vouchers.pdf.payment-receipt', compact('payment', 'voucherNumber', 'isPending'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper($this->paymentPaperSize($payment));
        $pdf->render();

        return $pdf->output();
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function paymentPaperSize(Payment $payment): array
    {
        $width = 595.28;
        $baseHeight = 420.0;
        $notesLength = mb_strlen(trim((string) ($payment->notes ?? '')));
        $notesLines = $notesLength > 0 ? (int) ceil($notesLength / 95) : 0;
        $height = $baseHeight + max(0, $notesLines - 2) * 12;

        return [0, 0, $width, min(max($height, 420.0), 900.0)];
    }

    private function buildWhatsAppShareUrl(Payment $payment, string $recipientWhatsapp): string
    {
        $isPending = $payment->isPending();
        $voucherNumber = sprintf($isPending ? 'ORD-%06d' : 'PAGO-%06d', (int) $payment->id);

        $message = sprintf(
            "Hola, te comparto %s %s del servicio %s por %s %s.\nReferencia: %s.\nAdjunto el PDF en este envio.",
            $isPending ? 'la orden de pago' : 'el comprobante de pago',
            $voucherNumber,
            $payment->service?->name ?: 'N/A',
            number_format((float) $payment->amount, 2),
            $payment->currency,
            $payment->reference ?: '-'
        );

        return 'https://wa.me/'.$recipientWhatsapp.'?text='.rawurlencode($message);
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== '' ? $digits : null;
    }

    private function advanceSubscriptionRenewal(Payment $payment): void
    {
        if ((string) $payment->status !== Payment::STATUS_CONFIRMED) {
            return;
        }

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
