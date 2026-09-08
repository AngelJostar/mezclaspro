<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->nullable()->constrained('laboratories')->nullOnDelete();
            $table->string('source_key')->nullable()->unique();
            $table->string('service');
            $table->string('qualification_stages')->nullable();
            $table->string('type')->nullable();
            $table->string('areas')->nullable();
            $table->string('frequency')->nullable();
            $table->text('identification')->nullable();
            $table->string('providers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_services');
    }
};
