<?php

// This preview only uses an in-memory database and synthetic requests.
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
foreach (['2026_09_15_130000_create_hospital_conciliation_submissions.php', '2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php',
    '2026_10_06_180000_add_direction_to_hospital_conciliation_submissions.php',
    '2026_10_06_190000_create_conciliation_periods.php'] as $migration) (require database_path('migrations/'.$migration))->up();
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
$input = json_decode($argv[2] ?? '{}', true) + ['seccion' => 'conciliacion', 'modalidad' => 'periodo', 'desde' => '2026-09-01', 'hasta' => '2026-10-06'];
$request = Illuminate\Http\Request::create('https://conciliation.test/admin/instituciones-reportes', $action === 'accept' ? 'POST' : 'GET', $input);
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.instituciones.reportes'));
$app->instance('request', $request);
Illuminate\Support\Facades\URL::forceRootUrl('https://conciliation.test');
Illuminate\Support\Facades\URL::forceScheme('https');
$controller = app(App\Http\Controllers\Admin\ConciliationPeriodController::class);
if ($action !== 'page') {
    echo $controller->{$action}($request)->getContent();
    exit;
}
$view = $controller->index($request);
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/instituciones/conciliacion/period.blade.php')));
echo '<main class="admin-page"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')", $view->getData() + ['errors' => new Illuminate\Support\ViewErrorBag]);
echo '</div></main>';
