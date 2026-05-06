<?php

namespace Tests\Feature;

use App\Models\CostItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSnapshotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_configure_shared_cost_allocations(): void
    {
        $user = User::factory()->create();

        $serviceA = Service::query()->create([
            'name' => 'Servicio A',
            'status' => 'active',
        ]);

        $serviceB = Service::query()->create([
            'name' => 'Servicio B',
            'status' => 'active',
        ]);

        $costItem = CostItem::query()->create([
            'name' => 'Licencia compartida',
            'category' => 'license',
            'cost_type' => 'shared',
            'amount' => 40,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $this->actingAs($user)->put(route('costos.asignaciones.update', $costItem), [
            'allocation_mode' => 'weight',
            'service_ids' => [$serviceA->id, $serviceB->id],
            'weights' => [
                $serviceA->id => 3,
                $serviceB->id => 1,
            ],
        ])->assertRedirect(route('costos.index'));

        $this->assertDatabaseHas('cost_allocations', [
            'cost_item_id' => $costItem->id,
            'service_id' => $serviceA->id,
            'allocation_mode' => 'weight',
            'weight' => '3.0000',
            'is_active' => 1,
        ]);

        $this->assertDatabaseHas('cost_allocations', [
            'cost_item_id' => $costItem->id,
            'service_id' => $serviceB->id,
            'allocation_mode' => 'weight',
            'weight' => '1.0000',
            'is_active' => 1,
        ]);
    }

    public function test_snapshot_command_generates_monthly_net_margin_history(): void
    {
        $serviceA = Service::query()->create([
            'name' => 'Servicio A',
            'status' => 'active',
        ]);

        $serviceB = Service::query()->create([
            'name' => 'Servicio B',
            'status' => 'active',
        ]);

        $costItem = CostItem::query()->create([
            'name' => 'Servidor compartido',
            'category' => 'infra',
            'cost_type' => 'shared',
            'amount' => 40,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $costItem->allocations()->createMany([
            [
                'service_id' => $serviceA->id,
                'allocation_mode' => 'weight',
                'weight' => 3,
                'is_active' => true,
            ],
            [
                'service_id' => $serviceB->id,
                'allocation_mode' => 'weight',
                'weight' => 1,
                'is_active' => true,
            ],
        ]);

        Payment::query()->create([
            'service_id' => $serviceA->id,
            'paid_at' => now()->startOfMonth()->addDays(2)->toDateString(),
            'amount' => 100,
            'currency' => 'USD',
            'method' => 'transfer',
        ]);

        $period = now()->format('Y-m');

        $this->artisan('finance:snapshots', ['period' => $period])
            ->assertSuccessful();

        $this->assertDatabaseHas('monthly_snapshots', [
            'period' => $period,
            'service_id' => $serviceA->id,
            'income_total' => '100.00',
            'shared_cost_total' => '30.00',
            'net_margin' => '70.00',
        ]);

        $this->assertDatabaseHas('monthly_snapshots', [
            'period' => $period,
            'service_id' => $serviceB->id,
            'income_total' => '0.00',
            'shared_cost_total' => '10.00',
            'net_margin' => '-10.00',
        ]);
    }
}
