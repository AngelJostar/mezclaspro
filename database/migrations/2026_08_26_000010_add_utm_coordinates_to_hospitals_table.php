<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->unsignedTinyInteger('utm_zone')->nullable()->after('longitude');
            $table->char('utm_hemisphere', 1)->nullable()->after('utm_zone');
            $table->decimal('utm_easting', 12, 3)->nullable()->after('utm_hemisphere');
            $table->decimal('utm_northing', 13, 3)->nullable()->after('utm_easting');
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn([
                'utm_zone',
                'utm_hemisphere',
                'utm_easting',
                'utm_northing',
            ]);
        });
    }
};
