<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('hospitals', 'access_is_active')) {
            return;
        }

        Schema::table('hospitals', function (Blueprint $table) {
            $table->boolean('access_is_active')->default(true)->after('is_active')->index();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('hospitals', 'access_is_active')) {
            return;
        }

        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn('access_is_active');
        });
    }
};
