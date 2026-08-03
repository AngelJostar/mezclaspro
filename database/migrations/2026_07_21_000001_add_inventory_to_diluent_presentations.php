<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM diluent_presentations'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        if (!in_array('diluent_presentations_diluent_id_idx', $indexes, true)) {
            Schema::table('diluent_presentations', function (Blueprint $table) {
                $table->index('diluent_id', 'diluent_presentations_diluent_id_idx');
            });
        }

        if (in_array('diluent_presentations_diluent_id_presentacion_unique', $indexes, true)) {
            Schema::table('diluent_presentations', function (Blueprint $table) {
                $table->dropUnique('diluent_presentations_diluent_id_presentacion_unique');
            });
        }

        Schema::table('diluent_presentations', function (Blueprint $table) {
            if (!Schema::hasColumn('diluent_presentations', 'laboratory_id')) {
                $table->foreignId('laboratory_id')
                    ->nullable()
                    ->after('diluent_id')
                    ->constrained('laboratories')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('diluent_presentations', 'fecha_ingreso')) {
                $table->date('fecha_ingreso')->nullable()->after('caducidad');
            }

            if (!Schema::hasColumn('diluent_presentations', 'stock_inicial')) {
                $table->decimal('stock_inicial', 12, 2)->default(0)->after('fecha_ingreso');
            }

            if (!Schema::hasColumn('diluent_presentations', 'stock_actual')) {
                $table->decimal('stock_actual', 12, 2)->default(0)->after('stock_inicial');
            }

            if (!Schema::hasColumn('diluent_presentations', 'stock_reservado')) {
                $table->decimal('stock_reservado', 12, 2)->default(0)->after('stock_actual');
            }
        });

        if (!Schema::hasTable('diluent_stock_movements')) {
            Schema::create('diluent_stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('diluent_presentation_id')
                    ->constrained('diluent_presentations')
                    ->cascadeOnDelete();
                $table->foreignId('laboratory_id')
                    ->nullable()
                    ->constrained('laboratories')
                    ->nullOnDelete();
                $table->foreignId('user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->string('movement_type', 50);
                $table->decimal('quantity', 12, 2);
                $table->decimal('stock_actual_before', 12, 2)->default(0);
                $table->decimal('stock_actual_after', 12, 2)->default(0);
                $table->string('reference_type', 100)->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('notes', 500)->nullable();
                $table->timestamps();

                $table->index(['diluent_presentation_id', 'movement_type'], 'dsm_presentation_type_idx');
                $table->index(['reference_type', 'reference_id'], 'dsm_reference_idx');
            });
        }

        if (Schema::hasTable('diluent_stock_movements')) {
            $movementIndexes = collect(DB::select('SHOW INDEX FROM diluent_stock_movements'))
                ->pluck('Key_name')
                ->unique()
                ->values()
                ->all();

            Schema::table('diluent_stock_movements', function (Blueprint $table) use ($movementIndexes) {
                if (!in_array('dsm_presentation_type_idx', $movementIndexes, true)) {
                    $table->index(['diluent_presentation_id', 'movement_type'], 'dsm_presentation_type_idx');
                }

                if (!in_array('dsm_reference_idx', $movementIndexes, true)) {
                    $table->index(['reference_type', 'reference_id'], 'dsm_reference_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('diluent_stock_movements');

        Schema::table('diluent_presentations', function (Blueprint $table) {
            if (Schema::hasColumn('diluent_presentations', 'laboratory_id')) {
                $table->dropConstrainedForeignId('laboratory_id');
            }

            foreach (['fecha_ingreso', 'stock_inicial', 'stock_actual', 'stock_reservado'] as $column) {
                if (Schema::hasColumn('diluent_presentations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
