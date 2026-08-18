<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitud_oncos', function (Blueprint $table) {
            $table->string('tipo_solicitud', 20)
                ->default('oncologicos')
                ->after('hospital_id')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_oncos', function (Blueprint $table) {
            $table->dropIndex(['tipo_solicitud']);
            $table->dropColumn('tipo_solicitud');
        });
    }
};
