<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLicenseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.license_api.cipher_secret', 'test-license-cipher-secret');
    }

    public function test_user_can_generate_secret_from_license_management_section(): void
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
            'license_api_enabled' => true,
        ]);

        $this->actingAs($user)
            ->from(route('suscripciones.edit', $subscription))
            ->post(route('suscripciones.licencia.rotate', $subscription))
            ->assertRedirect(route('suscripciones.edit', $subscription))
            ->assertSessionHas('license_plain_secret');

        $fresh = $subscription->fresh();

        $this->assertNotNull($fresh?->license_code);
        $this->assertNotNull($fresh?->license_secret_hash);
        $this->assertNotNull($fresh?->license_secret_encrypted);
        $this->assertNotNull($fresh?->license_secret_hint);
        $this->assertNotNull($fresh?->license_key_rotated_at);
        $this->assertNull($fresh?->license_key_revoked_at);
    }

    public function test_user_can_reveal_secret_again_when_cipher_secret_is_configured(): void
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
            'license_api_enabled' => true,
        ]);

        $rotateResponse = $this->actingAs($user)
            ->from(route('suscripciones.edit', $subscription))
            ->post(route('suscripciones.licencia.rotate', $subscription));

        $rotateResponse->assertRedirect(route('suscripciones.edit', $subscription));
        $generatedSecret = (string) $rotateResponse->getSession()->get('license_plain_secret');

        $this->assertNotSame('', $generatedSecret);

        $this->actingAs($user)
            ->from(route('suscripciones.edit', $subscription))
            ->post(route('suscripciones.licencia.reveal', $subscription))
            ->assertRedirect(route('suscripciones.edit', $subscription))
            ->assertSessionHas('license_plain_secret', $generatedSecret);
    }

    public function test_user_cannot_generate_secret_when_license_api_is_disabled(): void
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
            'license_api_enabled' => false,
        ]);

        $this->actingAs($user)
            ->from(route('suscripciones.edit', $subscription))
            ->post(route('suscripciones.licencia.rotate', $subscription))
            ->assertRedirect(route('suscripciones.edit', $subscription))
            ->assertSessionHasErrors('license_api');

        $this->assertNull($subscription->fresh()?->license_code);
    }

    public function test_user_can_revoke_and_reactivate_license_api_access(): void
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
            'license_api_enabled' => true,
            'license_code' => 'LIC-REV-001',
            'license_secret_hash' => 'hash',
        ]);

        $this->actingAs($user)
            ->post(route('suscripciones.licencia.revoke', $subscription))
            ->assertRedirect();

        $this->assertNotNull($subscription->fresh()?->license_key_revoked_at);

        $this->actingAs($user)
            ->post(route('suscripciones.licencia.reactivate', $subscription))
            ->assertRedirect();

        $this->assertNull($subscription->fresh()?->license_key_revoked_at);
    }
}
