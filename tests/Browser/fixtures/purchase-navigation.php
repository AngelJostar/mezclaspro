<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\PurchaseNavigationData::seed();
Illuminate\Support\Facades\URL::forceRootUrl('http://purchase-navigation.test/mezclaspro/public');
$section = $argv[1] ?? 'mine';
$central = (int) ($argv[2] ?? 1);
$routeName = $section === 'minimum-stock' ? 'admin.purchases.minimum-stock' : 'admin.warehouses.purchase-orders.index';
$request = Illuminate\Http\Request::create(route($routeName), 'GET', ['section' => $section, 'laboratory_id' => $central]);
$request->setUserResolver(fn () => $user);
$route = app('router')->getRoutes()->getByName($routeName)->bind($request);
$request->setRouteResolver(fn () => $route);
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 0, 'myPurchaseOrdersCount' => 3])->render();
if ($section === 'minimum-stock') {
    echo app(App\Http\Controllers\Admin\WarehouseController::class)->minimumStock($request, app(App\Services\MinimumStockCatalogService::class))->render();
} elseif ($section === 'new') {
    echo app(App\Http\Controllers\Admin\Oncologicos\LaboratoryPurchaseOrderController::class)->create(App\Models\Oncologicos\Laboratory::findOrFail($central))->render();
} else {
    echo app(App\Http\Controllers\Admin\WarehouseController::class)->purchaseOrders($request)->render();
}
