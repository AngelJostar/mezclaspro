<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_supply_requests', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('area')->default('Producción');
            $table->string('status')->default('requested')->index();
            $table->text('observations')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supplied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('supplied_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('production_supply_request_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_supply_request_id');
            $table->unsignedBigInteger('diluent_presentation_id');
            $table->decimal('requested_quantity', 12, 2);
            $table->decimal('approved_quantity', 12, 2)->nullable();
            $table->decimal('supplied_quantity', 12, 2)->nullable();
            $table->decimal('received_quantity', 12, 2)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();
            $table->unique(['production_supply_request_id', 'diluent_presentation_id'], 'psr_line_unique_supply');
            $table->foreign('production_supply_request_id', 'psrl_request_fk')
                ->references('id')->on('production_supply_requests')->cascadeOnDelete();
            $table->foreign('diluent_presentation_id', 'psrl_supply_fk')
                ->references('id')->on('diluent_presentations')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_supply_request_lines');
        Schema::dropIfExists('production_supply_requests');
    }
};
