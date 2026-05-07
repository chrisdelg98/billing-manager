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
            ->assertSee('Descuento (%)');
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
}
