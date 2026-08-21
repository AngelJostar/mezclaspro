<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->foreignId('assigned_buyer_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('commercial_name')->nullable()->after('name');
            $table->string('subcategory', 80)->nullable()->after('category');
            $table->string('municipality', 150)->nullable()->after('location');
            $table->string('postal_code', 10)->nullable()->after('municipality');
            $table->string('bank_name', 120)->nullable()->after('bank_details');
            $table->string('bank_account', 50)->nullable()->after('bank_name');
            $table->string('bank_clabe', 18)->nullable()->after('bank_account');
            $table->string('bank_reference')->nullable()->after('bank_clabe');
            $table->string('tax_certificate_path')->nullable()->after('bank_reference');
            $table->string('bank_cover_path')->nullable()->after('tax_certificate_path');
            $table->string('additional_document_path')->nullable()->after('bank_cover_path');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_buyer_id');
            $table->dropColumn([
                'commercial_name',
                'subcategory',
                'municipality',
                'postal_code',
                'bank_name',
                'bank_account',
                'bank_clabe',
                'bank_reference',
                'tax_certificate_path',
                'bank_cover_path',
                'additional_document_path',
            ]);
        });
    }
};
