<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_orders_by_closest_renewal_by_default(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'SUB-LATER',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => now()->addDays(20)->toDateString(),
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'SUB-SOON',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => now()->addDays(3)->toDateString(),
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'SUB-OVERDUE',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => now()->subDays(1)->toDateString(),
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'SUB-NODATE',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'next_renewal_at' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('suscripciones.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'SUB-OVERDUE',
                'SUB-SOON',
                'SUB-LATER',
                'SUB-NODATE',
            ]);
    }

    public function test_index_can_filter_by_risk_cycle_and_text(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'KRISSIA-YEARLY-WARNING',
            'billing_cycle' => 'yearly',
            'amount' => 300,
            'currency' => 'USD',
            'next_renewal_at' => now()->addDays(5)->toDateString(),
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'KRISSIA-MONTHLY-DANGER',
            'billing_cycle' => 'monthly',
            'amount' => 50,
            'currency' => 'USD',
            'next_renewal_at' => now()->addDays(1)->toDateString(),
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'OTRO-YEARLY-SAFE',
            'billing_cycle' => 'yearly',
            'amount' => 120,
            'currency' => 'USD',
            'next_renewal_at' => now()->addDays(25)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('suscripciones.index', [
                'q' => 'KRISSIA',
                'billing_cycle' => 'yearly',
                'renewal_risk' => 'warning',
            ]))
            ->assertOk()
            ->assertSee('KRISSIA-YEARLY-WARNING')
            ->assertDontSee('KRISSIA-MONTHLY-DANGER')
            ->assertDontSee('OTRO-YEARLY-SAFE');
    }
}
