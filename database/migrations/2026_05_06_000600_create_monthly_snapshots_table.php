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
        Schema::create('monthly_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('period', 7);
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->decimal('income_total', 12, 2)->default(0);
            $table->decimal('direct_cost_total', 12, 2)->default(0);
            $table->decimal('shared_cost_total', 12, 2)->default(0);
            $table->decimal('net_margin', 12, 2)->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['period', 'service_id']);
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_snapshots');
    }
};
