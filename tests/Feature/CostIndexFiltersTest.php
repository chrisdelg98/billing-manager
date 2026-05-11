<?php

namespace Tests\Feature;

use App\Models\CostItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_filter_costs_by_multiple_fields(): void
    {
        $user = User::factory()->create();

        CostItem::query()->create([
            'name' => 'COSTO-SHARED-INFRA',
            'category' => 'infra',
            'cost_type' => 'shared',
            'amount' => 80,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'billing_interval_months' => 1,
            'next_renewal_at' => now()->addDays(5)->toDateString(),
            'is_active' => true,
        ]);

        CostItem::query()->create([
            'name' => 'COSTO-DIRECT-LICENSE',
            'category' => 'license',
            'cost_type' => 'direct',
            'amount' => 120,
            'currency' => 'USD',
            'billing_cycle' => 'yearly',
            'billing_interval_months' => 12,
            'next_renewal_at' => now()->addDays(20)->toDateString(),
            'is_active' => false,
        ]);

        CostItem::query()->create([
            'name' => 'COSTO-DIRECT-HOSTING',
            'category' => 'hosting',
            'cost_type' => 'direct',
            'amount' => 40,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'billing_interval_months' => 1,
            'next_renewal_at' => now()->addDays(2)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('costos.index', [
                'q' => 'SHARED',
                'status' => 'active',
                'category' => 'infra',
                'cost_type' => 'shared',
                'billing_cycle' => 'monthly',
            ]))
            ->assertOk()
            ->assertSee('COSTO-SHARED-INFRA')
            ->assertDontSee('COSTO-DIRECT-LICENSE')
            ->assertDontSee('COSTO-DIRECT-HOSTING');
    }

    public function test_index_can_filter_costs_by_renewal_window_and_date_range(): void
    {
        $user = User::factory()->create();

        $today = now()->startOfDay();

        CostItem::query()->create([
            'name' => 'COSTO-OVERDUE',
            'category' => 'infra',
            'cost_type' => 'direct',
            'amount' => 60,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'billing_interval_months' => 1,
            'next_renewal_at' => $today->copy()->subDay()->toDateString(),
            'is_active' => true,
        ]);

        CostItem::query()->create([
            'name' => 'COSTO-NEXT-7',
            'category' => 'infra',
            'cost_type' => 'direct',
            'amount' => 60,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'billing_interval_months' => 1,
            'next_renewal_at' => $today->copy()->addDays(4)->toDateString(),
            'is_active' => true,
        ]);

        CostItem::query()->create([
            'name' => 'COSTO-NEXT-40',
            'category' => 'infra',
            'cost_type' => 'direct',
            'amount' => 60,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'billing_interval_months' => 1,
            'next_renewal_at' => $today->copy()->addDays(40)->toDateString(),
            'is_active' => true,
        ]);

        CostItem::query()->create([
            'name' => 'COSTO-NO-DATE',
            'category' => 'infra',
            'cost_type' => 'direct',
            'amount' => 60,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'billing_interval_months' => 1,
            'next_renewal_at' => null,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('costos.index', ['renewal_window' => 'next_7']))
            ->assertOk()
            ->assertSee('COSTO-NEXT-7')
            ->assertDontSee('COSTO-OVERDUE')
            ->assertDontSee('COSTO-NEXT-40')
            ->assertDontSee('COSTO-NO-DATE');

        $this->actingAs($user)
            ->get(route('costos.index', [
                'next_from' => $today->copy()->addDays(35)->toDateString(),
                'next_to' => $today->copy()->addDays(45)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('COSTO-NEXT-40')
            ->assertDontSee('COSTO-OVERDUE')
            ->assertDontSee('COSTO-NEXT-7')
            ->assertDontSee('COSTO-NO-DATE');
    }
}
