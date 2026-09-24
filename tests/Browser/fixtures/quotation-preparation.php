<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\QuotationPreparationData::seed();
$category = $argv[1] ?? 'oncologicos';
$unit = $argv[2] ?? ($category === 'nutricionales' ? 'ml' : 'mg');
$count = (int) ($argv[3] ?? 1);
$quote = ($argv[4] ?? '') === 'presentations'
    ? Tests\Fixtures\QuotationPreparationData::presentationGroups($category, $unit)
    : Tests\Fixtures\QuotationPreparationData::quotation($category, $unit, $count, null, $count > 1);
$request = Illuminate\Http\Request::create(route('admin.solicitudes.cotizacion.preparation', $quote));
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.solicitudes.cotizacion.preparation'));
$app->instance('request', $request);
$view = app(App\Http\Controllers\Admin\QuotationPreparationController::class)->create($request, $quote, app(App\Services\QuotationPreparationService::class));
$data = $view->getData();
$data['errors'] = new Illuminate\Support\ViewErrorBag;
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents($view->getPath()));
echo '<main class="admin-page"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source, $data);
echo '</div></main>';
