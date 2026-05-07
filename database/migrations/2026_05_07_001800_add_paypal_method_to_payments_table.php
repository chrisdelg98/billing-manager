<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE payments MODIFY method ENUM('transfer', 'cash', 'paypal', 'other') NOT NULL DEFAULT 'transfer'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_method_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('transfer', 'cash', 'paypal', 'other'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        DB::table('payments')->where('method', 'paypal')->update(['method' => 'other']);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE payments MODIFY method ENUM('transfer', 'cash', 'other') NOT NULL DEFAULT 'transfer'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_method_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('transfer', 'cash', 'other'))");
        }
    }
};
