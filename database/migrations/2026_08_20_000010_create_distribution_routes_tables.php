<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::create('distribution_route_hospital', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('stop_order')->default(1);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['distribution_route_id', 'hospital_id'], 'distribution_route_hospital_unique');
            $table->index(['distribution_route_id', 'stop_order'], 'distribution_route_stop_order_index');
        });

        Schema::create('distribution_route_messenger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['distribution_route_id', 'user_id'], 'distribution_route_messenger_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_route_messenger');
        Schema::dropIfExists('distribution_route_hospital');
        Schema::dropIfExists('distribution_routes');
    }
};
