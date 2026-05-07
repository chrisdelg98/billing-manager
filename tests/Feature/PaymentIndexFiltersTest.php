<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_filter_payments_by_multiple_fields(): void
    {
        $user = User::factory()->create();

        $serviceA = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $serviceB = Service::query()->create([
            'name' => 'LABFLOW',
            'status' => 'active',
        ]);

        Payment::query()->create([
            'service_id' => $serviceA->id,
            'status' => 'confirmed',
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 200,
            'currency' => 'USD',
            'method' => 'paypal',
            'reference' => 'PAYPAL-OK-001',
        ]);

        Payment::query()->create([
            'service_id' => $serviceA->id,
            'status' => 'pending',
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 200,
            'currency' => 'USD',
            'method' => 'other',
            'reference' => 'PENDING-001',
        ]);

        Payment::query()->create([
            'service_id' => $serviceB->id,
            'status' => 'confirmed',
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 200,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'TRANSFER-001',
        ]);

        $this->actingAs($user)
            ->get(route('pagos.index', [
                'service_id' => $serviceA->id,
                'status' => 'confirmed',
                'method' => 'paypal',
                'q' => 'PAYPAL-OK',
            ]))
            ->assertOk()
            ->assertSee('PAYPAL-OK-001')
            ->assertDontSee('PENDING-001')
            ->assertDontSee('TRANSFER-001');
    }

    public function test_index_can_filter_payments_by_subscription_scope(): void
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
            'amount' => 120,
            'currency' => 'USD',
            'next_renewal_at' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        Payment::query()->create([
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'status' => 'confirmed',
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 120,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'WITH-SUB-001',
        ]);

        Payment::query()->create([
            'service_id' => $service->id,
            'status' => 'confirmed',
            'paid_at' => now()->toDateString(),
            'covered_period_start' => now()->startOfMonth()->toDateString(),
            'amount' => 50,
            'currency' => 'USD',
            'method' => 'cash',
            'reference' => 'WITHOUT-SUB-001',
        ]);

        $this->actingAs($user)
            ->get(route('pagos.index', ['subscription_scope' => 'with_subscription']))
            ->assertOk()
            ->assertSee('WITH-SUB-001')
            ->assertDontSee('WITHOUT-SUB-001');

        $this->actingAs($user)
            ->get(route('pagos.index', ['subscription_scope' => 'without_subscription']))
            ->assertOk()
            ->assertSee('WITHOUT-SUB-001')
            ->assertDontSee('WITH-SUB-001');
    }

    public function test_index_can_filter_payments_by_window_and_date_range(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        $today = now()->startOfDay();

        Payment::query()->create([
            'service_id' => $service->id,
            'status' => 'confirmed',
            'paid_at' => $today->toDateString(),
            'covered_period_start' => $today->copy()->startOfMonth()->toDateString(),
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'DATE-TODAY',
        ]);

        Payment::query()->create([
            'service_id' => $service->id,
            'status' => 'confirmed',
            'paid_at' => $today->copy()->subDays(10)->toDateString(),
            'covered_period_start' => $today->copy()->subDays(10)->startOfMonth()->toDateString(),
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'DATE-10-DAYS',
        ]);

        Payment::query()->create([
            'service_id' => $service->id,
            'status' => 'confirmed',
            'paid_at' => $today->copy()->subDays(45)->toDateString(),
            'covered_period_start' => $today->copy()->subDays(45)->startOfMonth()->toDateString(),
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'DATE-45-DAYS',
        ]);

        $this->actingAs($user)
            ->get(route('pagos.index', ['paid_window' => 'last_7']))
            ->assertOk()
            ->assertSee('DATE-TODAY')
            ->assertDontSee('DATE-10-DAYS')
            ->assertDontSee('DATE-45-DAYS');

        $this->actingAs($user)
            ->get(route('pagos.index', [
                'paid_from' => $today->copy()->subDays(11)->toDateString(),
                'paid_to' => $today->copy()->subDays(9)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('DATE-10-DAYS')
            ->assertDontSee('DATE-TODAY')
            ->assertDontSee('DATE-45-DAYS');
    }
}
