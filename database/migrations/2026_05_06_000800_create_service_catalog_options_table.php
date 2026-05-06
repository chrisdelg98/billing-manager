<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_catalog_options', function (Blueprint $table) {
            $table->id();
            $table->enum('catalog_type', ['service_type', 'provider']);
            $table->string('name', 120);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['catalog_type', 'name']);
            $table->index(['catalog_type', 'is_active']);
        });

        $now = now();

        DB::table('service_catalog_options')->insert([
            ['catalog_type' => 'service_type', 'name' => 'Hosting compartido', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'VPS', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'Servidor dedicado', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'Dominio', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'SSL', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'CDN/WAF', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'Correo empresarial', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'Base de datos gestionada', 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'Backup', 'sort_order' => 90, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'SaaS', 'sort_order' => 100, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'API', 'sort_order' => 110, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'service_type', 'name' => 'CI/CD', 'sort_order' => 120, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            ['catalog_type' => 'provider', 'name' => 'Hostinger', 'sort_order' => 10, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Namecheap', 'sort_order' => 20, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Cloudflare', 'sort_order' => 30, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'DigitalOcean', 'sort_order' => 40, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Vultr', 'sort_order' => 50, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Linode', 'sort_order' => 60, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Hetzner', 'sort_order' => 70, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'OVHcloud', 'sort_order' => 80, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Amazon Web Services', 'sort_order' => 90, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Google Cloud', 'sort_order' => 100, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Microsoft Azure', 'sort_order' => 110, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'GitHub', 'sort_order' => 120, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'GitLab', 'sort_order' => 130, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Vercel', 'sort_order' => 140, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Netlify', 'sort_order' => 150, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'PayPal', 'sort_order' => 160, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['catalog_type' => 'provider', 'name' => 'Stripe', 'sort_order' => 170, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_catalog_options');
    }
};
