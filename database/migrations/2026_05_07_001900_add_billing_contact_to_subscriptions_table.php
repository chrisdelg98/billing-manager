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
            $table->string('billing_contact_name')->nullable()->after('notes');
            $table->string('billing_contact_email')->nullable()->after('billing_contact_name');
            $table->string('billing_contact_whatsapp', 30)->nullable()->after('billing_contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_contact_name',
                'billing_contact_email',
                'billing_contact_whatsapp',
            ]);
        });
    }
};
