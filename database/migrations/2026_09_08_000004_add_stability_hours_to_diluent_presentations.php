<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('diluent_presentations', function (Blueprint $table) {
            $table->unsignedSmallInteger('stability_hours')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('diluent_presentations', fn (Blueprint $table) => $table->dropColumn('stability_hours'));
    }
};
