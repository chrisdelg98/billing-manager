<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->boolean('has_trial')->default(false)->after('notes');
            $table->date('trial_ends_at')->nullable()->after('has_trial');
            $table->index('trial_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['trial_ends_at']);
            $table->dropColumn(['has_trial', 'trial_ends_at']);
        });
    }
};
