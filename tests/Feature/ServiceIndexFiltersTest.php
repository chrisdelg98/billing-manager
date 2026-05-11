<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_filter_services_by_multiple_fields(): void
    {
        $user = User::factory()->create();

        Service::query()->create([
            'name' => 'CLINEXUS',
            'type' => 'SaaS',
            'provider' => 'Hostinger',
            'status' => 'active',
            'owner_name' => 'Chris',
        ]);

        Service::query()->create([
            'name' => 'LABFLOW',
            'type' => 'API',
            'provider' => 'Cloudflare',
            'status' => 'paused',
            'owner_name' => 'Ana',
        ]);

        Service::query()->create([
            'name' => 'WEBKIT',
            'type' => 'SaaS',
            'provider' => 'Hostinger',
            'status' => 'archived',
            'owner_name' => 'Luis',
        ]);

        $this->actingAs($user)
            ->get(route('servicios.index', [
                'q' => 'CLINEXUS',
                'status' => 'active',
                'type' => 'SaaS',
                'provider' => 'Hostinger',
                'owner_name' => 'Chris',
            ]))
            ->assertOk()
            ->assertSee('CLINEXUS')
            ->assertDontSee('LABFLOW')
            ->assertDontSee('WEBKIT');
    }

    public function test_index_search_can_match_owner_name(): void
    {
        $user = User::factory()->create();

        Service::query()->create([
            'name' => 'SERVICIO-ALFA',
            'status' => 'active',
            'owner_name' => 'Carlos',
        ]);

        Service::query()->create([
            'name' => 'SERVICIO-BETA',
            'status' => 'active',
            'owner_name' => 'Ana Gomez',
        ]);

        $this->actingAs($user)
            ->get(route('servicios.index', ['q' => 'Ana']))
            ->assertOk()
            ->assertSee('SERVICIO-BETA')
            ->assertDontSee('SERVICIO-ALFA');
    }
}
