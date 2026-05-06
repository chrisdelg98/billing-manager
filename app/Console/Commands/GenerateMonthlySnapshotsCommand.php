<?php

namespace App\Console\Commands;

use App\Support\FinanceSnapshotService;
use Illuminate\Console\Command;

class GenerateMonthlySnapshotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:snapshots {period? : Period in format YYYY-MM}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate or update monthly finance snapshots by service';

    public function handle(FinanceSnapshotService $snapshotService): int
    {
        $period = (string) ($this->argument('period') ?: now()->subMonthNoOverflow()->format('Y-m'));

        $rows = $snapshotService->generateForPeriod($period);

        $this->info("Snapshots generated for {$period}. Services processed: {$rows}.");

        return self::SUCCESS;
    }
}
