<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_migration_tool_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('herramientas.migraciones.index'))
            ->assertOk()
            ->assertSee('Migraciones')
            ->assertSee('Pendientes');
    }

    public function test_non_admin_user_cannot_open_or_execute_migration_tool(): void
    {
        $operator = User::factory()->create([
            'role' => 'operador',
        ]);

        $this->actingAs($operator)
            ->get(route('herramientas.migraciones.index'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->post(route('herramientas.migraciones.run'))
            ->assertForbidden();

        $this->actingAs($operator)
            ->post(route('herramientas.migraciones.baseline'))
            ->assertForbidden();
    }

    public function test_admin_can_trigger_migrations_from_web_tool(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("Migrating: 2026_05_06_000800_create_service_catalog_options_table\nMigrated");

        $this->actingAs($admin)
            ->post(route('herramientas.migraciones.run'))
            ->assertRedirect(route('herramientas.migraciones.index'))
            ->assertSessionHas('status', 'Migraciones ejecutadas correctamente.')
            ->assertSessionHas('migration_run', function (array $payload): bool {
                return ($payload['exit_code'] ?? null) === 0
                    && isset($payload['output'])
                    && str_contains($payload['output'], 'Migrating')
                    && isset($payload['executed_at']);
            });
    }

    public function test_admin_can_register_baseline_history_without_running_migrations(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        Schema::dropIfExists('migrations');

        $this->actingAs($admin)
            ->post(route('herramientas.migraciones.baseline'))
            ->assertRedirect(route('herramientas.migraciones.index'))
            ->assertSessionHas('status');

        $this->assertTrue(Schema::hasTable('migrations'));
        $this->assertGreaterThan(0, (int) DB::table('migrations')->count());
    }

    public function test_run_auto_baselines_missing_create_migration_before_executing_migrate(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $createMigration = DB::table('migrations')
            ->where('migration', 'like', '%create%')
            ->orderBy('id')
            ->value('migration');

        $this->assertNotNull($createMigration);

        DB::table('migrations')
            ->where('migration', $createMigration)
            ->delete();

        $this->assertDatabaseMissing('migrations', [
            'migration' => $createMigration,
        ]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('migrate', ['--force' => true])
            ->andReturn(0);

        Artisan::shouldReceive('output')
            ->once()
            ->andReturn('Migrated');

        $this->actingAs($admin)
            ->post(route('herramientas.migraciones.run'))
            ->assertRedirect(route('herramientas.migraciones.index'))
            ->assertSessionHas('migration_run');

        $this->assertDatabaseHas('migrations', [
            'migration' => $createMigration,
        ]);
    }
}
