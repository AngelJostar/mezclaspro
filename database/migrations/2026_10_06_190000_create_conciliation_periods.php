<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliation_periods', function (Blueprint $table) {
            $table->id();
            $table->uuid('creation_key')->unique();
            $table->string('request_hash', 64);
            $table->foreignId('institution_id')->constrained('clientes');
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->date('period_from');
            $table->date('period_to');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('conciliation_period_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conciliation_period_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 24);
            $table->unsignedBigInteger('mixture_id');
            $table->json('snapshot');
            $table->unique(['kind', 'mixture_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliation_period_items');
        Schema::dropIfExists('conciliation_periods');
    }
};
