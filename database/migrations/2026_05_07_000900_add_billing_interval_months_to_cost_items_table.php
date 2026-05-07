<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cost_items', function (Blueprint $table): void {
            $table->unsignedSmallInteger('billing_interval_months')->default(1)->after('billing_cycle');
        });

        DB::statement("UPDATE cost_items SET billing_interval_months = CASE billing_cycle WHEN 'yearly' THEN 12 ELSE 1 END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_items', function (Blueprint $table): void {
            $table->dropColumn('billing_interval_months');
        });
    }
};
