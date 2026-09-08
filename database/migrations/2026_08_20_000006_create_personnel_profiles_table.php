<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('laboratory_id')->nullable()->constrained('laboratories')->nullOnDelete();
            $table->string('paternal_surname');
            $table->string('maternal_surname')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('personal_email')->nullable()->unique();
            $table->json('positions');
            $table->string('department')->nullable();
            $table->date('hire_date');
            $table->string('employment_status', 30)->default('hired');
            $table->boolean('force_password_change')->default(false);
            $table->string('cv_path')->nullable();
            $table->string('cv_original_name')->nullable();
            $table->text('prior_experience')->nullable();
            $table->text('additional_information')->nullable();
            $table->timestamps();

            $table->index(['hire_date', 'employment_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_profiles');
    }
};
