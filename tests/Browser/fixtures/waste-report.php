<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\InspectionWorkflow::seed();
(require database_path('migrations/2026_09_08_000003_create_ai_agents_table.php'))->up();
Spatie\Permission\Models\Role::create(['name' => 'Admin', 'guard_name' => 'web']);
foreach ([90, 200] as $index => $dose) {
    App\Models\InspectionWaste::create([
        'mezcla_id' => $index + 1, 'production_attempt' => 1, 'reason' => 'Fuga detectada',
        'snapshot' => [
            'category' => 'oncologicos', 'mixture' => ['lote' => $index === 0 ? 'LOTE-A' : 'LOTE-B'],
            'medications' => [['denominacion_snapshot' => 'Medicamento de prueba', 'dosis' => $dose]],
        ],
    ]);
}
$request = Illuminate\Http\Request::create('/admin/superadministrador');
$app->instance('request', $request);
$data = app(App\Http\Controllers\Admin\SuperAdministratorController::class)->index($request)->getData();
$base = $data['wasteRecords']->first();
foreach ([
    ['remanente', 'Oncológico', ['mg' => 10], 'LOTE-A'],
    ['remanente', 'Nutricional', ['mL' => 20], 'LOTE-B'],
    ['frasco', 'Oncológico', ['frascos' => 2, 'mL' => 50], 'LOTE-A'],
] as $index => [$type, $area, $units, $lot]) {
    $data['wasteRecords']->push(array_replace($base, [
        'id' => 'extra-'.$index, 'type' => $type, 'type_label' => 'Merma de '.$type, 'product' => 'Producto '.$index,
        'area' => $area, 'units' => $units, 'lot' => $lot, 'inspection_destination' => null, 'inspection_materials' => [],
    ]));
}
foreach (['all', 'remanente', 'frasco', 'inspeccion'] as $type) {
    $rows = $type === 'all' ? $data['wasteRecords'] : $data['wasteRecords']->where('type', $type);
    $data['wasteTotals'][$type] = App\Support\WasteReportUnits::sum($rows->pluck('units'));
    $data['wasteSummary'][$type] = $rows->count();
}
$data['wasteAuthorizationRequests'] = collect([1, 2])->map(function ($id) {
    $request = new App\Models\WasteAuthorizationRequest([
        'domain' => 'oncologico', 'quantity_containers' => $id, 'quantity_ml' => $id * 10,
        'snapshot_product' => 'Producto solicitado '.$id, 'status' => 'rejected', 'reason' => 'Solicitud de prueba',
    ]);
    $request->id = $id;
    $request->setRelations(['requester' => null, 'reviewer' => null]);
    return $request;
});
$data['requestedUnitTotals'] = App\Support\WasteReportUnits::sum([['frascos' => 3, 'mL' => 30]]);
$data['errors'] = new Illuminate\Support\ViewErrorBag();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], ['', '@stack("js")'],
    file_get_contents(resource_path('views/admin/superadministrator/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, $data);
