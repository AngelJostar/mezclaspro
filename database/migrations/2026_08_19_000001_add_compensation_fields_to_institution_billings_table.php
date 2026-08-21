<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_billings', function (Blueprint $table) {
            $table->date('fecha_compensacion')->nullable()->after('fecha_carta_factura');
            $table->text('observaciones')->nullable()->after('fecha_compensacion');
        });
    }

    public function down(): void
    {
        Schema::table('institution_billings', function (Blueprint $table) {
            $table->dropColumn(['fecha_compensacion', 'observaciones']);
        });
    }
};
