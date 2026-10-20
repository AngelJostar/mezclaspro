<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliation_period_decisions', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 80);
            $table->string('version', 64);
            $table->string('action', 16);
            $table->foreignId('institution_id')->constrained('clientes');
            $table->foreignId('hospital_id')->constrained('hospitals');
            $table->date('period_from');
            $table->date('period_to');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name');
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['group_key', 'version', 'action'], 'conciliation_period_decision_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliation_period_decisions');
    }
};
