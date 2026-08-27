<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['medicine_lists', 'nutri_medicine_lists'] as $tableName) {
            $indexName = $tableName === 'medicine_lists'
                ? 'medicine_lists_location_backup_index'
                : 'nutri_lists_location_backup_index';

            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->foreignId('laboratory_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('laboratories')
                    ->nullOnDelete();
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('laboratory_id')
                    ->constrained('warehouses')
                    ->nullOnDelete();
                $table->boolean('backup_enabled')->default(false)->after('warehouse_id');
                $table->foreignId('backup_warehouse_id')
                    ->nullable()
                    ->after('backup_enabled')
                    ->constrained('warehouses')
                    ->nullOnDelete();
                $table->boolean('is_backup')->default(false)->after('backup_warehouse_id');
                $table->foreignId('primary_warehouse_id')
                    ->nullable()
                    ->after('is_backup')
                    ->constrained('warehouses')
                    ->nullOnDelete();
                $table->index(['laboratory_id', 'warehouse_id', 'is_backup'], $indexName);
            });
        }

        $laboratory = DB::table('laboratories')
            ->where('activo', true)
            ->get(['id', 'nombre', 'estado'])
            ->sortBy(function ($laboratory) {
                $label = Str::lower(Str::ascii($laboratory->nombre.' '.$laboratory->estado));

                return str_contains($label, 'cdmx') || str_contains($label, 'ciudad de mexico') ? 0 : 1;
            })
            ->first();

        if (! $laboratory) {
            return;
        }

        $warehouse = DB::table('warehouses')
            ->where('laboratory_id', $laboratory->id)
            ->where('is_active', true)
            ->get(['id', 'name'])
            ->sortBy(function ($warehouse) {
                $name = Str::lower(Str::ascii($warehouse->name));

                return str_contains($name, 'central')
                    || str_contains($name, 'principal')
                    || str_contains($name, 'prodifem')
                    ? 0
                    : 1;
            })
            ->first();

        foreach (['medicine_lists', 'nutri_medicine_lists'] as $tableName) {
            DB::table($tableName)
                ->whereNull('laboratory_id')
                ->update([
                    'laboratory_id' => $laboratory->id,
                    'warehouse_id' => $warehouse?->id,
                ]);
        }
    }

    public function down(): void
    {
        foreach (['medicine_lists', 'nutri_medicine_lists'] as $tableName) {
            $indexName = $tableName === 'medicine_lists'
                ? 'medicine_lists_location_backup_index'
                : 'nutri_lists_location_backup_index';

            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
                $table->dropConstrainedForeignId('primary_warehouse_id');
                $table->dropColumn('is_backup');
                $table->dropConstrainedForeignId('backup_warehouse_id');
                $table->dropColumn('backup_enabled');
                $table->dropConstrainedForeignId('warehouse_id');
                $table->dropConstrainedForeignId('laboratory_id');
            });
        }
    }
};
