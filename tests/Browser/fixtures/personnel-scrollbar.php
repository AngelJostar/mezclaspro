<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array']);
$request = Illuminate\Http\Request::create('http://localhost/admin/capacitaciones/personal');
$request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('admin.capacitaciones.personal'));
$app->instance('request', $request);

$personnel = [];
for ($index = 1; $index <= (int) ($argv[1] ?? 20); $index++) {
    $personnel[] = ['name' => 'Persona de prueba '.$index, 'initials' => 'PP', 'avatar' => 'sky',
        'positions' => [['name' => 'Vendedor', 'current' => true]], 'hireDate' => '01/09/2026',
        'completedPrograms' => [], 'currentPrograms' => [], 'examScores' => [],
        'department' => 'Ventas', 'employmentStatus' => 'hired', 'activity' => '22/09/2026 10:00'];
}
if (($argv[2] ?? '') === 'filters') {
    $personnel[0] = array_replace($personnel[0], [
        'name' => 'Alvaro 10', 'hireDate' => '02/01/2026', 'department' => 'Calidad',
        'positions' => [['name' => 'Verificador', 'current' => true]],
        'examScores' => [['exam' => 'Seguridad', 'score' => 80]],
        'activity' => '02/09/2026 09:00', 'completedPrograms' => ['Seguridad', 'Induccion'],
    ]);
    $personnel[1] = array_replace($personnel[1], [
        'name' => 'Alvaro 2', 'hireDate' => '15/12/2025', 'department' => 'Calidad',
        'positions' => [['name' => 'Verificador', 'current' => true]],
        'employmentStatus' => 'inactive', 'examScores' => [['exam' => 'Seguridad', 'score' => 100]],
        'activity' => '30/08/2026 10:00', 'completedPrograms' => ['Induccion'],
    ]);
    $personnel[2] = array_replace($personnel[2], [
        'name' => 'Brenda Perez', 'hireDate' => 'Sin fecha',
        'activity' => 'Sin actividad',
    ]);
}
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/capacitaciones/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source."\n@stack('css')\n@stack('js')", [
    'persistedPersonnel' => $personnel, 'isSuperAdmin' => false, 'jobCatalog' => ['Ventas' => ['Vendedor']],
    'laboratories' => collect(), 'errors' => new Illuminate\Support\ViewErrorBag,
]);
