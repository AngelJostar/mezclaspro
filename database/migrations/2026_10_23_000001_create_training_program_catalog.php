<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_programs', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('owner', 100);
            $table->string('status', 20)->default('Borrador');
            $table->json('module_definitions');
            $table->unsignedInteger('students')->default(0);
            $table->boolean('is_custom')->default(false);
            $table->boolean('legacy_imported')->default(false);
            $table->timestamps();
        });
        // Keep only the identifier, so an old browser cache cannot recreate deleted data.
        Schema::create('deleted_training_programs', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->timestamp('deleted_at');
        });
        Schema::create('training_program_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('training_program_id', 191);
            $table->foreign('training_program_id')->references('id')->on('training_programs')->cascadeOnDelete();
            $table->json('module_ids');
            $table->boolean('all_modules')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'training_program_id']);
        });
        Schema::table('personnel_profiles', fn (Blueprint $table) => $table->boolean('training_access_configured')->default(false));
        foreach (\App\Support\TrainingProgramDefaults::programs() as $program) {
            DB::table('training_programs')->insert([
                'id' => $program['id'], 'title' => $program['title'], 'description' => $program['description'],
                'owner' => $program['owner'], 'status' => $program['status'], 'students' => $program['students'],
                'module_definitions' => json_encode($program['module_definitions'], JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('training_program_access');
        Schema::dropIfExists('deleted_training_programs');
        Schema::dropIfExists('training_programs');
        Schema::table('personnel_profiles', fn (Blueprint $table) => $table->dropColumn('training_access_configured'));
    }
};
