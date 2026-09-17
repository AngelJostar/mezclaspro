<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ai_agents', fn (Blueprint $table) => $table->string('integration_key')->nullable()->unique());
    }

    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropUnique(['integration_key']);
            $table->dropColumn('integration_key');
        });
    }
};
