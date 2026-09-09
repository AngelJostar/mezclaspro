<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\InspectionWorkflow::seed();

$category = ($argv[1] ?? '') === 'antibioticos' ? 'antibioticos' : 'oncologicos';
Illuminate\Support\Facades\DB::table('solicitud_oncos')->where('id', 1)->update(['tipo_solicitud' => $category]);
foreach (['pendiente', 'aprobada', 'dispensada', 'preparada', 'revisada', 'cancelada'] as $index => $status) {
    Illuminate\Support\Facades\DB::table('mezclas')->updateOrInsert(['id' => $index + 1], [
        'solicitud_id' => 1, 'estado' => $status, 'lote' => 'PRUEBA-'.($index + 1),
    ]);
}
echo view('livewire.oncologicos.solicitudes-table', [
    'mezclas' => App\Models\Oncologicos\Mezcla::with('solicitud.hospital.instituciones')->orderBy('id')->paginate(10),
    'sortField' => 'id', 'sortDirection' => 'asc',
])->render();
