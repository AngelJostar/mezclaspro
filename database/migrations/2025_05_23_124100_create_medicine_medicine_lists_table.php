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
        Schema::create('medicine_medicine_lists', function (Blueprint $table) {
            $table->unsignedBigInteger('medicine_list_id');
            $table->unsignedBigInteger('medicine_id');
            $table->decimal('precio', 10, 2);
            $table->primary(['medicine_list_id', 'medicine_id']);
            $table->foreign('medicine_list_id')->references('id')->on('medicine_lists')->onDelete('cascade');
            $table->foreign('medicine_id')->references('id')->on('medicine_oncos')->onDelete('cascade');
            $table->decimal('precio_mg_override', 12, 4)->nullable();
            $table->enum('charge_by', ['mg', 'frasco'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_medicine_lists');
    }
};
