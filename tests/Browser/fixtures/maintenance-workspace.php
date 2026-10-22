<?php
require 'vendor/autoload.php';
$app=require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','session.driver'=>'array','cache.default'=>'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$fixture=Tests\Fixtures\MaintenanceWorkspace::seed();
$pages=[];
foreach (['calendario','servicios','proveedores','programacion'] as $section) {
    foreach ($section==='calendario' ? ['semana','mes','lista'] : ['semana'] as $mode) {
        $request=Illuminate\Http\Request::create('http://maintenance.test/admin/mantenimiento-calificaciones','GET',['seccion'=>$section,'vista'=>$mode,'fecha'=>'2026-10-21']);
        $request->setLaravelSession(session()->driver());
        $app->instance('request',$request);
        $pages[$section.'-'.$mode]=app(App\Http\Controllers\Admin\MaintenanceWorkspaceController::class)->index($request)->render();
    }
}
echo json_encode($pages);
