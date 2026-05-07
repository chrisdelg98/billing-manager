<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->date('covered_period_start')->nullable()->after('paid_at');
            $table->index(['subscription_id', 'covered_period_start']);
        });

        DB::table('payments')
            ->select('id', 'paid_at')
            ->whereNotNull('subscription_id')
            ->whereNull('covered_period_start')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('payments')
                        ->where('id', $row->id)
                        ->update([
                            'covered_period_start' => Carbon::parse((string) $row->paid_at)->startOfMonth()->toDateString(),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['subscription_id', 'covered_period_start']);
            $table->dropColumn('covered_period_start');
        });
    }
};
