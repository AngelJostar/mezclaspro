<?php

use App\Http\Controllers\Admin\CatalogoListasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\Fixtures\CatalogExportData;
use Tests\Fixtures\SupplyCatalog;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
CatalogExportData::seed();
URL::forceRootUrl('http://catalog.test');
$category = $argv[1];
$section = $argv[2] ?? 'diluyentes';
$request = Request::create('/', 'GET', ['tipo_insumo' => $section]);
$app->instance('request', $request);
$controller = app(CatalogoListasController::class);
$html = SupplyCatalog::render($controller->catalog($category)->getData());
$response = $controller->exportCatalog($request, $category);
$path = $response->getFile()->getPathname();
try {
    echo json_encode([
        'html' => $html,
        'disposition' => $response->headers->get('Content-Disposition'),
        'content' => base64_encode(file_get_contents($path)),
    ], JSON_THROW_ON_ERROR);
} finally {
    unlink($path);
}
