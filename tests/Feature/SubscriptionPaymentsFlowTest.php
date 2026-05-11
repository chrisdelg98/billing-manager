<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPaymentsFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_duplicate_subscription_from_index_action(): void
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
            'amount' => 25,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('suscripciones.duplicate', $subscription))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('subscriptions', 2);

        $duplicate = Subscription::query()
            ->where('name', 'CLINEXUS CORE (copia)')
            ->firstOrFail();

        $this->assertSame($service->id, $duplicate->service_id);
        $this->assertEquals('25.00', $duplicate->amount);
    }

    public function test_payment_create_can_be_prefilled_from_subscription(): void
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
            'amount' => 25,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('pagos.create', [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
            ]))
            ->assertOk()
            ->assertSee("subscriptionId: '{$subscription->id}'", false)
            ->assertSee('baseAmount: Number(25)', false)
            ->assertSee('Descuento (%)')
            ->assertDontSee('Periodo que cubre');
    }

    public function test_payment_store_applies_discount_percentage_to_final_amount(): void
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
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'paid_at' => now()->toDateString(),
                'base_amount' => 100,
                'discount_percent' => 15,
                'discount_amount' => 0,
                'amount' => 100,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'DISC-001',
                'notes' => 'Pago con descuento',
            ])
            ->assertRedirect(route('pagos.index'));

        $payment = Payment::query()->firstOrFail();

        $this->assertEquals('85.00', (string) $payment->amount);
        $this->assertStringContainsString('Descuento aplicado', (string) $payment->notes);
    }

    public function test_payment_store_accepts_paypal_method(): void
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
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'status' => 'confirmed',
                'paid_at' => '2026-06-12',
                'base_amount' => 100,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 100,
                'currency' => 'USD',
                'method' => 'paypal',
                'reference' => 'PAYPAL-001',
            ])
            ->assertRedirect(route('pagos.index'));

        $payment = Payment::query()->firstOrFail();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('paypal', $payment->method);
        $this->assertEquals('2026-07-30', $subscription->fresh()->next_renewal_at?->toDateString());
    }

    public function test_payment_store_requires_email_when_send_method_is_email(): void
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
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'status' => 'confirmed',
                'paid_at' => '2026-06-12',
                'base_amount' => 100,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 100,
                'currency' => 'USD',
                'method' => 'transfer',
                'send_method' => 'email',
            ])
            ->assertSessionHasErrors('recipient_email');
    }

    public function test_payment_store_can_prepare_manual_whatsapp_delivery(): void
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
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'status' => 'pending',
                'paid_at' => '2026-06-12',
                'base_amount' => 100,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 100,
                'currency' => 'USD',
                'reference' => 'ORD-WA-001',
                'send_method' => 'whatsapp',
                'recipient_name' => 'Cliente Demo',
                'recipient_whatsapp' => '+1 (809) 555-1234',
            ]);

        $payment = Payment::query()->firstOrFail();

        $response
            ->assertRedirect(route('comprobantes.pagos.show', $payment))
            ->assertSessionHas('whatsapp_share_url');

        $this->assertSame('18095551234', $payment->recipient_whatsapp);
    }

    public function test_payment_store_allows_pending_order_with_discount_without_advancing_renewal(): void
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
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'status' => 'pending',
                'paid_at' => '2026-06-10',
                'base_amount' => 100,
                'discount_percent' => 10,
                'discount_amount' => 0,
                'amount' => 100,
                'currency' => 'USD',
                'reference' => 'ORD-DISC-001',
                'notes' => 'Orden con descuento',
            ])
            ->assertRedirect(route('pagos.index'));

        $payment = Payment::query()->firstOrFail();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('other', $payment->method);
        $this->assertEquals('90.00', (string) $payment->amount);
        $this->assertEquals('2026-06-30', $subscription->fresh()->next_renewal_at?->toDateString());
    }

    public function test_pending_payment_can_be_confirmed_later_and_then_advances_renewal(): void
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
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $payment = Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'status' => 'pending',
            'paid_at' => '2026-06-10',
            'covered_period_start' => '2026-06-01',
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'other',
            'reference' => 'ORD-TO-CONFIRM',
        ]);

        $this->actingAs($user)
            ->put(route('pagos.update', $payment), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'status' => 'confirmed',
                'paid_at' => '2026-06-12',
                'base_amount' => 100,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 100,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'PAY-001',
            ])
            ->assertRedirect(route('pagos.index'));

        $payment = $payment->fresh();

        $this->assertSame('confirmed', $payment->status);
        $this->assertSame('transfer', $payment->method);
        $this->assertEquals('2026-07-30', $subscription->fresh()->next_renewal_at?->toDateString());
    }

    public function test_payment_store_advances_monthly_subscription_next_renewal(): void
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
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'paid_at' => '2026-06-30',
                'base_amount' => 25,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 25,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'RNW-MTH-001',
            ])
            ->assertRedirect(route('pagos.index'));

        $this->assertEquals('2026-07-30', $subscription->fresh()->next_renewal_at?->toDateString());
    }

    public function test_subscription_store_accepts_billing_contact_fields(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('suscripciones.store'), [
                'service_id' => $service->id,
                'name' => 'CLINEXUS CONTACTO',
                'billing_cycle' => 'monthly',
                'amount' => 99,
                'currency' => 'USD',
                'is_active' => 1,
                'billing_contact_name' => 'Mariana Perez',
                'billing_contact_email' => 'mariana@example.com',
                'billing_contact_whatsapp' => '+1 (809) 555-7777',
            ])
            ->assertRedirect(route('suscripciones.index'));

        $this->assertDatabaseHas('subscriptions', [
            'name' => 'CLINEXUS CONTACTO',
            'billing_contact_name' => 'Mariana Perez',
            'billing_contact_email' => 'mariana@example.com',
            'billing_contact_whatsapp' => '18095557777',
        ]);
    }

    public function test_payment_store_advances_yearly_subscription_next_renewal(): void
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
            'amount' => 200,
            'currency' => 'USD',
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'paid_at' => '2026-06-30',
                'base_amount' => 200,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 200,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'RNW-YR-001',
            ])
            ->assertRedirect(route('pagos.index'));

        $this->assertEquals('2027-06-30', $subscription->fresh()->next_renewal_at?->toDateString());
    }

    public function test_payment_create_form_hides_manual_covered_period_field(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $subscription = Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE ANUAL PREFILL',
            'billing_cycle' => 'yearly',
            'amount' => 200,
            'currency' => 'USD',
            'next_renewal_at' => '2027-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('pagos.create', [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
            ]))
            ->assertOk()
            ->assertDontSee('Periodo que cubre');
    }

    public function test_payment_store_allows_multiple_records_for_same_subscription_period(): void
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
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'paid_at' => '2026-06-10',
            'covered_period_start' => '2026-06-01',
            'amount' => 25,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'RNW-DUP-001',
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'paid_at' => '2026-06-20',
                'base_amount' => 25,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 25,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'RNW-DUP-002',
            ])
            ->assertRedirect(route('pagos.index'));

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_yearly_payment_allows_new_record_in_following_month(): void
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
            'amount' => 200,
            'currency' => 'USD',
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'paid_at' => '2026-06-10',
            'covered_period_start' => '2026-06-01',
            'amount' => 200,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'ANUAL-001',
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'paid_at' => '2026-07-05',
                'base_amount' => 200,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 200,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'ANUAL-002',
            ])
            ->assertRedirect(route('pagos.index'));

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_payment_date_month_advances_subscription_next_renewal(): void
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
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => '2026-06-30',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('pagos.store'), [
                'service_id' => $service->id,
                'subscription_id' => $subscription->id,
                'paid_at' => '2026-06-15',
                'base_amount' => 25,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'amount' => 25,
                'currency' => 'USD',
                'method' => 'transfer',
                'reference' => 'RNW-LATE-001',
            ])
            ->assertRedirect(route('pagos.index'));

        $this->assertEquals('2026-07-30', $subscription->fresh()->next_renewal_at?->toDateString());
    }

    public function test_subscription_can_store_optional_trial_period(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $trialEnd = now()->addDays(14)->toDateString();

        $this->actingAs($user)
            ->post(route('suscripciones.store'), [
                'service_id' => $service->id,
                'name' => 'CLINEXUS CORE - PRUEBA',
                'billing_cycle' => 'monthly',
                'amount' => 25,
                'currency' => 'USD',
                'has_trial' => 1,
                'trial_ends_at' => $trialEnd,
                'is_active' => 1,
            ])
            ->assertRedirect(route('suscripciones.index'));

        $this->assertDatabaseHas('subscriptions', [
            'name' => 'CLINEXUS CORE - PRUEBA',
            'has_trial' => 1,
        ]);

        $created = Subscription::query()->where('name', 'CLINEXUS CORE - PRUEBA')->firstOrFail();

        $this->assertSame($trialEnd, $created->trial_ends_at?->toDateString());
        $this->assertSame($trialEnd, $created->next_renewal_at?->toDateString());
    }

    public function test_subscription_trial_respects_manual_next_renewal_override(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('suscripciones.store'), [
                'service_id' => $service->id,
                'name' => 'CLINEXUS CORE - PRUEBA MANUAL',
                'billing_cycle' => 'yearly',
                'amount' => 120,
                'currency' => 'USD',
                'has_trial' => 1,
                'trial_ends_at' => '2026-06-30',
                'next_renewal_at' => '2027-08-15',
                'is_active' => 1,
            ])
            ->assertRedirect(route('suscripciones.index'));

        $subscription = Subscription::query()->where('name', 'CLINEXUS CORE - PRUEBA MANUAL')->firstOrFail();

        $this->assertSame('2027-08-15', $subscription->next_renewal_at?->toDateString());
    }

    public function test_subscription_keeps_trial_history_after_trial_end(): void
    {
        $subscription = Subscription::query()->create([
            'service_id' => Service::query()->create([
                'name' => 'CLINEXUS',
                'status' => 'active',
            ])->id,
            'name' => 'CLINEXUS CORE - HISTORICO PRUEBA',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'has_trial' => true,
            'trial_ends_at' => now()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        $fresh = $subscription->fresh();

        $this->assertTrue((bool) $fresh?->has_trial);
        $this->assertFalse($fresh?->isInTrial());
    }
}
