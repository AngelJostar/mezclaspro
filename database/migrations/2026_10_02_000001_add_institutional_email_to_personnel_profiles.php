<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('personnel_profiles', function (Blueprint $table) {
            $table->string('institutional_email')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('personnel_profiles', fn (Blueprint $table) => $table->dropColumn('institutional_email'));
    }
};
