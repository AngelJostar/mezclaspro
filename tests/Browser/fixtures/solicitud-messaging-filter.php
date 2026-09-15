<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
$user = Tests\Fixtures\UnifiedRequestExportData::seed();
(require database_path('migrations/2026_09_14_000007_create_mixture_messages.php'))->up();
foreach (['nutricionales' => 11, 'oncologicos' => 1, 'antibioticos' => 2] as $kind => $id) {
    for ($i = 0; $i < 2; $i++) {
        App\Models\MixtureMessage::create(['kind' => $kind, 'target_id' => $id, 'hospital_id' => 1,
            'author_id' => 1, 'author_name' => 'Hospital de prueba', 'sender_side' => 'hospital',
            'body' => 'Mensaje de prueba', 'client_token' => (string) Illuminate\Support\Str::uuid()]);
    }
}
$request = Illuminate\Http\Request::create('http://localhost/admin/solicitudes', 'GET', ['estado' => $argv[1] ?? 'todas']);
$request->setUserResolver(fn () => $user);
$app->instance('request', $request);
$data = app(App\Http\Controllers\Admin\UnifiedSolicitudController::class)->index($request)->getData();
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/solicitudes/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, $data);
