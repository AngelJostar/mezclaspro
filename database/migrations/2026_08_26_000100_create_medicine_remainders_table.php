<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_remainders', function (Blueprint $table) {
            $table->id();
            $table->enum('domain', ['nutricional', 'oncologico']);
            $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();

            $table->foreignId('nutrition_medicine_presentation_id')
                ->nullable()
                ->constrained('nutrition_medicine_presentations')
                ->nullOnDelete();

            $table->foreignId('medicine_presentation_id')
                ->nullable()
                ->constrained('medicine_presentations')
                ->nullOnDelete();

            $table->foreignId('medicine_laboratory_stock_id')
                ->nullable()
                ->constrained('medicine_laboratory_stocks')
                ->nullOnDelete();

            $table->foreignId('medicine_batch_id')
                ->nullable()
                ->constrained('medicine_batches')
                ->nullOnDelete();

            $table->string('lote', 100)->nullable();
            $table->date('caducidad')->nullable();
            $table->dateTime('opened_at');
            $table->dateTime('usable_until')->nullable();
            $table->decimal('initial_ml', 12, 4)->default(0);
            $table->decimal('current_ml', 12, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('opened_for_type', 80)->nullable();
            $table->unsignedBigInteger('opened_for_id')->nullable();
            $table->dateTime('discarded_at')->nullable();
            $table->string('discard_reason', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['domain', 'laboratory_id', 'is_active'], 'mr_domain_lab_active_idx');
            $table->index(['nutrition_medicine_presentation_id', 'usable_until'], 'mr_nutri_presentation_idx');
            $table->index(['medicine_presentation_id', 'usable_until'], 'mr_onco_presentation_idx');
            $table->index(['medicine_laboratory_stock_id'], 'mr_nutri_stock_idx');
            $table->index(['medicine_batch_id'], 'mr_onco_batch_idx');
            $table->index(['opened_for_type', 'opened_for_id'], 'mr_opened_for_idx');
        });

        Schema::create('medicine_remainder_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_remainder_id')
                ->constrained('medicine_remainders')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('movement_type', ['apertura', 'consumo', 'devolucion', 'descarte', 'ajuste']);
            $table->decimal('quantity_ml', 12, 4)->default(0);
            $table->decimal('stock_before_ml', 12, 4)->default(0);
            $table->decimal('stock_after_ml', 12, 4)->default(0);
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id'], 'mrm_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_remainder_movements');
        Schema::dropIfExists('medicine_remainders');
    }
};
