<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function indexExists(string $table, string $index): bool
    {
        return !empty(DB::select(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [$table, $index]
        ));
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!$this->indexExists('nutrition_medicine_presentations', 'nmp_catalog_idx')) {
            Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
                $table->index('nutrition_medicine_catalog_id', 'nmp_catalog_idx');
            });
        }

        $this->dropIndexIfExists('nutrition_medicine_presentations', 'nutri_med_present_unique_catalog_comercial');
        $this->dropIndexIfExists('nutrition_medicine_presentations', 'uq_nutri_catalog_denominacion_comercial');

        if ($this->indexExists('nutrition_medicine_presentations', 'uq_nutri_catalog_comercial_presentacion')) {
            return;
        }

        Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
            $table->unique(
                ['nutrition_medicine_catalog_id', 'denominacion_comercial', 'presentacion'],
                'uq_nutri_catalog_comercial_presentacion'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('nutrition_medicine_presentations', 'uq_nutri_catalog_comercial_presentacion');

        Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
            $table->unique(
                ['nutrition_medicine_catalog_id', 'denominacion_comercial'],
                'nutri_med_present_unique_catalog_comercial'
            );
        });
    }
};
