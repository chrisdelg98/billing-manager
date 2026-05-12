<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentVouchersTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_payment_receipt_voucher(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE',
            'billing_cycle' => 'monthly',
            'amount' => 100,
            'currency' => 'USD',
            'next_renewal_at' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $payment = Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'TRX-VCH-001',
            'notes' => 'Pago validado',
        ]);

        $this->actingAs($user)
            ->get(route('comprobantes.pagos.show', $payment))
            ->assertOk()
            ->assertSee('Comprobante de pago')
            ->assertSee('PAGO-')
            ->assertSee('PAGO CONFIRMADO')
            ->assertSee('CLINEXUS')
            ->assertSee('Enviar comprobante por correo');

        $this->actingAs($user)
            ->get(route('comprobantes.pagos.show', ['payment' => $payment, 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="comprobante-'.sprintf('PAGO-%06d', (int) $payment->id).'.pdf"');

        $this->actingAs($user)
            ->post(route('comprobantes.pagos.send', $payment), [
                'recipient_name' => 'Patricia Garcia',
                'recipient_email' => 'patricia@example.com',
            ])
            ->assertRedirect(route('comprobantes.pagos.show', $payment))
            ->assertSessionHas('status', 'Comprobante enviado por correo con el voucher adjunto.');
    }

    public function test_authenticated_user_can_open_subscription_reminder_voucher(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE ANUAL',
            'billing_cycle' => 'yearly',
            'amount' => 300,
            'currency' => 'USD',
            'next_renewal_at' => now()->addDays(5)->toDateString(),
            'billing_contact_name' => 'Krissia Castro',
            'billing_contact_email' => 'krissia@example.com',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('comprobantes.suscripciones.recordatorio', $subscription))
            ->assertOk()
            ->assertSee('Voucher de recordatorio de pago')
            ->assertSee('PENDIENTE DE PAGO')
            ->assertSee('CLINEXUS CORE ANUAL')
            ->assertSee('300.00 USD')
            ->assertSee('Ultimo dia de pago')
            ->assertSee('Enviar recordatorio por correo')
            ->assertSee('Para gestionar el pago, comunicarte con Christian Arevalo.');

        $this->actingAs($user)
            ->get(route('comprobantes.suscripciones.recordatorio', ['subscription' => $subscription, 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="recordatorio-'.sprintf('RMD-%06d', (int) $subscription->id).'.pdf"');

        $this->actingAs($user)
            ->post(route('comprobantes.suscripciones.recordatorio.send', $subscription), [
                'recipient_name' => 'Krissia Castro',
                'recipient_email' => 'krissia@example.com',
            ])
            ->assertRedirect(route('comprobantes.suscripciones.recordatorio', $subscription))
            ->assertSessionHas('status', 'Recordatorio enviado por correo con el voucher adjunto.');
    }

    public function test_authenticated_user_can_open_pending_payment_order_voucher(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE',
            'billing_cycle' => 'monthly',
            'amount' => 100,
            'currency' => 'USD',
            'next_renewal_at' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $payment = Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 90,
            'currency' => 'USD',
            'method' => 'other',
            'reference' => 'ORD-001',
            'notes' => 'Orden con descuento aplicado',
        ]);

        $this->actingAs($user)
            ->get(route('comprobantes.pagos.show', $payment))
            ->assertOk()
            ->assertSee('Orden de pago')
            ->assertSee('ORD-')
            ->assertSee('PAGO PENDIENTE')
            ->assertSee('Por confirmar')
            ->assertSee('Enviar orden de pago por correo');

        $this->actingAs($user)
            ->get(route('comprobantes.pagos.show', ['payment' => $payment, 'format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="orden-pago-'.sprintf('ORD-%06d', (int) $payment->id).'.pdf"');
    }

    public function test_guest_cannot_access_voucher_routes(): void
    {
        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE',
            'billing_cycle' => 'monthly',
            'amount' => 100,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $payment = Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'transfer',
        ]);

        $this->get(route('comprobantes.pagos.show', $payment))
            ->assertRedirect(route('login'));

        $this->get(route('comprobantes.suscripciones.recordatorio', $subscription))
            ->assertRedirect(route('login'));
    }
}
