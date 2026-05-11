<?php

namespace Tests\Feature;

use App\Models\CostItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Subscription;
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

    public function test_custom_cost_interval_is_prorated_in_finance_projection(): void
    {
        $user = User::factory()->create();

        CostItem::query()->create([
            'name' => 'Web Hosting 4y',
            'category' => 'hosting',
            'cost_type' => 'shared',
            'amount' => 400,
            'currency' => 'USD',
            'billing_cycle' => 'custom',
            'billing_interval_months' => 48,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('finanzas.index', ['period' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('8.33 USD')
            ->assertDontSee('400.00 USD');
    }

    public function test_finance_projection_excludes_active_subscriptions_still_in_trial(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'Plan normal',
            'billing_cycle' => 'monthly',
            'amount' => 20,
            'currency' => 'USD',
            'is_active' => true,
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'Plan en prueba',
            'billing_cycle' => 'monthly',
            'amount' => 30,
            'currency' => 'USD',
            'has_trial' => true,
            'trial_ends_at' => now()->addDays(10)->toDateString(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('finanzas.index', ['period' => now()->format('Y-m')]))
            ->assertOk()
            ->assertSee('20.00 USD')
            ->assertDontSee('50.00 USD');
    }

    public function test_finance_projection_includes_trial_subscription_after_trial_period(): void
    {
        $user = User::factory()->create();

        $service = Service::query()->create([
            'name' => 'Servicio Trial Futuro',
            'status' => 'active',
        ]);

        $trialEnd = now()->startOfMonth()->addDays(10);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'Plan en prueba',
            'billing_cycle' => 'monthly',
            'amount' => 30,
            'currency' => 'USD',
            'has_trial' => true,
            'trial_ends_at' => $trialEnd->toDateString(),
            'is_active' => true,
        ]);

        $nextPeriod = now()->startOfMonth()->addMonthNoOverflow()->format('Y-m');

        $this->actingAs($user)
            ->get(route('finanzas.index', ['period' => $nextPeriod]))
            ->assertOk()
            ->assertSee('30.00 USD');
    }

    public function test_finance_filters_by_service_status_and_search(): void
    {
        $user = User::factory()->create();

        $activeService = Service::query()->create([
            'name' => 'Servicio Activo ROI',
            'status' => 'active',
        ]);

        $inactiveService = Service::query()->create([
            'name' => 'Servicio Inactivo Legacy',
            'status' => 'paused',
        ]);

        Payment::query()->create([
            'service_id' => $activeService->id,
            'paid_at' => now()->startOfMonth()->toDateString(),
            'amount' => 55,
            'currency' => 'USD',
            'method' => 'transfer',
        ]);

        Payment::query()->create([
            'service_id' => $inactiveService->id,
            'paid_at' => now()->startOfMonth()->toDateString(),
            'amount' => 40,
            'currency' => 'USD',
            'method' => 'transfer',
        ]);

        $this->actingAs($user)
            ->get(route('finanzas.index', [
                'period' => now()->format('Y-m'),
                'service_status' => 'active',
                'q' => 'ROI',
            ]))
            ->assertOk()
            ->assertSee('Servicio Activo ROI')
            ->assertDontSee('Servicio Inactivo Legacy');
    }

    public function test_finance_profitability_filter_shows_only_negative_services_and_detail_actions(): void
    {
        $user = User::factory()->create();

        $positiveService = Service::query()->create([
            'name' => 'Servicio Ganador',
            'status' => 'active',
        ]);

        $negativeService = Service::query()->create([
            'name' => 'Servicio Perdida Controlada',
            'status' => 'active',
        ]);

        $costItem = CostItem::query()->create([
            'name' => 'Infra compartida',
            'category' => 'infra',
            'cost_type' => 'shared',
            'amount' => 40,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $costItem->allocations()->createMany([
            [
                'service_id' => $positiveService->id,
                'allocation_mode' => 'equal',
                'weight' => null,
                'is_active' => true,
            ],
            [
                'service_id' => $negativeService->id,
                'allocation_mode' => 'equal',
                'weight' => null,
                'is_active' => true,
            ],
        ]);

        Payment::query()->create([
            'service_id' => $positiveService->id,
            'paid_at' => now()->startOfMonth()->addDay()->toDateString(),
            'amount' => 90,
            'currency' => 'USD',
            'method' => 'transfer',
        ]);

        $this->actingAs($user)
            ->get(route('finanzas.index', [
                'period' => now()->format('Y-m'),
                'profitability' => 'negative',
            ]))
            ->assertOk()
            ->assertSee('Servicio Perdida Controlada')
            ->assertDontSee('Servicio Ganador')
            ->assertSee('clic para ver detalle')
            ->assertSee('Comparativo rapido');
    }
}
