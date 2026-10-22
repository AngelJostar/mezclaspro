<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_services', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('applicable_assets')->nullable();
            $table->string('deliverables')->nullable();
            $table->boolean('report_required')->default(true);
        });
        Schema::create('maintenance_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialty')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('coverage')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('maintenance_provider_service', function (Blueprint $table) {
            $table->foreignId('provider_id')->constrained('maintenance_providers')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('maintenance_services')->cascadeOnDelete();
            $table->primary(['provider_id', 'service_id']);
        });
        Schema::create('maintenance_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('maintenance_services')->restrictOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained('maintenance_providers')->restrictOnDelete();
            $table->foreignId('laboratory_id')->constrained('laboratories')->restrictOnDelete();
            $table->string('asset');
            $table->string('location')->nullable();
            $table->string('responsible_name')->nullable();
            $table->string('frequency')->nullable();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->date('next_service_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->string('report_status')->default('pending');
            $table->text('report_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('maintenance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained('maintenance_providers')->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('maintenance_appointments')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedBigInteger('size');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_documents');
        Schema::dropIfExists('maintenance_appointments');
        Schema::dropIfExists('maintenance_provider_service');
        Schema::dropIfExists('maintenance_providers');
        Schema::table('maintenance_services', fn (Blueprint $table) => $table->dropColumn(['description', 'applicable_assets', 'deliverables', 'report_required']));
    }
};
