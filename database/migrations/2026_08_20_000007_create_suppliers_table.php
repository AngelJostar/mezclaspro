<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('rfc', 20)->nullable()->unique();
            $table->string('contact_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('fax', 40)->nullable();
            $table->string('category', 80)->nullable()->index();
            $table->string('location')->nullable();
            $table->text('address')->nullable();
            $table->text('bank_details')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->importPurchaseOrderSuppliers();
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }

    private function importPurchaseOrderSuppliers(): void
    {
        if (! Schema::hasTable('laboratory_purchase_orders')) {
            return;
        }

        $orders = DB::table('laboratory_purchase_orders')
            ->select([
                'supplier',
                'supplier_rfc',
                'supplier_contact',
                'supplier_phone',
                'supplier_email',
                'supplier_fax',
                'supplier_address',
                'supplier_bank_details',
                'order_type',
                'created_by',
                'created_at',
                'updated_at',
            ])
            ->whereNotNull('supplier')
            ->where('supplier', '<>', '')
            ->orderByDesc('updated_at')
            ->get();

        $imported = [];

        foreach ($orders as $order) {
            $name = trim((string) $order->supplier);
            $rfc = Str::upper(trim((string) $order->supplier_rfc));
            $identity = $rfc !== '' ? 'rfc:'.$rfc : 'name:'.Str::lower($name);

            if (isset($imported[$identity])) {
                continue;
            }

            $imported[$identity] = true;

            DB::table('suppliers')->insert([
                'name' => $name,
                'rfc' => $rfc !== '' ? $rfc : null,
                'contact_name' => $order->supplier_contact ?: null,
                'phone' => $order->supplier_phone ?: null,
                'email' => $order->supplier_email ?: null,
                'fax' => $order->supplier_fax ?: null,
                'category' => $order->order_type ?: 'Medicamentos',
                'location' => null,
                'address' => $order->supplier_address ?: null,
                'bank_details' => $order->supplier_bank_details ?: null,
                'status' => 'active',
                'created_by' => $order->created_by,
                'created_at' => $order->created_at ?? now(),
                'updated_at' => $order->updated_at ?? now(),
            ]);
        }
    }
};
