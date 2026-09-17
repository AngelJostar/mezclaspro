<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
Tests\Fixtures\HospitalToolsData::addLogCases();
$history = ($argv[1] ?? '') === 'history';
$query = json_decode($argv[2] ?? '{}', true, flags: JSON_THROW_ON_ERROR);
$request = Illuminate\Http\Request::create('http://localhost/admin/herramientas', 'GET', array_merge(['tab' => 'ajustes'], $query));
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName($history ? 'admin.hospital.ajustes.historial' : 'admin.hospital.herramientas'));
$app->instance('request', $request);
$controller = app(App\Http\Controllers\Admin\HospitalToolsController::class);
$data = ($history ? $controller->adjustmentHistory($request, 'oncologicos', (int) ($query['target'] ?? 1)) : $controller->index($request))->getData();
if (($argv[1] ?? '') === 'export') {
    echo Maatwebsite\Excel\Facades\Excel::raw(new App\Exports\HospitalAdjustmentLogExport(collect($data['rows']->items())), Maatwebsite\Excel\Excel::XLSX);
    exit;
}
$data['errors'] = new Illuminate\Support\ViewErrorBag();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path(
    'views/admin/hospital/'.($history ? 'adjustment-history' : 'herramientas').'.blade.php')));
if (! $history) {
    echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
    echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 1, 'myPurchaseOrdersCount' => 0])->render();
}
echo '<main class="admin-page '.($history ? 'workflow-page' : 'sm:ml-44').'"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source, $data);
echo '</div></main>';
if (! $history) {
    echo view('layouts.includes.workflow-modal')->render();
    echo view('admin.solicitudes._messages-dialog')->render();
}
echo '<script type="application/json" id="workflow-page-config">'.json_encode(['embedded' => $history, 'completed' => false]).'</script>';
