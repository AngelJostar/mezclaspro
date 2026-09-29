<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinical_sources', function (Blueprint $table) {
            $table->boolean('allows_medical_authorization')->default(false);
        });
        Schema::table('clinical_reviews', function (Blueprint $table) {
            $table->longText('medical_authorization')->nullable();
        });
        Schema::table('solicitud_oncos', function (Blueprint $table) {
            $table->decimal('talla', 6, 2)->nullable();
            $table->decimal('superficie_corporal', 6, 3)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('solicitud_oncos', fn (Blueprint $table) => $table->dropColumn(['talla', 'superficie_corporal']));
        Schema::table('clinical_reviews', fn (Blueprint $table) => $table->dropColumn('medical_authorization'));
        Schema::table('clinical_sources', fn (Blueprint $table) => $table->dropColumn('allows_medical_authorization'));
    }
};
