<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_route_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_route_id');
            $table->unsignedBigInteger('messenger_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('start_latitude', 10, 7)->nullable();
            $table->decimal('start_longitude', 10, 7)->nullable();
            $table->decimal('start_accuracy', 10, 2)->nullable();
            $table->timestamps();

            $table->index(['messenger_id', 'ended_at'], 'dist_runs_messenger_active_idx');
            $table->index(['distribution_route_id', 'started_at'], 'dist_runs_route_started_idx');
            $table->foreign('distribution_route_id', 'dist_runs_route_fk')
                ->references('id')->on('distribution_routes')->cascadeOnDelete();
            $table->foreign('messenger_id', 'dist_runs_messenger_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('distribution_location_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_route_run_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['distribution_route_run_id', 'recorded_at'], 'dist_location_run_recorded_idx');
            $table->foreign('distribution_route_run_id', 'dist_location_run_fk')
                ->references('id')->on('distribution_route_runs')->cascadeOnDelete();
        });

        Schema::create('distribution_delivery_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribution_delivery_schedule_id');
            $table->unsignedBigInteger('distribution_route_run_id');
            $table->unsignedBigInteger('messenger_id');
            $table->timestamp('delivered_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['messenger_id', 'delivered_at'], 'dist_confirmation_messenger_idx');
            $table->unique('distribution_delivery_schedule_id', 'dist_confirmation_schedule_unique');
            $table->foreign('distribution_delivery_schedule_id', 'dist_confirmation_schedule_fk')
                ->references('id')->on('distribution_delivery_schedules')->cascadeOnDelete();
            $table->foreign('distribution_route_run_id', 'dist_confirmation_run_fk')
                ->references('id')->on('distribution_route_runs')->cascadeOnDelete();
            $table->foreign('messenger_id', 'dist_confirmation_messenger_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_delivery_confirmations');
        Schema::dropIfExists('distribution_location_updates');
        Schema::dropIfExists('distribution_route_runs');
    }
};
