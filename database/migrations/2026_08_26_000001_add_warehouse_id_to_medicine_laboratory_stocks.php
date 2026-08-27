<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('medicine_laboratory_stocks', 'warehouse_id')) {
            Schema::table('medicine_laboratory_stocks', function (Blueprint $table) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after('laboratory_id')
                    ->constrained('warehouses')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('warehouses')) {
            return;
        }

        $warehouseIds = DB::table('warehouses')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get(['id', 'laboratory_id'])
            ->groupBy('laboratory_id')
            ->map(fn($warehouses) => $warehouses->first()->id);

        if ($warehouseIds->isEmpty()) {
            return;
        }

        DB::table('medicine_laboratory_stocks')
            ->whereNull('warehouse_id')
            ->orderBy('id')
            ->select(['id', 'laboratory_id'])
            ->chunkById(200, function ($stocks) use ($warehouseIds) {
                foreach ($stocks as $stock) {
                    $warehouseId = $warehouseIds->get($stock->laboratory_id);

                    if ($warehouseId) {
                        DB::table('medicine_laboratory_stocks')
                            ->where('id', $stock->id)
                            ->update(['warehouse_id' => $warehouseId]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('medicine_laboratory_stocks', 'warehouse_id')) {
            return;
        }

        try {
            Schema::table('medicine_laboratory_stocks', function (Blueprint $table) {
                $table->dropForeign(['warehouse_id']);
            });
        } catch (Throwable $e) {
            //
        }

        Schema::table('medicine_laboratory_stocks', function (Blueprint $table) {
            $table->dropColumn('warehouse_id');
        });
    }
};
