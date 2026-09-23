<?php

namespace Tests\Fixtures;

use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PurchaseNavigationData
{
    public static function seed(): User
    {
        $user = SolicitudValidations::boot();
        foreach ([
            'laboratories' => ['nombre', 'direccion', 'activo'],
            'warehouses' => ['laboratory_id', 'name', 'address', 'is_active'],
            'users' => ['name', 'lastname', 'is_active'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id();
                foreach ($columns as $column) $table->string($column)->nullable();
                $table->timestamps();
            });
        }
        foreach ([
            '2026_08_11_000004_create_laboratory_purchase_orders_table.php',
            '2026_08_11_000006_expand_laboratory_purchase_orders.php',
            '2026_08_13_000002_add_delivery_destination_to_laboratory_purchase_orders.php',
            '2026_08_20_000001_add_inventory_destination_to_laboratory_purchase_orders.php',
            '2026_08_20_000007_create_suppliers_table.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Usuario de prueba', 'is_active' => 1],
            ['id' => 2, 'name' => 'Otro usuario', 'is_active' => 1],
        ]);
        foreach (['CDMX', 'Monterrey', 'Puebla', 'Queretaro', 'Toluca', 'Veracruz', 'Central historica'] as $index => $name) {
            $id = $index + 1;
            DB::table('laboratories')->insert(['id' => $id, 'nombre' => $name, 'direccion' => 'Avenida de prueba 100, Colonia Centro', 'activo' => $id < 7]);
            DB::table('warehouses')->insert(['id' => $id, 'laboratory_id' => $id, 'name' => 'Almacen '.$name, 'is_active' => true]);
            foreach (['pagada', 'pendiente_pago', 'rechazada', 'enviada'] as $status) {
                LaboratoryPurchaseOrder::create([
                    'laboratory_id' => $id, 'folio' => 'OC-'.$id.'-'.$status,
                    'supplier' => 'Proveedor de prueba', 'requested_at' => '2026-09-01',
                    'details' => 'Partida de prueba', 'status' => $status, 'created_by' => $status === 'pagada' ? 2 : 1,
                    'warehouse_id' => $id, 'total' => 100, 'inventory_destination' => 'oncologicos',
                ]);
            }
        }
        DB::table('warehouses')->insert(['id' => 8, 'laboratory_id' => 1, 'name' => 'Almacen inactivo', 'is_active' => false]);
        MinimumStockData::migrate();

        return $user;
    }
}
