<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Fixtures\CatalogAvailabilityData::seed();
Illuminate\Support\Facades\Auth::setUser(Tests\Fixtures\CatalogAvailabilityData::user());
Illuminate\Support\Facades\URL::forceRootUrl('http://catalog.test');
$data = app(App\Http\Controllers\Admin\CatalogoListasController::class)->catalog($argv[1] ?? 'oncologicos')->getData();
echo Tests\Fixtures\SupplyCatalog::render($data);
