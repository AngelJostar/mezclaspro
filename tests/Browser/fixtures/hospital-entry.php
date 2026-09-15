<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
    'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\UnifiedRequestExportData::seed();
$request = Illuminate\Http\Request::create('http://localhost/admin/solicitudes');
$request->setUserResolver(fn () => $user);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.solicitudes.index'));
$app->instance('request', $request);
$data = app(App\Http\Controllers\Admin\UnifiedSolicitudController::class)->index($request)->getData();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/solicitudes/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 1, 'myPurchaseOrdersCount' => 0])->render();
echo '<main class="admin-page sm:ml-44"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source, $data);
echo '</div></main>';
