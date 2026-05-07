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
            ->assertOk()
            ->assertSee('Monedas')
            ->assertSee('USD');
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

    public function test_user_can_add_currency_catalog_option(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('catalogos.servicios.store'), [
            'catalog_type' => ServiceCatalogOption::TYPE_CURRENCY,
            'name' => 'cop',
            'sort_order' => 230,
            'is_active' => 1,
        ])->assertRedirect(route('catalogos.servicios.index'));

        $this->assertDatabaseHas('service_catalog_options', [
            'catalog_type' => ServiceCatalogOption::TYPE_CURRENCY,
            'name' => 'COP',
            'sort_order' => 230,
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

    public function test_user_can_reorder_catalog_items(): void
    {
        $user = User::factory()->create();

        $itemA = ServiceCatalogOption::query()->create([
            'catalog_type' => ServiceCatalogOption::TYPE_SERVICE,
            'name' => 'Tipo Zeta',
            'sort_order' => 500,
            'is_active' => true,
        ]);

        $itemB = ServiceCatalogOption::query()->create([
            'catalog_type' => ServiceCatalogOption::TYPE_SERVICE,
            'name' => 'Tipo Alfa',
            'sort_order' => 510,
            'is_active' => true,
        ]);

        $orderedIds = ServiceCatalogOption::query()
            ->where('catalog_type', ServiceCatalogOption::TYPE_SERVICE)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('id')
            ->all();

        $indexA = array_search($itemA->id, $orderedIds, true);
        $indexB = array_search($itemB->id, $orderedIds, true);

        [$orderedIds[$indexA], $orderedIds[$indexB]] = [$orderedIds[$indexB], $orderedIds[$indexA]];

        $this->actingAs($user)
            ->postJson(route('catalogos.servicios.reorder'), [
                'catalog_type' => ServiceCatalogOption::TYPE_SERVICE,
                'ordered_ids' => $orderedIds,
            ])
            ->assertOk();

        $expectedOrderB = (array_search($itemB->id, $orderedIds, true) + 1) * 10;
        $expectedOrderA = (array_search($itemA->id, $orderedIds, true) + 1) * 10;

        $this->assertDatabaseHas('service_catalog_options', [
            'id' => $itemB->id,
            'sort_order' => $expectedOrderB,
        ]);

        $this->assertDatabaseHas('service_catalog_options', [
            'id' => $itemA->id,
            'sort_order' => $expectedOrderA,
        ]);
    }
}
