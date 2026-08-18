<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->string('short_name', 100)->nullable()->after('name');
            $table->string('internal_key', 50)->nullable()->unique()->after('short_name');
            $table->string('unit_type', 100)->nullable()->after('internal_key');
            $table->string('care_level', 100)->nullable()->after('unit_type');
            $table->string('rfc', 20)->nullable()->after('care_level');
            $table->string('clues', 30)->nullable()->after('rfc');
            $table->string('state', 100)->nullable()->after('clues');
            $table->string('municipality', 150)->nullable()->after('state');
            $table->string('postal_code', 10)->nullable()->after('municipality');
            $table->string('neighborhood', 150)->nullable()->after('postal_code');
            $table->string('street_number')->nullable()->after('neighborhood');
            $table->string('contact_name', 150)->nullable()->after('street_number');
            $table->string('contact_position', 100)->nullable()->after('contact_name');
            $table->string('phone', 30)->nullable()->after('contact_position');
            $table->string('email', 150)->nullable()->after('phone');
            $table->string('reception_hours', 100)->nullable()->after('email');
            $table->json('operation_days')->nullable()->after('reception_hours');
            $table->boolean('service_oncology')->default(false)->after('operation_days');
            $table->boolean('service_antibiotics')->default(false)->after('service_oncology');
            $table->boolean('service_nutrition')->default(false)->after('service_antibiotics');
        });
    }

    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropUnique(['internal_key']);
            $table->dropColumn([
                'short_name',
                'internal_key',
                'unit_type',
                'care_level',
                'rfc',
                'clues',
                'state',
                'municipality',
                'postal_code',
                'neighborhood',
                'street_number',
                'contact_name',
                'contact_position',
                'phone',
                'email',
                'reception_hours',
                'operation_days',
                'service_oncology',
                'service_antibiotics',
                'service_nutrition',
            ]);
        });
    }
};
