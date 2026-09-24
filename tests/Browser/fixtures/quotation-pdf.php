<?php

use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\RequestQuotation;
use App\Services\RequestQuotationPdf;

require __DIR__.'/../../../vendor/autoload.php';
$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$directory = $argv[1] ?? '';
if (!is_dir($directory)) throw new RuntimeException('An existing QA output directory is required.');

$quotation = new RequestQuotation;
$quotation->forceFill(['id' => 1, 'created_at' => '2026-09-23 10:00:00', 'total' => 351672,
    'patient_name' => 'PRIVATE PATIENT', 'clinical_data' => ['diagnosis' => 'PRIVATE DIAGNOSIS']]);
$quotation->setRelation('hospital', new Hospital(['name' => "Hospital \u{00c1}ngeles Metropolitano"]));
$quotation->setRelation('institution', new Institucion(['nombre' => 'Institucion de ejemplo']));
$medication = ['description' => 'ENHERTU 100 MG', 'presentation' => 'SOL INY FAM CAJ C/1',
    'quantity' => 4, 'unit' => 'frasco', 'mixtures' => 1, 'unit_price' => 87808, 'vat' => 0, 'total' => 351232];
$service = ['description' => "Servicio de preparaci\u{00f3}n", 'unit' => 'servicio', 'quantity' => 1,
    'unit_price' => 440, 'vat' => 0, 'total' => 440];
$quotation->pricing_snapshot = ['lines' => [$medication, $service]];
file_put_contents($directory.'/quotation-pdf-single.pdf', app(RequestQuotationPdf::class)->render($quotation));

$lines = [];
for ($i = 1; $i <= 40; $i++) {
    $lines[] = array_replace($medication, ['description' => 'Medicamento de prueba '.$i,
        'presentation' => 'Presentacion y denominacion comercial con texto extenso para verificar los saltos de linea',
        'quantity' => 1, 'unit_price' => 50.1234, 'vat' => 0, 'total' => 50.12]);
}
$quotation->pricing_snapshot = ['lines' => $lines];
$quotation->total = 2004.80;
file_put_contents($directory.'/quotation-pdf-multiple.pdf', app(RequestQuotationPdf::class)->render($quotation));

$lines = [];
foreach ([1, 2] as $number) {
    $lines[] = array_replace($medication, ['mixture_number' => $number, 'quantity' => 1, 'total' => 87808]);
    $lines[] = $service + ['mixture_number' => $number];
}
$quotation->pricing_snapshot = ['lines' => $lines];
$quotation->total = 176496;
file_put_contents($directory.'/quotation-pdf-mixtures.pdf', app(RequestQuotationPdf::class)->render($quotation));
echo "Generated three synthetic QA PDFs.\n";
