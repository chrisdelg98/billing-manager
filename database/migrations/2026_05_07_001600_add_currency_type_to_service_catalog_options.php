<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE service_catalog_options MODIFY COLUMN catalog_type ENUM('service_type','provider','currency','cost_category') NOT NULL");
        }

        $now = now();

        $currencies = [
            ['name' => 'USD', 'sort_order' => 10],
            ['name' => 'EUR', 'sort_order' => 20],
            ['name' => 'DOP', 'sort_order' => 30],
            ['name' => 'MXN', 'sort_order' => 40],
        ];

        foreach ($currencies as $currency) {
            DB::table('service_catalog_options')->updateOrInsert(
                [
                    'catalog_type' => 'currency',
                    'name' => $currency['name'],
                ],
                [
                    'sort_order' => $currency['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('service_catalog_options')
            ->where('catalog_type', 'currency')
            ->delete();

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE service_catalog_options MODIFY COLUMN catalog_type ENUM('service_type','provider','cost_category') NOT NULL");
        }
    }
};
