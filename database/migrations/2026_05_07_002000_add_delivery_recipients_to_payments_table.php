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
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('recipient_name')->nullable()->after('reference');
            $table->string('recipient_email')->nullable()->after('recipient_name');
            $table->string('recipient_whatsapp', 30)->nullable()->after('recipient_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'recipient_name',
                'recipient_email',
                'recipient_whatsapp',
            ]);
        });
    }
};
