<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\InspectionWorkflow::seed();

$modal = Livewire\Livewire::test(App\Livewire\Oncologicos\InspeccionMezcla::class)
    ->call('abrirModalInspeccion', 1)->set('observaciones', 'Observaciones sin guardar')
    ->call('rechazarInspeccion');
$states = ['reason' => $modal->html()];
$modal->call('guardarRechazo');
$states['error'] = $modal->html();
$modal->set('motivoRechazo', 'Fuga detectada en el contenedor')->call('guardarRechazo');
$states['success'] = $modal->html();
echo json_encode($states, JSON_THROW_ON_ERROR);
