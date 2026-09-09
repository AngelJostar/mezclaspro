<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\PreparationWorkflow::seed();
Illuminate\Support\Facades\URL::forceRootUrl('http://preparation.test/mezclaspro/public');
$listUrl = route('admin.oncologicos.solicitudes.index');
$request = Illuminate\Http\Request::create(route('admin.oncologicos.mezclas.update', 1), 'PUT', [
    'accion' => 'preparada', 'return_to' => $listUrl,
]);
$app->instance('request', $request);
$renderList = fn () => view('livewire.oncologicos.solicitudes-table', [
    'mezclas' => App\Models\Oncologicos\Mezcla::with('solicitud.hospital.instituciones')->paginate(10),
    'sortField' => 'id', 'sortDirection' => 'asc',
])->render();
$before = $renderList();
$response = app(App\Http\Controllers\Admin\Oncologicos\MezclaController::class)->update($request, 1);
$layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
preg_match('/@if \(session\(\'swal\'\)\)(.*?)@endif/s', $layout, $matches);
$dialog = Illuminate\Support\Facades\Blade::render($matches[1]);
echo json_encode([
    'before' => $before, 'after' => $renderList().$dialog,
    'listUrl' => $listUrl, 'targetUrl' => $response->getTargetUrl(),
]);
