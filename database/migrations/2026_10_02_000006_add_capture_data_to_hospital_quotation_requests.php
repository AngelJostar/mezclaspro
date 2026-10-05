<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('hospital_quotation_requests', fn (Blueprint $table) => $table->json('capture_data')->nullable()); }
    public function down(): void { Schema::table('hospital_quotation_requests', fn (Blueprint $table) => $table->dropColumn('capture_data')); }
};
