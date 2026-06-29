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
            // Modo postpago: permite seguir accediendo durante una ventana de gracia
            // posterior a next_renewal_at, para que el cliente pueda pagar durante el mes.
            $table->boolean('grace_period_enabled')->default(false)->after('next_renewal_at');
            $table->unsignedTinyInteger('grace_months')->default(1)->after('grace_period_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn(['grace_period_enabled', 'grace_months']);
        });
    }
};
