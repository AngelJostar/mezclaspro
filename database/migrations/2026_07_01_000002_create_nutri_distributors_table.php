<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nutri_distributors')) {
            return;
        }

        Schema::create('nutri_distributors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutri_medicine_list_id')
                ->unique()
                ->constrained('nutri_medicine_lists')
                ->cascadeOnDelete();
            $table->string('nombre');
            $table->text('direccion')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutri_distributors');
    }
};
