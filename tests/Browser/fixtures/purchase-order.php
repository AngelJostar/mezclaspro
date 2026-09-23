<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
app('request')->setLaravelSession(session()->driver());
$old = json_decode($argv[1] ?? '{}', true);
if (($argv[2] ?? '') === 'popup') {
    app('request')->query->set('purchase_popup', 1);
    Illuminate\Support\Facades\URL::forceRootUrl('http://purchase-order.test');
}
session()->flash('_old_input', $old);
auth()->setUser(new App\Models\User(['name' => 'Ana Maria', 'lastname' => 'Lopez Ruiz']));
auth()->user()->setRelation('roles', new Illuminate\Database\Eloquent\Collection([
    new Spatie\Permission\Models\Role(['name' => 'Super Admin', 'guard_name' => 'web']),
]));
$laboratories = collect([1, 2])->map(function ($id) {
    $laboratory = new App\Models\Oncologicos\Laboratory();
    $laboratory->forceFill(['id' => $id, 'nombre' => 'Central '.$id, 'direccion' => 'Direccion '.$id, 'activo' => true, 'active_warehouses_count' => 2]);
    $laboratory->setRelation('warehouses', collect([$id * 10 + 1, $id * 10 + 2])->map(function ($warehouseId) use ($id) {
        $warehouse = new App\Models\Warehouse();
        return $warehouse->forceFill(['id' => $warehouseId, 'laboratory_id' => $id, 'name' => 'Almacen '.$warehouseId, 'address' => 'Direccion '.$warehouseId]);
    }));
    return $laboratory;
});
// Render the real form without the admin layout, using fixtures and no database records.
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/oncologicos/laboratory/purchase-orders/create.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, [
    'laboratory' => $laboratories->first(), 'deliveryLaboratories' => $laboratories, 'laboratories' => $laboratories,
    'supplierOptions' => collect(), 'inventoryDestinations' => App\Models\Oncologicos\LaboratoryPurchaseOrder::INVENTORY_DESTINATIONS,
    'errors' => new Illuminate\Support\ViewErrorBag(),
]);
