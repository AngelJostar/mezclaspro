<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'hospitals' => 'HOSP',
            'nutrition_medicines_catalog' => 'NPT-CAT',
            'nutrition_medicine_presentations' => 'NPT-PRES',
            'medicines_catalog' => 'ONC-CAT',
            'medicine_presentations' => 'ONC-PRES',
        ];

        foreach ($tables as $table => $prefix) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('external_code', 80)->nullable()->after('id');
            });

            DB::table($table)
                ->select('id')
                ->orderBy('id')
                ->eachById(function (object $row) use ($table, $prefix): void {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['external_code' => $prefix.'-'.str_pad((string) $row->id, 6, '0', STR_PAD_LEFT)]);
                });

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->unique('external_code', $table.'_external_code_unique');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'hospitals',
            'nutrition_medicines_catalog',
            'nutrition_medicine_presentations',
            'medicines_catalog',
            'medicine_presentations',
        ] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique($table.'_external_code_unique');
                $blueprint->dropColumn('external_code');
            });
        }
    }
};
