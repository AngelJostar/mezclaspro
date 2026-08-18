<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->string('department')->nullable()->after('folio');
            $table->string('supplier_rfc', 20)->nullable()->after('supplier');
            $table->text('supplier_bank_details')->nullable()->after('supplier_rfc');
            $table->text('supplier_address')->nullable()->after('supplier_bank_details');
            $table->string('supplier_contact')->nullable()->after('supplier_address');
            $table->string('supplier_phone', 40)->nullable()->after('supplier_contact');
            $table->string('quotation_number')->nullable()->after('supplier_phone');
            $table->string('order_type', 80)->nullable()->after('quotation_number');
            $table->string('supplier_email')->nullable()->after('order_type');
            $table->string('supplier_fax', 40)->nullable()->after('supplier_email');
            $table->date('proposed_delivery_at')->nullable()->after('requested_at');
            $table->string('urgent_delivery_time')->nullable()->after('proposed_delivery_at');
            $table->string('invoice_to')->nullable()->after('urgent_delivery_time');
            $table->text('invoice_address')->nullable()->after('invoice_to');
            $table->string('invoice_rfc', 20)->nullable()->after('invoice_address');
            $table->text('invoice_emails')->nullable()->after('invoice_rfc');
            $table->string('delivery_attention')->nullable()->after('invoice_emails');
            $table->text('delivery_address')->nullable()->after('delivery_attention');
            $table->string('delivery_schedule')->nullable()->after('delivery_address');
            $table->json('items')->nullable()->after('details');
            $table->decimal('subtotal', 14, 2)->default(0)->after('items');
            $table->decimal('discount', 14, 2)->default(0)->after('subtotal');
            $table->decimal('tax_rate', 5, 2)->default(16)->after('discount');
            $table->decimal('tax_amount', 14, 2)->default(0)->after('tax_rate');
            $table->decimal('total', 14, 2)->default(0)->after('tax_amount');
            $table->string('prepared_by')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'department',
                'supplier_rfc',
                'supplier_bank_details',
                'supplier_address',
                'supplier_contact',
                'supplier_phone',
                'quotation_number',
                'order_type',
                'supplier_email',
                'supplier_fax',
                'proposed_delivery_at',
                'urgent_delivery_time',
                'invoice_to',
                'invoice_address',
                'invoice_rfc',
                'invoice_emails',
                'delivery_attention',
                'delivery_address',
                'delivery_schedule',
                'items',
                'subtotal',
                'discount',
                'tax_rate',
                'tax_amount',
                'total',
                'prepared_by',
            ]);
        });
    }
};
