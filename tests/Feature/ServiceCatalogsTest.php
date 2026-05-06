<?php

namespace Tests\Feature;

use App\Models\ServiceCatalogOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_catalogs_section(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('catalogos.servicios.index'))
            ->assertOk();
    }

    public function test_user_can_add_service_type_catalog_option(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('catalogos.servicios.store'), [
            'catalog_type' => ServiceCatalogOption::TYPE_SERVICE,
            'name' => 'Monitorizacion',
            'sort_order' => 220,
            'is_active' => 1,
        ])->assertRedirect(route('catalogos.servicios.index'));

        $this->assertDatabaseHas('service_catalog_options', [
            'catalog_type' => ServiceCatalogOption::TYPE_SERVICE,
            'name' => 'Monitorizacion',
            'sort_order' => 220,
            'is_active' => 1,
        ]);
    }

    public function test_service_form_shows_catalog_suggestions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('servicios.create'))
            ->assertOk()
            ->assertSee('datalist id="service_type_options"', false)
            ->assertSee('datalist id="provider_options"', false)
            ->assertSee('API')
            ->assertSee('Cloudflare');
    }
}
