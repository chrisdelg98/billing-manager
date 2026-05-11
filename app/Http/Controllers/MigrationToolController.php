<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class MigrationToolController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $snapshot = $this->migrationSnapshot();

        return view('herramientas.migraciones', [
            'migrationFiles' => $snapshot['migrationFiles'],
            'appliedMigrations' => $snapshot['appliedMigrations'],
            'pendingMigrations' => $snapshot['pendingMigrations'],
            'hasMigrationsTable' => $snapshot['hasMigrationsTable'],
            'lastBatch' => $snapshot['lastBatch'],
            'migrationRun' => session('migration_run'),
        ]);
    }

    public function run(): RedirectResponse
    {
        $this->authorizeAdmin();

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $baselineAligned = $this->syncMigrationHistory(markAllMissingAsApplied: false);

            $exitCode = Artisan::call('migrate', [
                '--force' => true,
            ]);

            $output = trim(Artisan::output());

            if ($baselineAligned > 0) {
                $output = "Se registraron {$baselineAligned} migraciones existentes como baseline.\n\n" . ($output !== '' ? $output : 'Sin salida de consola.');
            }

            return redirect()
                ->route('herramientas.migraciones.index')
                ->with('status', $exitCode === 0
                    ? 'Migraciones ejecutadas correctamente.'
                    : 'Se ejecuto migrate, pero se reportaron incidencias. Revisa el detalle.')
                ->with('migration_run', [
                    'exit_code' => $exitCode,
                    'output' => $output !== '' ? $output : 'Sin salida de consola.',
                    'executed_at' => now()->toDateTimeString(),
                    'baseline_aligned' => $baselineAligned,
                ]);
        } catch (Throwable $exception) {
            $errorMessage = $exception->getMessage();

            if (str_contains($errorMessage, 'Base table or view already exists')) {
                $errorMessage .= ' Esta base ya tenia tablas. Usa "Registrar historial" para sincronizar migraciones sin recrear tablas.';
            }

            return redirect()
                ->route('herramientas.migraciones.index')
                ->withErrors([
                    'migrate' => $errorMessage,
                ]);
        }
    }

    public function baseline(): RedirectResponse
    {
        $this->authorizeAdmin();

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $registered = $this->syncMigrationHistory(markAllMissingAsApplied: true);

            return redirect()
                ->route('herramientas.migraciones.index')
                ->with('status', $registered > 0
                    ? "Se registraron {$registered} migraciones en historial sin ejecutar SQL."
                    : 'No habia migraciones faltantes por registrar en historial.');
        } catch (Throwable $exception) {
            return redirect()
                ->route('herramientas.migraciones.index')
                ->withErrors([
                    'migrate' => $exception->getMessage(),
                ]);
        }
    }

    private function authorizeAdmin(): void
    {
        $user = request()->user();

        abort_unless($user && $user->role === 'admin', 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function migrationSnapshot(): array
    {
        $migrationFiles = $this->migrationFileNames();

        $hasMigrationsTable = Schema::hasTable('migrations');

        $appliedMigrations = $hasMigrationsTable
            ? DB::table('migrations')
                ->select(['id', 'migration', 'batch'])
                ->orderByDesc('id')
                ->get()
            : collect();

        $appliedNames = $hasMigrationsTable
            ? $appliedMigrations->pluck('migration')
            : collect();

        $pendingMigrations = $migrationFiles
            ->reject(fn (string $migration) => $appliedNames->contains($migration))
            ->values();

        return [
            'migrationFiles' => $migrationFiles,
            'appliedMigrations' => $appliedMigrations,
            'pendingMigrations' => $pendingMigrations,
            'hasMigrationsTable' => $hasMigrationsTable,
            'lastBatch' => $hasMigrationsTable ? (int) DB::table('migrations')->max('batch') : 0,
        ];
    }

    private function syncMigrationHistory(bool $markAllMissingAsApplied): int
    {
        if (! Schema::hasTable('migrations')) {
            Artisan::call('migrate:install');
        }

        if (! Schema::hasTable('migrations')) {
            return 0;
        }

        $existingTables = collect(Schema::getTableListing())
            ->map(fn ($table) => strtolower((string) $table))
            ->values();

        $appliedNames = DB::table('migrations')
            ->pluck('migration')
            ->map(fn ($migration) => (string) $migration);

        $baselineMigrations = $this->migrationFileNames()
            ->reject(fn (string $migration) => $appliedNames->contains($migration))
            ->filter(fn (string $migration) => $this->migrationCanBeMarkedAsApplied($migration, $existingTables, $markAllMissingAsApplied))
            ->values();

        if ($baselineMigrations->isEmpty()) {
            return 0;
        }

        $batch = (int) DB::table('migrations')->max('batch');

        if ($batch < 1) {
            $batch = 1;
        }

        return DB::table('migrations')->insertOrIgnore(
            $baselineMigrations
                ->map(fn (string $migration) => [
                    'migration' => $migration,
                    'batch' => $batch,
                ])
                ->all()
        );
    }

    private function migrationCanBeMarkedAsApplied(string $migration, Collection $existingTables, bool $markAllMissingAsApplied): bool
    {
        $requiredTables = match ($migration) {
            '0001_01_01_000000_create_users_table' => ['users', 'password_reset_tokens', 'sessions'],
            '0001_01_01_000001_create_cache_table' => ['cache', 'cache_locks'],
            '0001_01_01_000002_create_jobs_table' => ['jobs', 'job_batches', 'failed_jobs'],
            default => null,
        };

        if ($requiredTables !== null) {
            $requiredTables = collect($requiredTables)
                ->map(fn (string $table) => strtolower($table))
                ->values();

            $existingRequiredTables = $requiredTables
                ->filter(fn (string $table) => $this->tableExistsInListing($existingTables, $table))
                ->values();

            if ($existingRequiredTables->count() === $requiredTables->count()) {
                return true;
            }

            // Legacy DBs can have the primary table (e.g. users) but miss helper tables.
            // Marking as applied avoids a hard fail and allows later migrations to continue.
            if ($this->tableExistsInListing($existingTables, (string) $requiredTables->first())) {
                return true;
            }

            return false;
        }

        if (preg_match('/create_(.+)_table$/', $migration, $matches) === 1) {
            return $this->tableExistsInListing($existingTables, strtolower($matches[1]));
        }

        if (preg_match('/create_(.+)$/', $migration, $matches) === 1) {
            return $this->tableExistsInListing($existingTables, strtolower($matches[1]));
        }

        if (preg_match('/add_.*_to_(.+)_table$/', $migration, $matches) === 1) {
            return $markAllMissingAsApplied && $this->tableExistsInListing($existingTables, strtolower($matches[1]));
        }

        return $markAllMissingAsApplied;
    }

    private function tableExistsInListing(Collection $existingTables, string $table): bool
    {
        $normalizedTable = strtolower($table);

        return $existingTables->contains(function ($existingTable) use ($normalizedTable): bool {
            $candidate = strtolower((string) $existingTable);

            return $candidate === $normalizedTable
                || str_ends_with($candidate, '.'.$normalizedTable)
                || str_ends_with($candidate, '_'.$normalizedTable);
        });
    }

    private function migrationFileNames(): Collection
    {
        return collect(File::files(database_path('migrations')))
            ->map(fn ($file) => pathinfo($file->getFilename(), PATHINFO_FILENAME))
            ->sort()
            ->values();
    }
}
