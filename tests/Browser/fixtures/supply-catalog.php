<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Fixtures\SupplyCatalog::seed();
Illuminate\Support\Facades\URL::forceRootUrl('http://catalog.test');
echo Tests\Fixtures\SupplyCatalog::render(Tests\Fixtures\SupplyCatalog::viewData($argv[1] ?? null));
