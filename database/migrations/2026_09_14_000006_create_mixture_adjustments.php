<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixture_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 24);
            $table->unsignedBigInteger('target_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('status', 24)->default('requested');
            $table->text('description');
            $table->json('proposal');
            $table->json('review');
            $table->string('baseline_hash', 64);
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('authorized_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('hospital_response')->nullable();
            $table->text('central_response')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['kind', 'target_id']);
            $table->index(['hospital_id', 'status']);
        });
        foreach (['mezclas', 'solicituds'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('adjustment_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (['mezclas', 'solicituds'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('adjustment_id'));
        }
        Schema::dropIfExists('mixture_adjustments');
    }
};
