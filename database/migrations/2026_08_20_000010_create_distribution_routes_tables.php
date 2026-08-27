<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distribution_routes')) {
            Schema::create('distribution_routes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->time('schedule_start');
                $table->time('schedule_end');
                $table->string('status')->default('pending');
                $table->uuid('qr_token')->unique();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->index(['status', 'schedule_start']);
            });
        }

        if (! Schema::hasTable('distribution_route_hospital')) {
            Schema::create('distribution_route_hospital', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distribution_route_id')->constrained('distribution_routes')->cascadeOnDelete();
                $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
                $table->unsignedInteger('stop_order')->default(1);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->unique(['distribution_route_id', 'hospital_id']);
                $table->index(['distribution_route_id', 'stop_order']);
            });
        }

        if (! Schema::hasTable('distribution_route_messenger')) {
            Schema::create('distribution_route_messenger', function (Blueprint $table) {
                $table->id();
                $table->foreignId('distribution_route_id')->constrained('distribution_routes')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['distribution_route_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_route_messenger');
        Schema::dropIfExists('distribution_route_hospital');
        Schema::dropIfExists('distribution_routes');
    }
};
