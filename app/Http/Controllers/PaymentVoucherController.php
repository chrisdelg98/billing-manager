<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;
use Throwable;

class PaymentVoucherController extends Controller
{
    public function payment(Request $request, Payment $payment): View|Response
    {
        $payment->loadMissing([
            'service:id,name',
            'subscription:id,name,billing_cycle,next_renewal_at',
        ]);

        $isPending = $payment->isPending();
        $voucherNumber = sprintf($isPending ? 'ORD-%06d' : 'PAGO-%06d', (int) $payment->id);

        if ($request->string('format')->toString() === 'pdf') {
            $html = view('vouchers.pdf.payment-receipt', compact('payment', 'voucherNumber', 'isPending'))->render();

            $filePrefix = $isPending ? 'orden-pago' : 'comprobante';

            return $this->renderPdf(
                $html,
                $this->paymentPaperSize($payment),
                "{$filePrefix}-{$voucherNumber}.pdf"
            );
        }

        return view('vouchers.payment-receipt', compact('payment', 'voucherNumber', 'isPending'));
    }

    public function sendPaymentEmail(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['required', 'email', 'max:190'],
        ]);

        $payment->loadMissing([
            'service:id,name',
            'subscription:id,name,billing_cycle,next_renewal_at',
        ]);

        $isPending = $payment->isPending();
        $voucherNumber = sprintf($isPending ? 'ORD-%06d' : 'PAGO-%06d', (int) $payment->id);
        $filePrefix = $isPending ? 'orden-pago' : 'comprobante';
        $pdfFileName = "{$filePrefix}-{$voucherNumber}.pdf";

        $recipientEmail = (string) $data['recipient_email'];
        $recipientName = trim((string) ($data['recipient_name'] ?? ''));
        if ($recipientName === '') {
            $recipientName = (string) ($payment->recipient_name ?? '');
        }

        $pdfOutput = $this->buildPaymentVoucherPdf($payment, $voucherNumber, $isPending);
        $subject = $isPending
            ? "Orden de pago {$voucherNumber}"
            : "Comprobante de pago {$voucherNumber}";

        try {
            Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $subject, $payment, $voucherNumber, $isPending, $pdfOutput, $pdfFileName): void {
                if ($recipientName !== '') {
                    $message->to($recipientEmail, $recipientName);
                } else {
                    $message->to($recipientEmail);
                }

                $message->subject($subject);
                $message->html(view('emails.payment-voucher', compact(
                    'payment',
                    'voucherNumber',
                    'isPending',
                    'recipientName'
                ))->render());
                $message->attachData($pdfOutput, $pdfFileName, ['mime' => 'application/pdf']);
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('comprobantes.pagos.show', $payment)
                ->with('status', 'No se pudo enviar el correo; revisa la configuracion SMTP.');
        }

        $successMessage = $isPending
            ? 'Orden de pago enviada por correo con el voucher adjunto.'
            : 'Comprobante de pago enviado por correo con el voucher adjunto.';

        return redirect()
            ->route('comprobantes.pagos.show', $payment)
            ->with('status', $successMessage);
    }

    public function reminder(Request $request, Subscription $subscription): View|Response
    {
        $subscription->loadMissing('service:id,name');

        $voucherNumber = sprintf('RMD-%06d', (int) $subscription->id);
        $daysUntilRenewal = $subscription->daysUntilRenewal();
        $lastPaymentDate = $subscription->next_renewal_at?->copy()->endOfMonth();

        if ($request->string('format')->toString() === 'pdf') {
            $html = view('vouchers.pdf.payment-reminder', compact('subscription', 'voucherNumber', 'daysUntilRenewal', 'lastPaymentDate'))->render();

            return $this->renderPdf(
                $html,
                $this->reminderPaperSize(),
                "recordatorio-{$voucherNumber}.pdf"
            );
        }

        return view('vouchers.payment-reminder', compact('subscription', 'voucherNumber', 'daysUntilRenewal', 'lastPaymentDate'));
    }

    public function sendReminderEmail(Request $request, Subscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'recipient_email' => ['required', 'email', 'max:190'],
        ]);

        $subscription->loadMissing('service:id,name');

        $voucherNumber = sprintf('RMD-%06d', (int) $subscription->id);
        $daysUntilRenewal = $subscription->daysUntilRenewal();
        $lastPaymentDate = $subscription->next_renewal_at?->copy()->endOfMonth();

        $recipientEmail = (string) $data['recipient_email'];
        $recipientName = trim((string) ($data['recipient_name'] ?? ''));
        if ($recipientName === '') {
            $recipientName = (string) ($subscription->billing_contact_name ?? '');
        }

        $subject = "Recordatorio de pago {$voucherNumber}";

        try {
            Mail::send([], [], function ($message) use ($recipientEmail, $recipientName, $subject, $subscription, $voucherNumber, $daysUntilRenewal, $lastPaymentDate): void {
                if ($recipientName !== '') {
                    $message->to($recipientEmail, $recipientName);
                } else {
                    $message->to($recipientEmail);
                }

                $message->subject($subject);
                $message->html(view('emails.subscription-reminder-voucher', compact(
                    'subscription',
                    'voucherNumber',
                    'daysUntilRenewal',
                    'lastPaymentDate',
                    'recipientName'
                ))->render());
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('comprobantes.suscripciones.recordatorio', $subscription)
                ->with('status', 'No se pudo enviar el correo; revisa la configuracion SMTP.');
        }

        return redirect()
            ->route('comprobantes.suscripciones.recordatorio', $subscription)
            ->with('status', 'Recordatorio enviado por correo.');
    }

    private function buildReminderVoucherPdf(Subscription $subscription, string $voucherNumber, ?int $daysUntilRenewal, $lastPaymentDate): string
    {
        $html = view('vouchers.pdf.payment-reminder', compact('subscription', 'voucherNumber', 'daysUntilRenewal', 'lastPaymentDate'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper($this->reminderPaperSize());
        $pdf->render();

        return $pdf->output();
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
     * @param array{0:int,1:int,2:float,3:float} $paper
     */
    private function renderPdf(string $html, array $paper, string $fileName): Response
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper($paper);
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function paymentPaperSize(Payment $payment): array
    {
        return $this->voucherPaperSize();
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function reminderPaperSize(): array
    {
        return $this->voucherPaperSize();
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function voucherPaperSize(): array
    {
        return [0, 0, 595.28, 540.0];
    }

}
