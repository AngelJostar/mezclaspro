<?php

// This preview only uses an in-memory database and synthetic requests.
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
foreach (['2026_08_19_000001_add_compensation_fields_to_institution_billings_table.php',
    '2026_09_15_130000_create_hospital_conciliation_submissions.php', '2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php',
    '2026_10_06_180000_add_direction_to_hospital_conciliation_submissions.php',
    '2026_10_06_190000_create_conciliation_periods.php',
    '2026_10_07_120000_add_creation_batch_to_conciliation_periods.php',
    '2026_10_07_150000_create_conciliation_period_decisions.php',
    '2026_10_07_160100_add_request_version_to_period_decisions.php'] as $migration) (require database_path('migrations/'.$migration))->up();
$pricing = Mockery::mock(App\Services\InstitutionBillingPricingService::class);
$pricing->shouldReceive('priceOncoMix')->andReturn(['total_iva_included' => 200.25, 'lines' => [['description' => 'Medicamento oncológico', 'quantity' => 2, 'unit_price' => 100.125, 'total_with_vat' => 200.25]]]);
$pricing->shouldReceive('priceNutritionRequest')->andReturn(['total_iva_included' => 300.50]);
$app->instance(App\Services\InstitutionBillingPricingService::class, $pricing);
Illuminate\Support\Facades\DB::table('mezclas')->whereIn('id', [1, 2, 4])->update(['remision' => Illuminate\Support\Facades\DB::raw("'REM-' || id")]);
Illuminate\Support\Facades\DB::table('mezclas')->where('id', 2)->update(['fecha_entrega' => '2026-10-06 18:00:00']);
Illuminate\Support\Facades\DB::table('solicituds')->where('id', 11)->update(['remision' => 'NPT-11']);
$user = App\Models\User::findOrFail(2);
$user->syncRoles(Spatie\Permission\Models\Role::findOrCreate('Super Admin', 'web'));
Illuminate\Support\Facades\Auth::setUser($user);
foreach (json_decode($argv[3] ?? '{}', true) as $key => $yes) {
    [$kind, $id] = explode('-', $key);
    App\Models\InstitutionBilling::updateOrCreate(['origen_tipo' => $kind === 'nutricionales' ? 'nutricional_solicitud' : 'oncologica_mezcla', 'origen_id' => $id], ['hospital_id' => 1, 'institucion_id' => 1, 'conciliable' => $yes ? 'Si' : 'No']);
}
$action = $argv[1] ?? 'page';
foreach (json_decode($argv[4] ?? '[]', true) as $decision) {
    App\Models\ConciliationPeriodDecision::create($decision + ['user_id' => $user->id, 'user_name' => $user->name, 'snapshot' => []]);
}
$input = json_decode($argv[2] ?? '{}', true) + ['seccion' => 'conciliacion', 'modalidad' => 'periodo', 'desde' => '2026-09-01', 'hasta' => '2026-10-06'];
$request = Illuminate\Http\Request::create('https://conciliation.test/admin/instituciones-reportes', in_array($action, ['accept', 'authorizeChanges']) ? 'POST' : 'GET', $input);
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.instituciones.reportes'));
$app->instance('request', $request);
Illuminate\Support\Facades\URL::forceRootUrl('https://conciliation.test');
Illuminate\Support\Facades\URL::forceScheme('https');
$controller = app(App\Http\Controllers\Admin\ConciliationPeriodController::class);
if (in_array($action, ['clientPage', 'clientForm'])) {
    $group = app(App\Services\ConciliationPeriodData::class)->groups(['institucion_id' => 1, 'hospital_id' => 1, 'desde' => '2026-09-01', 'hasta' => '2026-09-30'])->first();
    $submission = App\Models\HospitalConciliationSubmission::create([
        'submission_key' => (string) Illuminate\Support\Str::uuid(), 'direction' => 'sent',
        'hospital_id' => 1, 'hospital_name' => $group['hospital'], 'submitted_by' => $user->id, 'sender_name' => $user->name,
        'period_from' => $group['from'], 'period_to' => $group['to'], 'filters' => ['institucion_id' => 1],
        'mixture_count' => count($group['rows']), 'conciliable_count' => count($group['rows']), 'snapshot' => App\Services\ConciliationPeriodData::snapshot($group),
    ]);
    $client = App\Models\User::findOrFail(1);
    Illuminate\Support\Facades\Auth::setUser($client);
    $request->setUserResolver(fn () => $client);
    $request->merge(['seccion' => 'conciliacion']);
    $clientController = app(App\Http\Controllers\Admin\ClientToolsController::class);
    if ($action === 'clientForm') { echo $clientController->changeRequest($request, $submission)->getContent(); exit; }
    $view = $clientController->index($request);
    $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/herramientas/index.blade.php')));
    echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')", $view->getData() + ['errors' => new Illuminate\Support\ViewErrorBag]);
    exit;
}
if ($action !== 'page') {
    echo $controller->{$action}($request)->getContent();
    exit;
}
$view = $controller->index($request);
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/instituciones/conciliacion/period.blade.php')));
echo '<main class="admin-page"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')", $view->getData() + ['errors' => new Illuminate\Support\ViewErrorBag]);
echo '</div></main>';
