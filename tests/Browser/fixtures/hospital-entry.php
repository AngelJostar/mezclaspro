<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
app('session')->start();
$user = Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
if (($argv[4] ?? '') === 'quick-filters') {
    Tests\Fixtures\HospitalInvoiceData::seed();
    Illuminate\Support\Facades\DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
    foreach ([20 => 'signing', 21 => 'delivered'] as $id => $status) {
        $billing = App\Models\InstitutionBilling::where('origen_tipo', 'oncologica_mezcla')->where('origen_id', $id)->firstOrFail();
        App\Models\HospitalInvoiceAccount::updateOrCreate(['invoice_key' => App\Services\HospitalInvoiceLedger::key($billing)], [
            'hospital_id' => 1, 'institucion_id' => 1, 'document_status' => $status,
        ]);
    }
}
$toolsPage = ($argv[1] ?? '') === 'herramientas';
$query = $toolsPage ? array_merge(['desde' => '2026-09-01', 'hasta' => '2026-09-30', 'tab' => $argv[2] ?? 'conciliacion'], json_decode($argv[3] ?? '{}', true, 512, JSON_THROW_ON_ERROR)) : [];
$request = Illuminate\Http\Request::create('http://localhost/admin/'.($toolsPage ? 'herramientas' : 'solicitudes'), 'GET', $query);
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName($toolsPage ? 'admin.hospital.herramientas' : 'admin.solicitudes.index'));
$app->instance('request', $request);
$data = app($toolsPage ? App\Http\Controllers\Admin\HospitalToolsController::class : App\Http\Controllers\Admin\UnifiedSolicitudController::class)->index($request)->getData();
if ($toolsPage && ($argv[4] ?? '') === 'export-conciliation') {
    echo Maatwebsite\Excel\Facades\Excel::raw(new App\Exports\HospitalConciliationExport(collect($data['rows']->items())), Maatwebsite\Excel\Excel::XLSX);
    exit;
}
$data['errors'] = new Illuminate\Support\ViewErrorBag();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path($toolsPage
    ? 'views/admin/hospital/herramientas.blade.php' : 'views/admin/solicitudes/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 1, 'myPurchaseOrdersCount' => 0])->render();
echo '<main class="admin-page sm:ml-44"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source, $data);
echo '</div></main>';
