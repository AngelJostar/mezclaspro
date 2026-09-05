<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('personnel_profiles', 'force_password_change')) {
            return;
        }

        DB::table('personnel_profiles')->update([
            'force_password_change' => false,
        ]);

        Schema::table('personnel_profiles', function (Blueprint $table) {
            $table->boolean('force_password_change')->default(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('personnel_profiles', 'force_password_change')) {
            return;
        }

        Schema::table('personnel_profiles', function (Blueprint $table) {
            $table->boolean('force_password_change')->default(true)->change();
        });
    }
};
