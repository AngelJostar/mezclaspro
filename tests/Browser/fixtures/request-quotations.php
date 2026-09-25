<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = !empty($argv[2]) ? Tests\Fixtures\RequestQuotationCaptureData::seed() : Tests\Fixtures\RequestQuotationData::seed();
if (in_array($argv[3] ?? '', ['Cliente', 'Institucion'], true)) {
    $user->syncRoles(Spatie\Permission\Models\Role::findOrCreate($argv[3], 'web'));
}
if (($argv[2] ?? '') === 'preparation') {
    foreach (['oncologicos', 'nutricionales'] as $type) {
        $user->givePermissionTo(Spatie\Permission\Models\Permission::findOrCreate($type.'_solicitudes_store', 'web'));
    }
}
$seller = App\Models\User::create(['name' => 'Vendedora', 'lastname' => 'Prueba', 'username' => 'ventas.prueba', 'password' => 'fixture', 'is_active' => true]);
$seller->assignRole(Spatie\Permission\Models\Role::findOrCreate('Vendedor', 'web'));
App\Models\RequestQuotation::whereKey(1)->update(['seller_id' => $seller->id]);
parse_str($argv[1] ?? '', $query);
if (($argv[2] ?? '') === 'options') {
    echo json_encode(app(App\Services\RequestQuotationCaptureService::class)->catalog($user, $query['category'], 1));
    exit;
}
$request = Illuminate\Http\Request::create('http://localhost/admin/solicitudes/cotizacion', 'GET', $query);
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.solicitudes.cotizacion.index'));
$app->instance('request', $request);
if (($argv[2] ?? '') === 'show') {
    echo app(App\Http\Controllers\Admin\RequestQuotationController::class)->show($request, App\Models\RequestQuotation::findOrFail($query['id']))->getContent();
    exit;
}
$data = app(App\Http\Controllers\Admin\RequestQuotationController::class)->index($request)->getData();
$data['errors'] = new Illuminate\Support\ViewErrorBag;
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/solicitudes/cotizacion.blade.php')));
echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 1, 'myPurchaseOrdersCount' => 0])->render();
echo '<main class="admin-page sm:ml-44"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source, $data);
echo '</div></main>';
