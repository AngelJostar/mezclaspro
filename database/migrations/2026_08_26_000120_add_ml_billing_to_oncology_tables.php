<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medicine_lists MODIFY charge_by ENUM('mg','ml','frasco') NOT NULL DEFAULT 'mg'");
            DB::statement("ALTER TABLE medicine_medicine_lists MODIFY charge_by ENUM('mg','ml','frasco') NULL");
            DB::statement("ALTER TABLE medicine_list_presentation MODIFY charge_by ENUM('mg','ml','frasco') NOT NULL");
            DB::statement("ALTER TABLE mezcla_medicamentos MODIFY charge_by ENUM('mg','ml','frasco') NOT NULL DEFAULT 'mg'");
        }

        Schema::table('medicine_list_presentation', function (Blueprint $table) {
            $table->decimal('precio_ml_override', 12, 4)->nullable()->after('precio_mg_override');
        });
        Schema::table('mezcla_medicamentos', function (Blueprint $table) {
            $table->decimal('precio_ml_snapshot', 12, 4)->nullable()->after('precio_mg_snapshot');
        });
        Schema::table('mezcla_medicamento_presentaciones', function (Blueprint $table) {
            $table->string('charge_by_snapshot', 20)->nullable()->after('volumen_usado_ml');
            $table->decimal('precio_unitario_snapshot', 12, 4)->nullable()->after('precio_frasco_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('mezcla_medicamento_presentaciones', function (Blueprint $table) {
            $table->dropColumn(['charge_by_snapshot', 'precio_unitario_snapshot']);
        });
        Schema::table('mezcla_medicamentos', function (Blueprint $table) {
            $table->dropColumn('precio_ml_snapshot');
        });
        Schema::table('medicine_list_presentation', function (Blueprint $table) {
            $table->dropColumn('precio_ml_override');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE mezcla_medicamentos MODIFY charge_by ENUM('mg','frasco') NOT NULL DEFAULT 'mg'");
            DB::statement("ALTER TABLE medicine_list_presentation MODIFY charge_by ENUM('mg','frasco') NOT NULL");
            DB::statement("ALTER TABLE medicine_medicine_lists MODIFY charge_by ENUM('mg','frasco') NULL");
            DB::statement("ALTER TABLE medicine_lists MODIFY charge_by ENUM('mg','frasco') NOT NULL DEFAULT 'mg'");
        }
    }
};
