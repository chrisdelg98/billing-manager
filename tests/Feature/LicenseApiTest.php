<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LicenseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_license_status_returns_active_with_valid_credentials(): void
    {
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
            'license_api_enabled' => true,
            'license_code' => 'LIC-TEST-ACTIVE',
            'license_secret_hash' => Hash::make('secret-123'),
            'license_secret_hint' => 't123',
            'next_renewal_at' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->withHeaders([
            'X-License-Code' => 'LIC-TEST-ACTIVE',
            'X-License-Secret' => 'secret-123',
        ])->getJson('/api/v1/license/status');

        $response
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('can_access', true)
            ->assertJsonPath('subscription.id', $subscription->id)
            ->assertJsonPath('service.name', 'CLINEXUS');

        $this->assertDatabaseHas('license_access_logs', [
            'subscription_id' => $subscription->id,
            'license_code' => 'LIC-TEST-ACTIVE',
            'result_status' => 'active',
            'http_status' => 200,
        ]);

        $this->assertNotNull($subscription->fresh()?->license_last_used_at);
    }

    public function test_license_status_rejects_invalid_secret(): void
    {
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
            'license_api_enabled' => true,
            'license_code' => 'LIC-TEST-401',
            'license_secret_hash' => Hash::make('secret-123'),
        ]);

        $this->withHeaders([
            'X-License-Code' => 'LIC-TEST-401',
            'X-License-Secret' => 'wrong-secret',
        ])->getJson('/api/v1/license/status')
            ->assertStatus(401)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'invalid_credentials');

        $this->assertDatabaseHas('license_access_logs', [
            'subscription_id' => $subscription->id,
            'license_code' => 'LIC-TEST-401',
            'result_status' => 'invalid_credentials',
            'http_status' => 401,
        ]);
    }

    public function test_license_status_returns_not_found_when_api_disabled(): void
    {
        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'is_active' => true,
            'license_api_enabled' => false,
            'license_code' => 'LIC-TEST-404',
            'license_secret_hash' => Hash::make('secret-123'),
        ]);

        $this->withHeaders([
            'X-License-Code' => 'LIC-TEST-404',
            'X-License-Secret' => 'secret-123',
        ])->getJson('/api/v1/license/status')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_license_status_returns_overdue_when_renewal_expired(): void
    {
        $service = Service::query()->create([
            'name' => 'CLINEXUS',
            'status' => 'active',
        ]);

        Subscription::query()->create([
            'service_id' => $service->id,
            'name' => 'CLINEXUS CORE',
            'billing_cycle' => 'monthly',
            'amount' => 25,
            'currency' => 'USD',
            'is_active' => true,
            'license_api_enabled' => true,
            'license_code' => 'LIC-TEST-OVR',
            'license_secret_hash' => Hash::make('secret-123'),
            'next_renewal_at' => now()->subDay()->toDateString(),
        ]);

        $this->withHeaders([
            'X-License-Code' => 'LIC-TEST-OVR',
            'X-License-Secret' => 'secret-123',
        ])->getJson('/api/v1/license/status')
            ->assertOk()
            ->assertJsonPath('status', 'overdue')
            ->assertJsonPath('can_access', false)
            ->assertJsonPath('reason_code', 'renewal_overdue');
    }
}
