<?php

namespace App\Http\Controllers;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class PaymentVoucherController extends Controller
{
    public function payment(Request $request, Payment $payment): View|Response
    {
        $payment->loadMissing([
            'service:id,name',
            'subscription:id,name,billing_cycle,next_renewal_at',
        ]);

        $voucherNumber = sprintf('PAGO-%06d', (int) $payment->id);

        if ($request->string('format')->toString() === 'pdf') {
            $html = view('vouchers.pdf.payment-receipt', compact('payment', 'voucherNumber'))->render();

            return $this->renderPdf(
                $html,
                $this->paymentPaperSize($payment),
                "comprobante-{$voucherNumber}.pdf"
            );
        }

        return view('vouchers.payment-receipt', compact('payment', 'voucherNumber'));
    }

    public function reminder(Request $request, Subscription $subscription): View|Response
    {
        $subscription->loadMissing('service:id,name');

        $voucherNumber = sprintf('RMD-%06d', (int) $subscription->id);
        $daysUntilRenewal = $subscription->daysUntilRenewal();

        if ($request->string('format')->toString() === 'pdf') {
            $html = view('vouchers.pdf.payment-reminder', compact('subscription', 'voucherNumber', 'daysUntilRenewal'))->render();

            return $this->renderPdf(
                $html,
                $this->reminderPaperSize(),
                "recordatorio-{$voucherNumber}.pdf"
            );
        }

        return view('vouchers.payment-reminder', compact('subscription', 'voucherNumber', 'daysUntilRenewal'));
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
        $width = 595.28;
        $baseHeight = 420.0;
        $notesLength = mb_strlen(trim((string) ($payment->notes ?? '')));
        $notesLines = $notesLength > 0 ? (int) ceil($notesLength / 95) : 0;
        $height = $baseHeight + max(0, $notesLines - 2) * 12;

        return [0, 0, $width, min(max($height, 420.0), 900.0)];
    }

    /**
     * @return array{0:int,1:int,2:float,3:float}
     */
    private function reminderPaperSize(): array
    {
        return [0, 0, 595.28, 470.0];
    }
}
