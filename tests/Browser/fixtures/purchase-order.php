<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
app('request')->setLaravelSession(session()->driver());
$old = json_decode($argv[1] ?? '{}', true);
session()->flash('_old_input', $old);
auth()->setUser(new App\Models\User(['name' => 'Ana Maria', 'lastname' => 'Lopez Ruiz']));
$laboratories = collect([1, 2])->map(function ($id) {
    $laboratory = new App\Models\Oncologicos\Laboratory();
    $laboratory->forceFill(['id' => $id, 'nombre' => 'Central '.$id, 'direccion' => 'Direccion '.$id]);
    $laboratory->setRelation('warehouses', collect([$id * 10 + 1, $id * 10 + 2])->map(function ($warehouseId) use ($id) {
        $warehouse = new App\Models\Warehouse();
        return $warehouse->forceFill(['id' => $warehouseId, 'laboratory_id' => $id, 'name' => 'Almacen '.$warehouseId, 'address' => 'Direccion '.$warehouseId]);
    }));
    return $laboratory;
});
// Render the real form without the admin layout, using fixtures and no database records.
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/oncologicos/laboratory/purchase-orders/create.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, [
    'laboratory' => $laboratories->first(), 'deliveryLaboratories' => $laboratories,
    'supplierOptions' => collect(), 'inventoryDestinations' => App\Models\Oncologicos\LaboratoryPurchaseOrder::INVENTORY_DESTINATIONS,
    'errors' => new Illuminate\Support\ViewErrorBag(),
]);
