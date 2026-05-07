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
            $table->boolean('license_api_enabled')->default(false)->after('is_active');
            $table->string('license_code', 64)->nullable()->after('license_api_enabled');
            $table->string('license_secret_hash')->nullable()->after('license_code');
            $table->string('license_secret_hint', 12)->nullable()->after('license_secret_hash');
            $table->timestamp('license_key_rotated_at')->nullable()->after('license_secret_hint');
            $table->timestamp('license_key_revoked_at')->nullable()->after('license_key_rotated_at');
            $table->timestamp('license_last_used_at')->nullable()->after('license_key_revoked_at');

            $table->unique('license_code', 'subscriptions_license_code_unique');
            $table->index('license_api_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropUnique('subscriptions_license_code_unique');
            $table->dropIndex(['license_api_enabled']);
            $table->dropColumn([
                'license_api_enabled',
                'license_code',
                'license_secret_hash',
                'license_secret_hint',
                'license_key_rotated_at',
                'license_key_revoked_at',
                'license_last_used_at',
            ]);
        });
    }
};
