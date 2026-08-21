<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'username')) {
            DB::table('users')->update([
                'username' => DB::raw('LOWER(username)'),
            ]);
        }

        if (Schema::hasColumn('users', 'training_username')) {
            DB::table('users')
                ->whereNotNull('training_username')
                ->update([
                    'training_username' => DB::raw('LOWER(training_username)'),
                ]);
        }
    }

    public function down(): void
    {
        // The original capitalization cannot be recovered.
    }
};
