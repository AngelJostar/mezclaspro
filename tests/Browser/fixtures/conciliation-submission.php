<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$hospital = Tests\Fixtures\UnifiedRequestExportData::seed();
Tests\Fixtures\HospitalToolsData::addBilling();
(require database_path('migrations/2026_09_15_130000_create_hospital_conciliation_submissions.php'))->up();
(require database_path('migrations/2026_09_15_150000_add_confirmation_to_hospital_conciliation_submissions.php'))->up();
$pricing = Mockery::mock(App\Services\InstitutionBillingPricingService::class);
$pricing->shouldReceive('priceOncoMix')->andReturn(['total_iva_included' => 200.25]);
$app->instance(App\Services\InstitutionBillingPricingService::class, $pricing);
Illuminate\Support\Facades\DB::table('institution_billings')->where('origen_tipo', 'oncologica_mezcla')->where('origen_id', 1)->update(['conciliable' => 'No']);
$send = Illuminate\Http\Request::create('http://localhost/admin/herramientas/conciliacion/enviar', 'POST', [
    'desde' => '2026-09-01', 'hasta' => '2026-09-30', 'submission_key' => (string) Illuminate\Support\Str::uuid(),
]);
$send->setUserResolver(fn () => $hospital);
if (($argv[1] ?? '') === 'preview') $send->merge(json_decode($argv[2] ?? '{}', true));
$controller = app(App\Http\Controllers\Admin\HospitalToolsController::class);
$summary = app(App\Services\HospitalConciliationSummary::class);
$preview = $controller->previewConciliation($send, $summary)->getData(true);
if (($argv[1] ?? '') === 'preview') { echo json_encode($preview, JSON_THROW_ON_ERROR); exit; }
$send->merge(['confirmation_token' => $preview['confirmation_token'], 'reasons' => ['oncologicos-1' => 'Registro pendiente de revision']]);
$controller->sendConciliation($send, $summary);
if (($argv[3] ?? '') === 'many') {
    $submission = App\Models\HospitalConciliationSubmission::sole();
    $snapshot = $submission->snapshot;
    $prototype = collect($snapshot)->firstWhere('conciliable', true);
    foreach (range(100, 112) as $id) {
        $row = $prototype; $row['id'] = $id; $row['cells']['id'] = (string) $id;
        $row['cells']['patient'] = 'Paciente filtro '.$id; $row['cells']['request_id'] = 'SOL-'.$id;
        $snapshot[] = $row;
    }
    $submission->update(['snapshot' => $snapshot, 'mixture_count' => 17, 'conciliable_count' => 16]);
}
if (($argv[1] ?? '') === 'filters') {
    Illuminate\Support\Facades\DB::table('clientes')->insert([
        ['id' => 2, 'nombre' => 'Otra institucion'], ['id' => 3, 'nombre' => 'Institucion sin hospitales'],
    ]);
    Illuminate\Support\Facades\DB::table('hospitals')->insert(['id' => 3, 'name' => 'Segundo hospital']);
    Illuminate\Support\Facades\DB::table('cliente_hospital')->insert([
        ['cliente_id' => 2, 'hospital_id' => 2], ['cliente_id' => 1, 'hospital_id' => 3],
    ]);
    $original = App\Models\HospitalConciliationSubmission::sole();
    foreach ([2 => 'Hospital ajeno', 3 => 'Segundo hospital'] as $id => $name) {
        $copy = $original->replicate();
        $copy->submission_key = (string) Illuminate\Support\Str::uuid();
        $copy->hospital_id = $id; $copy->hospital_name = $name; $copy->snapshot = [];
        $copy->save();
    }
}
$user = App\Models\User::findOrFail(2);
$user->syncRoles(Spatie\Permission\Models\Role::findOrCreate('Super Admin', 'web'));
Illuminate\Support\Facades\Auth::setUser($user);
$detail = in_array($argv[1] ?? '', ['detail', 'summary'], true);
$request = Illuminate\Http\Request::create('http://localhost/admin/instituciones-reportes', 'GET', ['seccion' => 'conciliacion'] + json_decode($argv[2] ?? '{}', true));
$request->setUserResolver(fn () => $user);
if (($argv[1] ?? '') === 'summary') $request->headers->set('Accept', 'application/json');
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.instituciones.reportes'));
$app->instance('request', $request);
if (in_array($argv[1] ?? '', ['agent-info', 'agent-run'], true)) {
    foreach (['2026_09_08_000003_create_ai_agents_table.php', '2026_09_11_000001_add_is_active_to_ai_agents_table.php', '2026_09_14_000005_add_agent_execution.php', '2026_09_16_000001_add_integration_key_to_ai_agents.php'] as $migration) (require database_path('migrations/'.$migration))->up();
    if (!Illuminate\Support\Facades\Schema::hasColumn('hospitals', 'laboratory_id')) Illuminate\Support\Facades\Schema::table('hospitals', fn ($table) => $table->unsignedBigInteger('laboratory_id')->nullable());
    (new Database\Seeders\ConciliationAgentSeeder)->run();
    $controller = app(App\Http\Controllers\Admin\ConciliationAgentController::class);
    echo ($argv[1] === 'agent-info' ? $controller->show($request) : $controller->run($request, app(App\Services\Agents\AgentRunner::class)))->getContent();
    exit;
}
$controller = app(App\Http\Controllers\Admin\ConciliationSubmissionController::class);
$view = $detail ? $controller->show($request, App\Models\HospitalConciliationSubmission::sole()) : $controller->index($request);
if (($argv[1] ?? '') === 'summary') { echo $view->getContent(); exit; }
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/instituciones/conciliacion/'.($detail ? 'show' : 'index').'.blade.php')));
echo Illuminate\Support\Facades\Blade::render('<nav class="corporate-header"><x-corporate-brand /></nav>');
echo view('layouts.includes.admin.aside', ['pendingSolicitudesCount' => 1, 'myPurchaseOrdersCount' => 0, 'billingDueCounts' => ['yellow' => 0, 'red' => 0]])->render();
echo '<main class="admin-page sm:ml-44"><div class="admin-content">';
echo Illuminate\Support\Facades\Blade::render($source, $view->getData());
echo '</div></main>';
