<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\PurchaseNavigationData::seed();
Tests\Fixtures\MinimumStockData::seed();
Illuminate\Support\Facades\URL::forceRootUrl('http://stock.test');
$query = isset($argv[1]) ? json_decode($argv[1], true, 512, JSON_THROW_ON_ERROR) : [];
if (in_array($query['view'] ?? '', ['automated', 'history'], true) || isset($query['order'])) {
    Illuminate\Support\Facades\DB::table('medicine_batches')->where('id', 1)->update(['stock_actual' => 2]);
    foreach ([['nutrition', 1, 10, 20], ['medicine', 3, 6, 18]] as [$type, $id, $min, $max]) {
        App\Models\MinimumStockSetting::create(['laboratory_id' => 1, 'product_type' => $type, 'presentation_id' => $id,
            'minimum_stock' => $min, 'maximum_stock' => $max]);
    }
    app(App\Services\AutomaticPurchaseOrderService::class)->reconcile();
    $closed = App\Models\Oncologicos\LaboratoryPurchaseOrder::where('is_automatic', true)->where('inventory_destination', 'oncologicos')->first()->replicate();
    $closed->fill(['folio' => 'OC-AUTO-HIST-TEST', 'status' => 'cancelada', 'automatic_open_key' => null])->save();
}
$request = Illuminate\Http\Request::create(route('admin.purchases.minimum-stock'), 'GET', $query + ['laboratory_id' => 1]);
session()->start();
$request->setUserResolver(fn () => $user);
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
if (isset($query['order'])) {
    echo app(App\Http\Controllers\Admin\WarehouseController::class)->automaticPurchaseOrder(
        App\Models\Oncologicos\Laboratory::findOrFail($query['laboratory_id']),
        App\Models\Oncologicos\LaboratoryPurchaseOrder::findOrFail($query['order']))->render();
    exit;
}
echo app(App\Http\Controllers\Admin\WarehouseController::class)
    ->minimumStock($request, app(App\Services\MinimumStockCatalogService::class))->render();
