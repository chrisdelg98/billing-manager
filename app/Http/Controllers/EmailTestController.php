<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class EmailTestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin();

        $pendingPayments = Payment::query()
            ->where('status', Payment::STATUS_PENDING)
            ->with(['service:id,name', 'subscription:id,name,billing_cycle,next_renewal_at'])
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'service_id', 'subscription_id', 'amount', 'currency', 'paid_at', 'status']);

        $paidPayments = Payment::query()
            ->where('status', Payment::STATUS_CONFIRMED)
            ->with(['service:id,name', 'subscription:id,name,billing_cycle,next_renewal_at'])
            ->orderByDesc('id')
            ->limit(50)
            ->get(['id', 'service_id', 'subscription_id', 'amount', 'currency', 'paid_at', 'status']);

        $subscriptions = Subscription::query()
            ->with('service:id,name')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'service_id', 'name', 'billing_cycle', 'amount', 'currency', 'next_renewal_at']);

        return view('herramientas.correos-prueba', [
            'pendingPayments' => $pendingPayments,
            'paidPayments' => $paidPayments,
            'subscriptions' => $subscriptions,
            'recipientEmail' => (string) ($request->user()->email ?? ''),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'template' => ['required', 'string', 'in:orden_pago,comprobante_pago,recordatorio,bienvenida'],
            'payment_id' => ['nullable', 'integer', 'exists:payments,id'],
            'subscription_id' => ['nullable', 'integer', 'exists:subscriptions,id'],
        ]);

        $user = $request->user();
        $recipientEmail = (string) ($user->email ?? '');
        $recipientName = (string) ($user->name ?? '');

        if ($recipientEmail === '') {
            return redirect()
                ->route('herramientas.correos-prueba.index')
                ->withErrors(['template' => 'Tu usuario no tiene un correo configurado.']);
        }

        try {
            switch ($data['template']) {
                case 'orden_pago':
                case 'comprobante_pago':
                    $payment = Payment::query()
                        ->with(['service:id,name', 'subscription:id,name,billing_cycle,next_renewal_at'])
                        ->findOrFail((int) ($data['payment_id'] ?? 0));

                    $isPending = $data['template'] === 'orden_pago';
                    $voucherNumber = sprintf($isPending ? 'ORD-%06d' : 'PAGO-%06d', (int) $payment->id);
                    $subject = ($isPending
                        ? "Orden de pago {$voucherNumber}"
                        : "Comprobante de pago {$voucherNumber}");

                    Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $subject, $payment, $voucherNumber, $isPending): void {
                        $message->to($recipientEmail, $recipientName !== '' ? $recipientName : null);
                        $message->subject($subject);
                        $message->html(view('emails.payment-voucher', [
                            'payment' => $payment,
                            'voucherNumber' => $voucherNumber,
                            'isPending' => $isPending,
                            'recipientName' => $recipientName,
                        ])->render());
                    });

                    $label = $isPending ? 'orden de pago' : 'comprobante de pago';
                    break;

                case 'recordatorio':
                    $subscription = Subscription::query()
                        ->with('service:id,name')
                        ->findOrFail((int) ($data['subscription_id'] ?? 0));

                    $voucherNumber = sprintf('RMD-%06d', (int) $subscription->id);
                    $daysUntilRenewal = $subscription->daysUntilRenewal();
                    $lastPaymentDate = $subscription->next_renewal_at?->copy()->endOfMonth();
                    $subject = "Recordatorio de pago {$voucherNumber}";

                    Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $subject, $subscription, $voucherNumber, $daysUntilRenewal, $lastPaymentDate): void {
                        $message->to($recipientEmail, $recipientName !== '' ? $recipientName : null);
                        $message->subject($subject);
                        $message->html(view('emails.subscription-reminder-voucher', [
                            'subscription' => $subscription,
                            'voucherNumber' => $voucherNumber,
                            'daysUntilRenewal' => $daysUntilRenewal,
                            'lastPaymentDate' => $lastPaymentDate,
                            'recipientName' => $recipientName,
                        ])->render());
                    });

                    $label = 'recordatorio de pago';
                    break;

                case 'bienvenida':
                    $subscription = Subscription::query()
                        ->with('service:id,name')
                        ->findOrFail((int) ($data['subscription_id'] ?? 0));

                    $voucherNumber = sprintf('BNV-%06d', (int) $subscription->id);
                    $daysUntilRenewal = $subscription->daysUntilRenewal();
                    $lastPaymentDate = $subscription->next_renewal_at?->copy()->endOfMonth();
                    $subject = "[PRUEBA] Bienvenido a tu suscripcion - {$subscription->name}";

                    Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $subject, $subscription, $voucherNumber, $daysUntilRenewal, $lastPaymentDate): void {
                        $message->to($recipientEmail, $recipientName !== '' ? $recipientName : null);
                        $message->subject($subject);
                        $message->html(view('emails.subscription-welcome', [
                            'subscription' => $subscription,
                            'voucherNumber' => $voucherNumber,
                            'daysUntilRenewal' => $daysUntilRenewal,
                            'lastPaymentDate' => $lastPaymentDate,
                            'recipientName' => $recipientName,
                        ])->render());
                    });

                    $label = 'bienvenida';
                    break;

                default:
                    $label = 'correo';
            }
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('herramientas.correos-prueba.index')
                ->withErrors(['template' => 'No se pudo enviar el correo de prueba: '.$exception->getMessage()]);
        }

        return redirect()
            ->route('herramientas.correos-prueba.index')
            ->with('status', "Correo de prueba ({$label}) enviado a {$recipientEmail}.");
    }

    private function authorizeAdmin(): void
    {
        $user = request()->user();

        abort_unless($user && $user->role === 'admin', 403);
    }
}
