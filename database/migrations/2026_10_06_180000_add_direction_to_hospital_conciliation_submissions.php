<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hospital_conciliation_submissions', function (Blueprint $table) {
            // Existing reports were submitted by hospitals to Prodifem.
            $table->string('direction', 16)->default('received')->index();
        });
    }

    public function down(): void
    {
        Schema::table('hospital_conciliation_submissions', fn (Blueprint $table) => $table->dropColumn('direction'));
    }
};
