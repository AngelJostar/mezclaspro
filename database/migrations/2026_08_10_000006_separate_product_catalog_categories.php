<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines_catalog', function (Blueprint $table) {
            $table->string('catalog_category', 24)
                ->default('oncologicos')
                ->after('denominacion')
                ->index();
        });

        Schema::table('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->decimal('conc_min', 12, 4)->nullable()->after('osmolaridad');
            $table->decimal('conc_max', 12, 4)->nullable()->after('conc_min');
            $table->json('diluent_ids')->nullable()->after('conc_max');
            $table->json('administration_route_ids')->nullable()->after('diluent_ids');
        });
    }

    public function down(): void
    {
        Schema::table('nutrition_medicines_catalog', function (Blueprint $table) {
            $table->dropColumn([
                'conc_min',
                'conc_max',
                'diluent_ids',
                'administration_route_ids',
            ]);
        });

        Schema::table('medicines_catalog', function (Blueprint $table) {
            $table->dropIndex(['catalog_category']);
            $table->dropColumn('catalog_category');
        });
    }
};
