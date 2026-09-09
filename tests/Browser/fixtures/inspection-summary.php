<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\InspectionSummary::seed();

$modal = Livewire\Livewire::test(App\Livewire\Oncologicos\InspeccionMezcla::class)
    ->call('abrirModalInspeccion', 1);
$render = static fn ($modal) => Livewire\Drawer\Utils::insertAttributesIntoHtmlRoot($modal->html(), [
    'wire:snapshot' => json_encode($modal->snapshot, JSON_THROW_ON_ERROR),
    'wire:effects' => json_encode(Illuminate\Support\Arr::except($modal->effects, ['html']), JSON_THROW_ON_ERROR),
]);
$states = ['summary' => $render($modal)];
$modal->call('guardarInspeccion');
$states['validation'] = $render($modal);
echo json_encode($states, JSON_THROW_ON_ERROR);
