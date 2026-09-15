<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\SolicitudValidations::boot();
$section = $argv[1] ?? 'pending';
$sections = App\Support\AdministrationNavigation::billingSections(auth()->user());
$request = Illuminate\Http\Request::create(route($sections[$section]['route']));
app()->instance('request', $request);
echo Tests\Fixtures\BillingNavigation::view($section)->render();
