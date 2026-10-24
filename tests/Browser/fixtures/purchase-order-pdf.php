<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// This fixture never writes orders or connects to the live database.
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
$order = new App\Models\Oncologicos\LaboratoryPurchaseOrder([
    'folio' => 'VISTA PREVIA', 'department' => 'UNIDAD DE CALIDAD', 'requested_at' => '2026-10-22',
    'order_type' => 'Medicamentos', 'supplier_fax' => 'N/A', 'urgent_delivery_time' => 'Inmediata',
    'invoice_to' => 'Prodifem S.A. de C.V.', 'invoice_rfc' => 'PRO170214P96',
    'invoice_address' => 'Calle San Francisco No. 524, int. C, Col. Del Valle, Alcaldía Benito Juárez, Ciudad de México, C.P. 03100',
    'invoice_emails' => 'blandly_8@yahoo.com.mx, cmprodifem@gmail.com',
    'delivery_schedule' => 'L-V 08:00 - 14:00 h.',
    'subtotal' => 0, 'discount' => 0, 'tax_rate' => 16, 'tax_amount' => 0, 'total' => 0,
    'notes' => "CADUCIDAD 12 MESES (EN CASO DE NO CONTAR CON ESTA CADUCIDAD, FAVOR DE ENTREGAR CARTA COMPROMISO CANJE)\nENTREGA DE CERTIFICADO ANALÍTICO\nCOPIA DEL REGISTRO SANITARIO VIGENTE Y/O EN SU CASO SI CUENTA CON PRÓRROGA\nPRESENTACIÓN COMERCIAL\nFECHA DE ENTREGA: INMEDIATA\nPLAZO DE PAGO: CRÉDITO 30 DÍAS A PARTIR DE LA FECHA DE SOLICITUD",
]);
foreach (['laboratory', 'deliveryLaboratory', 'warehouse', 'creator'] as $relation) $order->setRelation($relation, null);
if (isset($argv[2])) {
    $order->items = array_map(fn ($index) => ['description' => 'Producto de prueba '.$index.' - Frasco de 100 mg / 4 ml', 'quantity' => 2, 'unit_price' => 100, 'subtotal' => 200], range(1, (int) $argv[2]));
    $order->subtotal = (int) $argv[2] * 200;
    $order->tax_amount = (float) $order->subtotal * .16;
    $order->total = (float) $order->subtotal * 1.16;
}
if (($argv[3] ?? '') === 'long-notes') $order->notes = str_repeat("Observación completa sin recortes. ", 280).'FIN DE OBSERVACIONES';
$pdf = Barryvdh\DomPDF\Facade\Pdf::loadView('admin.oncologicos.laboratory.purchase-orders.pdf', compact('order'))->setPaper('letter', 'portrait');
file_put_contents($argv[1], $pdf->output());
echo 'PDF: '.$argv[1].PHP_EOL;
