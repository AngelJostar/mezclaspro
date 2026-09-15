<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
Illuminate\Support\Facades\DB::purge('sqlite');
Tests\Fixtures\SolicitudValidations::boot();

echo view('layouts.includes.admin.aside', [
    'pendingSolicitudesCount' => 8,
    'myPurchaseOrdersCount' => 0,
])->render();
