<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
foreach (['2026_09_15_130000_create_hospital_conciliation_submissions.php', '2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php',
    '2026_10_06_180000_add_direction_to_hospital_conciliation_submissions.php'] as $migration) (require database_path('migrations/'.$migration))->up();
$pricing = Mockery::mock(App\Services\InstitutionBillingPricingService::class);
$pricing->shouldReceive('priceOncoMix', 'priceNutritionRequest')->andReturn(['total_iva_included' => 200.25]);
$app->instance(App\Services\InstitutionBillingPricingService::class, $pricing);
$user = App\Models\User::findOrFail(2);
$user->syncRoles(Spatie\Permission\Models\Role::findOrCreate('Super Admin', 'web'));
Illuminate\Support\Facades\Auth::setUser($user);
parse_str($argv[1] ?? '', $query);
$request = Illuminate\Http\Request::create('https://conciliation.test/admin/instituciones-reportes', 'GET', ['seccion' => 'conciliacion'] + $query);
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.instituciones.reportes'));
$app->instance('request', $request);
Illuminate\Support\Facades\URL::forceRootUrl('https://conciliation.test');
Illuminate\Support\Facades\URL::forceScheme('https');
$view = app(App\Http\Controllers\Admin\ConciliationSubmissionController::class)->index($request);
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/instituciones/conciliacion/index.blade.php')));
echo '<main class="admin-page"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')", $view->getData());
echo '</div></main>';
