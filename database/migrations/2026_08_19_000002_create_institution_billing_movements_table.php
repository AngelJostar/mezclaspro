<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_billing_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_billing_id')->nullable()->constrained('institution_billings')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('origen_tipo', 50);
            $table->unsignedBigInteger('origen_id');
            $table->string('remision')->nullable();
            $table->string('from_stage', 30);
            $table->string('to_stage', 30);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['origen_tipo', 'origen_id'], 'institution_billing_movements_origin_index');
            $table->index(['from_stage', 'to_stage'], 'institution_billing_movements_stage_index');
            $table->index('created_at', 'institution_billing_movements_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_billing_movements');
    }
};
