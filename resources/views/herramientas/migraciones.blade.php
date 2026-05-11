<x-app-layout>
    <x-slot name="header">
        <div class="space-y-1">
            <h2 class="text-xl font-semibold leading-tight text-slate-900">Migraciones</h2>
            <p class="text-sm text-slate-600">Herramienta interna para ejecutar migraciones sin acceso SSH.</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @error('migrate')
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Tabla migrations</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $hasMigrationsTable ? 'Disponible' : 'No existe' }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Archivos detectados</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $migrationFiles->count() }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Migradas</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $appliedMigrations->count() }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-medium uppercase tracking-[0.12em] text-slate-500">Pendientes</p>
                    <p class="mt-2 text-lg font-semibold {{ $pendingMigrations->count() > 0 ? 'text-amber-700' : 'text-emerald-700' }}">{{ $pendingMigrations->count() }}</p>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-4">
                <form method="POST" action="{{ route('herramientas.migraciones.run') }}" onsubmit="return confirm('Se ejecutaran migraciones pendientes. Continuar?')">
                    @csrf
                    <button type="submit" class="ui-btn rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Ejecutar migraciones pendientes
                    </button>
                </form>

                <form method="POST" action="{{ route('herramientas.migraciones.baseline') }}" onsubmit="return confirm('Esto registrara migraciones faltantes en historial sin ejecutar SQL. Continuar?')">
                    @csrf
                    <button type="submit" class="ui-btn rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                        Registrar historial (baseline)
                    </button>
                </form>

                <span class="text-xs text-slate-500">Ultimo batch: {{ $lastBatch }}</span>
            </div>

            <p class="mt-3 text-xs text-slate-500">
                Usa "Registrar historial" cuando la base ya tiene tablas y datos, pero no tenia registros en la tabla migrations.
            </p>
        </div>

        @if ($migrationRun)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <h3 class="text-sm font-semibold uppercase tracking-[0.12em] text-slate-500">Resultado de ejecucion</h3>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Exit code</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $migrationRun['exit_code'] ?? '-' }}</p>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm sm:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-slate-500">Fecha</p>
                        <p class="mt-1 font-semibold text-slate-900">{{ $migrationRun['executed_at'] ?? '-' }}</p>
                    </div>
                </div>

                <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">{{ $migrationRun['output'] ?? 'Sin salida.' }}</pre>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Migraciones pendientes</h3>
                </div>
                <div class="max-h-[420px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Archivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pendingMigrations as $migration)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $migration }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-8 text-center text-sm text-emerald-700">Sin pendientes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="border-b border-slate-200 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Migraciones aplicadas</h3>
                </div>
                <div class="max-h-[420px] overflow-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Migration</th>
                                <th class="px-4 py-3">Batch</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($appliedMigrations->take(100) as $migration)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $migration->migration }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ $migration->batch }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-8 text-center text-sm text-slate-500">No hay registros en migrations.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
