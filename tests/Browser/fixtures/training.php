<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
foreach (['2014_10_12_000000_create_users_table.php', '2024_04_11_013606_create_permission_tables.php',
    '2025_11_06_183220_create_laboratories_table.php', '2026_08_20_000006_create_personnel_profiles_table.php',
    '2026_10_23_000001_create_training_program_catalog.php', '2026_10_23_000002_add_training_content.php'] as $file) (require database_path('migrations/'.$file))->up();
$user = App\Models\User::create(['name' => 'Admin', 'lastname' => 'Prueba', 'username' => 'prueba', 'password' => bcrypt('prueba'), 'is_active' => true]);
$user->assignRole(Spatie\Permission\Models\Role::findOrCreate('Admin'));
Illuminate\Support\Facades\Auth::login($user);
$type = $argv[1] ?? 'programas';
foreach (json_decode($argv[2] ?? '[]', true) as $id) App\Models\TrainingProgram::whereKey($id)->delete();
foreach (json_decode($argv[3] ?? '[]', true) as $record) App\Models\TrainingProgram::updateOrCreate(['id' => $record['id']], $record);
if ($type === 'catalog') {
    echo app(App\Http\Controllers\Admin\TrainingPersonnelController::class)->trainingPrograms()->getContent();
    exit;
}
$request = Illuminate\Http\Request::create('http://training.test/admin/capacitaciones/'.$type);
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.capacitaciones.'.$type));
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
Illuminate\Support\Facades\URL::forceRootUrl('http://training.test');
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/capacitaciones/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')\n@stack('js')", [
    'persistedPersonnel' => [], 'isSuperAdmin' => false, 'jobCatalog' => ['Calidad' => ['Verificador']],
    'laboratories' => collect(), 'errors' => new Illuminate\Support\ViewErrorBag,
]);
