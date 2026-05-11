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
        Schema::table('cost_items', function (Blueprint $table): void {
            $table->enum('target_scope', ['general', 'service', 'subscription'])
                ->default('general')
                ->after('cost_type');

            $table->foreignId('service_id')
                ->nullable()
                ->after('target_scope')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->after('service_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['target_scope', 'cost_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_items', function (Blueprint $table): void {
            $table->dropIndex(['target_scope', 'cost_type']);
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn('target_scope');
        });
    }
};
