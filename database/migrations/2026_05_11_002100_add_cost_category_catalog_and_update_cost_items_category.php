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
            DB::statement("ALTER TABLE cost_items MODIFY category VARCHAR(120) NOT NULL DEFAULT 'Otro'");
        }

        $now = now();

        $costCategories = [
            ['name' => 'Hosting', 'sort_order' => 10],
            ['name' => 'Licencia', 'sort_order' => 20],
            ['name' => 'Infraestructura', 'sort_order' => 30],
            ['name' => 'Otro', 'sort_order' => 40],
        ];

        foreach ($costCategories as $category) {
            DB::table('service_catalog_options')->updateOrInsert(
                [
                    'catalog_type' => 'cost_category',
                    'name' => $category['name'],
                ],
                [
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('cost_items')->where('category', 'hosting')->update(['category' => 'Hosting']);
        DB::table('cost_items')->where('category', 'license')->update(['category' => 'Licencia']);
        DB::table('cost_items')->where('category', 'infra')->update(['category' => 'Infraestructura']);
        DB::table('cost_items')->where('category', 'other')->update(['category' => 'Otro']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cost_items')
            ->where('category', 'Hosting')
            ->update(['category' => 'hosting']);

        DB::table('cost_items')
            ->where('category', 'Licencia')
            ->update(['category' => 'license']);

        DB::table('cost_items')
            ->where('category', 'Infraestructura')
            ->update(['category' => 'infra']);

        DB::table('cost_items')
            ->where('category', 'Otro')
            ->update(['category' => 'other']);

        DB::table('cost_items')->whereNotIn('category', ['hosting', 'license', 'infra', 'other'])->update(['category' => 'other']);

        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE cost_items MODIFY category ENUM('hosting','license','infra','other') NOT NULL DEFAULT 'other'");
        }

        DB::table('service_catalog_options')
            ->where('catalog_type', 'cost_category')
            ->delete();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE service_catalog_options MODIFY COLUMN catalog_type ENUM('service_type','provider','currency') NOT NULL");
        }
    }
};
