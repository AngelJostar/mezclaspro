<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_lists', function (Blueprint $table) {
            $table->boolean('has_contract')->default(false)->after('show_label_lot_expiry');
            $table->string('contract_number')->nullable()->after('has_contract');
            $table->text('contract_information')->nullable()->after('contract_number');
        });

        Schema::table('nutri_medicine_lists', function (Blueprint $table) {
            $table->boolean('has_contract')->default(false)->after('active_brands');
            $table->string('contract_number')->nullable()->after('has_contract');
            $table->text('contract_information')->nullable()->after('contract_number');
        });

        Schema::table('distributors', function (Blueprint $table) {
            $table->string('rfc', 20)->nullable()->after('nombre');
            $table->string('contacto')->nullable()->after('direccion');
        });

        Schema::table('nutri_distributors', function (Blueprint $table) {
            $table->string('rfc', 20)->nullable()->after('nombre');
            $table->string('contacto')->nullable()->after('direccion');
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn(['rfc', 'contacto']);
        });

        Schema::table('nutri_distributors', function (Blueprint $table) {
            $table->dropColumn(['rfc', 'contacto']);
        });

        Schema::table('medicine_lists', function (Blueprint $table) {
            $table->dropColumn(['has_contract', 'contract_number', 'contract_information']);
        });

        Schema::table('nutri_medicine_lists', function (Blueprint $table) {
            $table->dropColumn(['has_contract', 'contract_number', 'contract_information']);
        });
    }
};
