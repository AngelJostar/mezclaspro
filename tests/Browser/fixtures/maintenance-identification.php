<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
$request = Illuminate\Http\Request::create('http://maintenance.test/');
$request->setLaravelSession(session()->driver());
$app->instance('request', $request);
$months = array_map(fn ($month) => ['key' => $month, 'label' => 'Mes '.$month], range(1, 12));
$row = [
    'id' => 1, 'frequency' => 'ANUAL', 'service' => 'Calibracion de manometros diferenciales Oncologicos',
    'quantity' => 10, 'identification' => "ONC: IM-02, IM-03, IM-04\nIM-05, IM-06, IM-07, IM-08, IM-09, IM-10, IM-11",
    'provider' => 'Proveedor Uno', 'unit_price' => 1187.72, 'one_time' => null, 'months' => [], 'total' => 11877.2,
];
$calendar = [
    'error' => null, 'year' => 2026, 'year_total' => 11877.2, 'scheduled_count' => 1,
    'monthly_summary' => [], 'service_count' => 3, 'provider_count' => 1,
    'provider_summary' => [], 'frequency_summary' => [], 'months' => $months,
    'rows' => [
        $row,
        array_merge($row, ['id' => 2, 'service' => 'Servicio sin identificacion', 'identification' => '']),
        array_merge($row, ['id' => 3, 'service' => 'Servicio extenso <prueba>', 'identification' => str_repeat("Equipo <script>alert(1)</script> & \"Norte\"\n", 80)]),
    ],
];
$laboratories = collect();
$selectedLaboratory = null;
$errors = new Illuminate\Support\ViewErrorBag();
// Render the real page with synthetic records and no database-backed navigation.
$source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], ['', "@stack('css') @stack('js')"],
    file_get_contents(resource_path('views/admin/maintenance-qualifications/index.blade.php')));
echo Illuminate\Support\Facades\Blade::render($source, compact('calendar', 'laboratories', 'selectedLaboratory', 'errors'));
