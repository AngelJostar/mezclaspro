<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
            if (!Schema::hasColumn('nutrition_medicine_presentations', 'stability_hours')) {
                $table->unsignedSmallInteger('stability_hours')->nullable()->after('presentacion_ml');
            }
        });

        Schema::table('medicine_batches', function (Blueprint $table) {
            if (!Schema::hasColumn('medicine_batches', 'stock_ml_inicial')) {
                $table->decimal('stock_ml_inicial', 12, 4)->default(0)->after('stock_reservado');
            }

            if (!Schema::hasColumn('medicine_batches', 'stock_ml_actual')) {
                $table->decimal('stock_ml_actual', 12, 4)->default(0)->after('stock_ml_inicial');
            }
        });

        Schema::table('medicine_batch_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('medicine_batch_movements', 'quantity_ml')) {
                $table->decimal('quantity_ml', 12, 4)->nullable()->after('quantity');
            }

            if (!Schema::hasColumn('medicine_batch_movements', 'stock_ml_before')) {
                $table->decimal('stock_ml_before', 12, 4)->nullable()->after('stock_actual_after');
            }

            if (!Schema::hasColumn('medicine_batch_movements', 'stock_ml_after')) {
                $table->decimal('stock_ml_after', 12, 4)->nullable()->after('stock_ml_before');
            }
        });

        Schema::table('mezcla_medicamento_presentaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('mezcla_medicamento_presentaciones', 'unidades_abiertas')) {
                $table->unsignedInteger('unidades_abiertas')->default(0)->after('unidades_usadas');
            }

            if (!Schema::hasColumn('mezcla_medicamento_presentaciones', 'volumen_usado_ml')) {
                $table->decimal('volumen_usado_ml', 12, 4)->default(0)->after('unidades_abiertas');
            }
        });

        if (
            DB::getDriverName() === 'mysql'
            && Schema::hasColumn('medicine_batches', 'stock_ml_inicial')
            && Schema::hasColumn('medicine_batches', 'stock_ml_actual')
        ) {
            DB::statement("
                UPDATE medicine_batches mb
                INNER JOIN medicine_presentations mp ON mp.id = mb.medicine_presentation_id
                SET
                    mb.stock_ml_inicial = CASE
                        WHEN COALESCE(mb.stock_ml_inicial, 0) = 0 THEN COALESCE(mb.stock_inicial, 0) * COALESCE(mp.volumen_diluyente, 0)
                        ELSE mb.stock_ml_inicial
                    END,
                    mb.stock_ml_actual = CASE
                        WHEN COALESCE(mb.stock_ml_actual, 0) = 0 THEN COALESCE(mb.stock_actual, 0) * COALESCE(mp.volumen_diluyente, 0)
                        ELSE mb.stock_ml_actual
                    END
            ");
        }
    }

    public function down(): void
    {
        Schema::table('mezcla_medicamento_presentaciones', function (Blueprint $table) {
            if (Schema::hasColumn('mezcla_medicamento_presentaciones', 'volumen_usado_ml')) {
                $table->dropColumn('volumen_usado_ml');
            }

            if (Schema::hasColumn('mezcla_medicamento_presentaciones', 'unidades_abiertas')) {
                $table->dropColumn('unidades_abiertas');
            }
        });

        Schema::table('medicine_batch_movements', function (Blueprint $table) {
            if (Schema::hasColumn('medicine_batch_movements', 'stock_ml_after')) {
                $table->dropColumn('stock_ml_after');
            }

            if (Schema::hasColumn('medicine_batch_movements', 'stock_ml_before')) {
                $table->dropColumn('stock_ml_before');
            }

            if (Schema::hasColumn('medicine_batch_movements', 'quantity_ml')) {
                $table->dropColumn('quantity_ml');
            }
        });

        Schema::table('medicine_batches', function (Blueprint $table) {
            if (Schema::hasColumn('medicine_batches', 'stock_ml_actual')) {
                $table->dropColumn('stock_ml_actual');
            }

            if (Schema::hasColumn('medicine_batches', 'stock_ml_inicial')) {
                $table->dropColumn('stock_ml_inicial');
            }
        });

        Schema::table('nutrition_medicine_presentations', function (Blueprint $table) {
            if (Schema::hasColumn('nutrition_medicine_presentations', 'stability_hours')) {
                $table->dropColumn('stability_hours');
            }
        });
    }
};
