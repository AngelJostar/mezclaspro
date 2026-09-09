<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mixture_lot_sequences', function (Blueprint $table) {
            $table->string('prefix', 8)->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mixture_lot_sequences');
    }
};
