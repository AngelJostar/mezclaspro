<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('rfc', 20)->nullable()->after('razon_social');
            $table->string('telefono', 30)->nullable()->after('rfc');

            $table->index('rfc');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropIndex(['rfc']);
            $table->dropColumn(['rfc', 'telefono']);
        });
    }
};
