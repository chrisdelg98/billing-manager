<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_all_section_indexes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('servicios.index'))->assertOk();
        $this->actingAs($user)->get(route('suscripciones.index'))->assertOk();
        $this->actingAs($user)->get(route('pagos.index'))->assertOk();
        $this->actingAs($user)->get(route('costos.index'))->assertOk();
        $this->actingAs($user)->get(route('finanzas.index'))->assertOk();
    }

    public function test_user_can_create_service_subscription_payment_and_cost(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('servicios.store'), [
            'name' => 'Cliente Demo',
            'type' => 'Hosting',
            'provider' => 'Cloud Provider',
            'status' => 'active',
            'owner_name' => 'Owner Test',
            'notes' => 'Notas base',
        ])->assertRedirect(route('servicios.index'));

        $service = Service::query()->firstOrFail();

        $this->actingAs($user)->post(route('suscripciones.store'), [
            'service_id' => $service->id,
            'name' => 'Plan mensual',
            'billing_cycle' => 'monthly',
            'amount' => 50,
            'currency' => 'USD',
            'next_renewal_at' => now()->addMonth()->toDateString(),
            'notes' => 'Mes de prueba con descuento del 20%',
            'is_active' => 1,
        ])->assertRedirect(route('suscripciones.index'));

        $subscription = Subscription::query()->firstOrFail();

        $this->actingAs($user)->post(route('pagos.store'), [
            'service_id' => $service->id,
            'subscription_id' => $subscription->id,
            'paid_at' => now()->toDateString(),
            'covered_period' => now()->format('Y-m'),
            'amount' => 50,
            'currency' => 'USD',
            'method' => 'transfer',
            'reference' => 'TRX-001',
            'notes' => 'Pago inicial',
        ])->assertRedirect(route('pagos.index'));

        $this->actingAs($user)->post(route('costos.store'), [
            'name' => 'Servidor base',
            'category' => 'infra',
            'cost_type' => 'direct',
            'amount' => 20,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'next_renewal_at' => now()->addMonth()->toDateString(),
            'is_active' => 1,
        ])->assertRedirect(route('costos.index'));

        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseHas('subscriptions', [
            'name' => 'Plan mensual',
            'notes' => 'Mes de prueba con descuento del 20%',
        ]);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('cost_items', 1);
        $this->assertDatabaseCount('audit_logs', 4);
    }
}
