<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_quotations', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('request_quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
        });
    }
};
