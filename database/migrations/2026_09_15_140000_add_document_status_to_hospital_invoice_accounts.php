<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hospital_invoice_accounts', function (Blueprint $table) {
            $table->string('document_status', 20)->default('received');
        });
    }

    public function down(): void
    {
        Schema::table('hospital_invoice_accounts', fn (Blueprint $table) => $table->dropColumn('document_status'));
    }
};
