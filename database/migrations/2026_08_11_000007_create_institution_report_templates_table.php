<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_report_templates', function (Blueprint $table) {
            $table->id();
            $table->string('report_key')->unique();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->json('columns');
            $table->json('info_boxes')->nullable();
            $table->json('free_fields')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_report_templates');
    }
};
