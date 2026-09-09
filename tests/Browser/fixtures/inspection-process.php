<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\InspectionWorkflow::seed();

$status = in_array($argv[1] ?? '', ['dispensada', 'preparada', 'revisada'], true) ? $argv[1] : 'aprobada';
Illuminate\Support\Facades\DB::table('solicitud_oncos')->where('id', 1)->update(['estado' => $status]);
Illuminate\Support\Facades\DB::table('mezclas')->where('id', 1)->update(['estado' => $status]);
Illuminate\Support\Facades\DB::table('mezclas')->insert([
    'id' => 2, 'solicitud_id' => 1, 'estado' => $status, 'lote' => 'REPROCESO-02', 'production_attempt' => 2,
]);
echo view('livewire.oncologicos.solicitudes-table', [
    'mezclas' => App\Models\Oncologicos\Mezcla::with('solicitud.hospital.instituciones')->orderBy('id')->paginate(10),
    'sortField' => 'id', 'sortDirection' => 'asc',
])->render();
