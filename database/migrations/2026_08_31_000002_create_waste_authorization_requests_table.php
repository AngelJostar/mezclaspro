<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_authorization_requests', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 24);
            $table->foreignId('medicine_batch_id')->nullable();
            $table->foreignId('medicine_laboratory_stock_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->unsignedInteger('quantity_containers');
            $table->decimal('quantity_ml', 12, 4);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('snapshot_product');
            $table->string('snapshot_presentation')->nullable();
            $table->string('snapshot_brand')->nullable();
            $table->string('snapshot_lot')->nullable();
            $table->string('snapshot_laboratory')->nullable();
            $table->string('snapshot_warehouse')->nullable();
            $table->decimal('snapshot_cost_per_ml', 12, 4)->nullable();
            $table->decimal('snapshot_cost_per_container', 12, 4)->nullable();
            $table->timestamps();

            $table->foreign('medicine_batch_id', 'waste_requests_batch_fk')
                ->references('id')
                ->on('medicine_batches')
                ->nullOnDelete();
            $table->foreign('medicine_laboratory_stock_id', 'waste_requests_nutrition_fk')
                ->references('id')
                ->on('medicine_laboratory_stocks')
                ->nullOnDelete();
            $table->foreign('requested_by', 'waste_requests_requester_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('reviewed_by', 'waste_requests_reviewer_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['status', 'created_at'], 'waste_requests_status_created_idx');
            $table->index(['medicine_batch_id', 'status'], 'waste_requests_batch_status_idx');
            $table->index(
                ['medicine_laboratory_stock_id', 'status'],
                'waste_requests_nutrition_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_authorization_requests');
    }
};
