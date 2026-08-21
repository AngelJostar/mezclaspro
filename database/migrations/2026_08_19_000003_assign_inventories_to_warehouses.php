<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $warehouseColumns = [
            'medicine_batches' => 'laboratory_id',
            'medicine_laboratory_stocks' => 'laboratory_id',
            'diluent_presentations' => 'laboratory_id',
            'medicine_batch_movements' => 'laboratory_id',
            'medicine_stock_movements' => 'medicine_laboratory_stock_id',
            'diluent_stock_movements' => 'laboratory_id',
        ];

        foreach ($warehouseColumns as $tableName => $afterColumn) {
            if (Schema::hasColumn($tableName, 'warehouse_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($afterColumn) {
                $table->foreignId('warehouse_id')
                    ->nullable()
                    ->after($afterColumn)
                    ->constrained('warehouses')
                    ->nullOnDelete();
            });
        }

        $defaultWarehouses = DB::table('warehouses')
            ->selectRaw('laboratory_id, MIN(id) as warehouse_id')
            ->groupBy('laboratory_id')
            ->get();

        foreach ($defaultWarehouses as $defaultWarehouse) {
            DB::table('medicine_batches')
                ->where('laboratory_id', $defaultWarehouse->laboratory_id)
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $defaultWarehouse->warehouse_id]);

            DB::table('medicine_laboratory_stocks')
                ->where('laboratory_id', $defaultWarehouse->laboratory_id)
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $defaultWarehouse->warehouse_id]);

            DB::table('diluent_presentations')
                ->where('laboratory_id', $defaultWarehouse->laboratory_id)
                ->whereNull('warehouse_id')
                ->update(['warehouse_id' => $defaultWarehouse->warehouse_id]);
        }

        if ($defaultWarehouses->count() === 1) {
            $defaultWarehouse = $defaultWarehouses->first();

            DB::table('diluent_presentations')
                ->whereNull('laboratory_id')
                ->whereNull('warehouse_id')
                ->update([
                    'laboratory_id' => $defaultWarehouse->laboratory_id,
                    'warehouse_id' => $defaultWarehouse->warehouse_id,
                ]);
        }

        DB::table('medicine_batch_movements')
            ->orderBy('id')
            ->chunkById(500, function ($movements) {
                foreach ($movements as $movement) {
                    $warehouseId = DB::table('medicine_batches')
                        ->where('id', $movement->medicine_batch_id)
                        ->value('warehouse_id');

                    DB::table('medicine_batch_movements')
                        ->where('id', $movement->id)
                        ->update(['warehouse_id' => $warehouseId]);
                }
            });

        DB::table('medicine_stock_movements')
            ->orderBy('id')
            ->chunkById(500, function ($movements) {
                foreach ($movements as $movement) {
                    $warehouseId = DB::table('medicine_laboratory_stocks')
                        ->where('id', $movement->medicine_laboratory_stock_id)
                        ->value('warehouse_id');

                    DB::table('medicine_stock_movements')
                        ->where('id', $movement->id)
                        ->update(['warehouse_id' => $warehouseId]);
                }
            });

        DB::table('diluent_stock_movements')
            ->orderBy('id')
            ->chunkById(500, function ($movements) {
                foreach ($movements as $movement) {
                    $warehouseId = DB::table('diluent_presentations')
                        ->where('id', $movement->diluent_presentation_id)
                        ->value('warehouse_id');

                    DB::table('diluent_stock_movements')
                        ->where('id', $movement->id)
                        ->update(['warehouse_id' => $warehouseId]);
                }
            });

        if (! $this->indexExists('medicine_batches', 'uq_batch_per_warehouse_presentation')) {
            Schema::table('medicine_batches', function (Blueprint $table) {
                $table->unique(
                    ['warehouse_id', 'medicine_presentation_id', 'lote'],
                    'uq_batch_per_warehouse_presentation'
                );
            });
        }

        if ($this->indexExists('medicine_batches', 'uq_batch_per_lab_presentation')) {
            Schema::table('medicine_batches', fn (Blueprint $table) => $table
                ->dropUnique('uq_batch_per_lab_presentation'));
        }

        if (! $this->indexExists('medicine_batches', 'medicine_batches_warehouse_stock_index')) {
            Schema::table('medicine_batches', fn (Blueprint $table) => $table
                ->index(['warehouse_id', 'stock_actual'], 'medicine_batches_warehouse_stock_index'));
        }

        if (! $this->indexExists('medicine_laboratory_stocks', 'mls_presentation_warehouse_lote_unique')) {
            Schema::table('medicine_laboratory_stocks', function (Blueprint $table) {
                $table->unique(
                    ['nutrition_medicine_presentation_id', 'warehouse_id', 'lote'],
                    'mls_presentation_warehouse_lote_unique'
                );
            });
        }

        if ($this->indexExists('medicine_laboratory_stocks', 'mls_presentation_lab_lote_unique')) {
            Schema::table('medicine_laboratory_stocks', fn (Blueprint $table) => $table
                ->dropUnique('mls_presentation_lab_lote_unique'));
        }

        if (! $this->indexExists('medicine_laboratory_stocks', 'mls_warehouse_stock_index')) {
            Schema::table('medicine_laboratory_stocks', fn (Blueprint $table) => $table
                ->index(['warehouse_id', 'frascos_actuales'], 'mls_warehouse_stock_index'));
        }

        if (! $this->indexExists('diluent_presentations', 'diluent_presentations_warehouse_lot_unique')) {
            Schema::table('diluent_presentations', function (Blueprint $table) {
                $table->unique(
                    ['warehouse_id', 'diluent_id', 'presentacion', 'lote'],
                    'diluent_presentations_warehouse_lot_unique'
                );
            });
        }

        if (! $this->indexExists('diluent_presentations', 'diluent_presentations_warehouse_stock_index')) {
            Schema::table('diluent_presentations', fn (Blueprint $table) => $table
                ->index(['warehouse_id', 'stock_actual'], 'diluent_presentations_warehouse_stock_index'));
        }
    }

    public function down(): void
    {
        Schema::table('diluent_presentations', function (Blueprint $table) {
            $table->dropUnique('diluent_presentations_warehouse_lot_unique');
            $table->dropIndex('diluent_presentations_warehouse_stock_index');
        });

        Schema::table('medicine_laboratory_stocks', function (Blueprint $table) {
            $table->dropUnique('mls_presentation_warehouse_lote_unique');
            $table->dropIndex('mls_warehouse_stock_index');
            $table->unique(
                ['nutrition_medicine_presentation_id', 'laboratory_id', 'lote'],
                'mls_presentation_lab_lote_unique'
            );
        });

        Schema::table('medicine_batches', function (Blueprint $table) {
            $table->dropUnique('uq_batch_per_warehouse_presentation');
            $table->dropIndex('medicine_batches_warehouse_stock_index');
            $table->unique(
                ['laboratory_id', 'medicine_presentation_id', 'lote'],
                'uq_batch_per_lab_presentation'
            );
        });

        foreach (['diluent_stock_movements', 'medicine_stock_movements', 'medicine_batch_movements', 'diluent_presentations', 'medicine_laboratory_stocks', 'medicine_batches'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('warehouse_id');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $existingIndex) => $existingIndex['name'] === strtolower($index));
    }
};
