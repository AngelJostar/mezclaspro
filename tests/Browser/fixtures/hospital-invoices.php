<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','session.driver'=>'array','cache.default'=>'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Carbon::setTestNow('2026-09-15 12:00:00');
$user = Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
Tests\Fixtures\HospitalInvoiceData::seed();
$detail = ($argv[1] ?? '') === 'detail';
$query = json_decode($argv[2] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
$request = Illuminate\Http\Request::create('http://localhost/admin/herramientas', 'GET', array_merge(['tab'=>'facturacion','desde'=>'2026-08-01','hasta'=>'2026-09-15'], $query));
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName($detail ? 'admin.hospital.facturacion.detalle' : 'admin.hospital.herramientas'));
$app->instance('request', $request);
$controller = app(App\Http\Controllers\Admin\HospitalToolsController::class);
$data = ($detail ? $controller->invoiceDetail($request, $query['invoice']) : $controller->index($request))->getData();
$data['errors'] = new Illuminate\Support\ViewErrorBag();
$source = str_replace(['<x-admin-layout>','</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/hospital/'.($detail ? 'invoice-detail' : 'herramientas').'.blade.php')));
if (!$detail) {
    echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
    echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount'=>1,'myPurchaseOrdersCount'=>0])->render();
}
echo '<main class="admin-page '.($detail ? 'workflow-page' : 'sm:ml-44').'"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source,$data);
echo '</div></main>';
if (!$detail) echo view('layouts.includes.workflow-modal')->render();
echo '<script type="application/json" id="workflow-page-config">'.json_encode(['embedded'=>$detail,'completed'=>false]).'</script>';
