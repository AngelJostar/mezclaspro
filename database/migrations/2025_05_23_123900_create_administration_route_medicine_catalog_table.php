<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('administration_route_medicine_catalog', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('administration_route_id');
            $table->foreign('administration_route_id', 'admin_route_fk')
                ->references('id')
                ->on('administration_routes')
                ->onDelete('cascade');
            $table->unsignedBigInteger('medicine_catalog_id');
            $table->foreign('medicine_catalog_id', 'med_catalog_fk')
                ->references('id')
                ->on('medicines_catalog')
                ->onDelete('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administration_route_medicine_catalog');
    }
};
