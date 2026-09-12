<?php

use App\Http\Controllers\Admin\Nutricionales\SolicitudController as NutritionController;
use App\Http\Controllers\Admin\Oncologicos\SolicitudController as OncologyController;
use App\Models\Nutricionales\Solicitud as NutritionRequest;
use App\Models\Oncologicos\SolicitudOnco;
use App\Services\InstitutionBillingPricingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$outputDirectory = dirname(__DIR__, 2).'/output/pdf';
File::ensureDirectoryExists($outputDirectory);

$pricing = app(InstitutionBillingPricingService::class);
$target = $argv[1] ?? 'all';
$generated = [];

if (in_array($target, ['all', 'onco', 'onco-mg'], true)) {
    $isMilligram = $target === 'onco-mg';
    $onco = SolicitudOnco::query()
        ->where('remision', $isMilligram ? 'PR-ONCO-MG-20260912' : 'PR-ONCO-F-20260912')
        ->firstOrFail();
    $mixtureId = (int) $onco->mezclas()->value('id');
    $oncoResponse = app(OncologyController::class)->remision(
        $onco,
        $pricing,
        Request::create('/prueba/remision-onco', 'GET', ['mezcla' => $mixtureId])
    );
    $oncoPath = $outputDirectory.'/remision-prueba-onco-'.($isMilligram ? 'mg' : 'frasco').'.pdf';
    file_put_contents($oncoPath, $oncoResponse->getContent());
    $generated['oncologia'] = $oncoPath;
}

if (in_array($target, ['all', 'nutri', 'nutri-ml'], true)) {
    $isMilliliter = $target === 'nutri-ml';
    $nutrition = NutritionRequest::query()
        ->where('remision', $isMilliliter ? 'PR-NUTRI-ML-20260912' : 'PR-NUTRI-F-20260912')
        ->firstOrFail();
    $nutritionResponse = app(NutritionController::class)->remision($nutrition, $pricing);
    $nutritionPath = $outputDirectory.'/remision-prueba-nutri-'.($isMilliliter ? 'ml' : 'frasco').'.pdf';
    file_put_contents($nutritionPath, $nutritionResponse->getContent());
    $generated['nutricion'] = $nutritionPath;
}

echo json_encode($generated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL;
