<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('distribution_routes') && ! Schema::hasColumn('distribution_routes', 'route_type')) {
            Schema::table('distribution_routes', function (Blueprint $table) {
                $table->string('route_type')->default('vehicular')->after('code')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('distribution_routes') && Schema::hasColumn('distribution_routes', 'route_type')) {
            Schema::table('distribution_routes', function (Blueprint $table) {
                $table->dropIndex(['route_type']);
                $table->dropColumn('route_type');
            });
        }
    }
};
