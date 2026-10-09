<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->string('engine', 24);
            $table->string('population', 24)->default('both');
            $table->string('severity', 24)->default('blocking');
            $table->string('status', 24)->default('draft');
            $table->text('description')->nullable();
            $table->json('configuration');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_enforced')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['engine', 'population', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_rules');
    }
};
