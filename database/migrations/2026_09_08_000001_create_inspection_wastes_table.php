<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mezclas', function (Blueprint $table) {
            $table->unsignedInteger('production_attempt')->default(1);
        });

        Schema::create('inspection_wastes', function (Blueprint $table) {
            $table->id();
            // Keep the audit record even if the source mixture is subsequently removed.
            $table->unsignedBigInteger('mezcla_id')->index();
            $table->unsignedInteger('production_attempt');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('reason');
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['mezcla_id', 'production_attempt']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_wastes');
        Schema::table('mezclas', fn (Blueprint $table) => $table->dropColumn('production_attempt'));
    }
};
