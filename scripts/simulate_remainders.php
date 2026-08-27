<?php

declare(strict_types=1);

use App\Models\MedicineRemainder;
use App\Services\MedicineRemainderService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__) . '/vendor/autoload.php';

$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [];
$check = static function (string $description, bool $passed, string $detail = '') use (&$checks): void {
    $checks[] = [$description, $passed, $detail];
};

$laboratoryId = (int) DB::table('laboratories')->value('id');
$oncoPresentationId = (int) DB::table('medicine_presentations')->value('id');
$nutritionPresentationId = (int) DB::table('nutrition_medicine_presentations')->value('id');

if (!$laboratoryId || !$oncoPresentationId || !$nutritionPresentationId) {
    fwrite(STDERR, "No hay laboratorio o presentaciones suficientes para ejecutar la simulacion.\n");
    exit(2);
}

$referenceId = random_int(800000000, 899999999);
$service = app(MedicineRemainderService::class);

DB::beginTransaction();

try {
    // Aisla la simulacion sin alterar permanentemente los remanentes locales.
    MedicineRemainder::query()
        ->where('laboratory_id', $laboratoryId)
        ->where(function ($query) use ($oncoPresentationId, $nutritionPresentationId) {
            $query->where('medicine_presentation_id', $oncoPresentationId)
                ->orWhere('nutrition_medicine_presentation_id', $nutritionPresentationId);
        })
        ->delete();

    $later = $service->openContainer([
        'domain' => 'oncologico',
        'laboratory_id' => $laboratoryId,
        'medicine_presentation_id' => $oncoPresentationId,
        'lote' => 'SIM-LATER',
        'opened_at' => now(),
        'stability_hours' => 48,
        'reference_type' => 'SimulacionRemanente',
        'reference_id' => $referenceId,
    ], 10, 2);

    $sooner = $service->openContainer([
        'domain' => 'oncologico',
        'laboratory_id' => $laboratoryId,
        'medicine_presentation_id' => $oncoPresentationId,
        'lote' => 'SIM-SOONER',
        'opened_at' => now(),
        'stability_hours' => 12,
        'reference_type' => 'SimulacionRemanente',
        'reference_id' => $referenceId + 1,
    ], 10, 4);

    $check('Apertura oncologica', $later && $sooner
        && abs((float) $later->current_ml - 8.0) < 0.0001
        && abs((float) $sooner->current_ml - 6.0) < 0.0001,
        'Se esperaban remanentes de 8 mL y 6 mL.');

    $consumption = $service->consumeAvailable(
        'oncologico',
        $oncoPresentationId,
        $laboratoryId,
        7,
        'SimulacionConsumo',
        $referenceId + 2
    );
    $later->refresh();
    $sooner->refresh();

    $check('Consumo por estabilidad mas proxima',
        abs($consumption['consumed_ml'] - 7.0) < 0.0001
        && abs((float) $sooner->current_ml) < 0.0001
        && abs((float) $later->current_ml - 7.0) < 0.0001,
        'Debe agotar primero 6 mL del remanente con menor vigencia y tomar 1 mL del siguiente.');

    $service->rollbackReference('SimulacionConsumo', $referenceId + 2);
    $later->refresh();
    $sooner->refresh();
    $check('Reversion de consumo',
        abs((float) $later->current_ml - 8.0) < 0.0001
        && abs((float) $sooner->current_ml - 6.0) < 0.0001,
        'La reversion debe devolver los 7 mL a sus remanentes originales.');

    $expired = $service->openContainer([
        'domain' => 'oncologico',
        'laboratory_id' => $laboratoryId,
        'medicine_presentation_id' => $oncoPresentationId,
        'lote' => 'SIM-EXPIRED',
        'opened_at' => now()->subHours(2),
        'stability_hours' => 1,
        'reference_type' => 'SimulacionVencimiento',
        'reference_id' => $referenceId + 3,
    ], 10, 3);
    $service->expireDueRemainders();
    $expired->refresh();
    $check('Descarte por estabilidad vencida',
        !$expired->is_active && abs((float) $expired->current_ml) < 0.0001,
        'El remanente vencido debe quedar inactivo y en 0 mL.');

    $nutrition = $service->openContainer([
        'domain' => 'nutricional',
        'laboratory_id' => $laboratoryId,
        'nutrition_medicine_presentation_id' => $nutritionPresentationId,
        'lote' => 'SIM-NUTRI',
        'opened_at' => now(),
        'stability_hours' => 24,
        'reference_type' => 'SimulacionNutricional',
        'reference_id' => $referenceId + 4,
    ], 100, 30);
    $nutritionConsumption = $service->consumeAvailable(
        'nutricional',
        $nutritionPresentationId,
        $laboratoryId,
        20,
        'SimulacionNutricionalConsumo',
        $referenceId + 5
    );
    $nutrition->refresh();
    $check('Flujo nutricional',
        abs($nutritionConsumption['consumed_ml'] - 20.0) < 0.0001
        && abs((float) $nutrition->current_ml - 50.0) < 0.0001,
        'Un envase de 100 mL, usando 30 mL y despues 20 mL, debe conservar 50 mL.');
} catch (Throwable $exception) {
    fwrite(STDERR, get_class($exception) . ': ' . $exception->getMessage() . PHP_EOL);
    fwrite(STDERR, $exception->getTraceAsString() . PHP_EOL);
    DB::rollBack();
    exit(1);
}

DB::rollBack();

foreach ($checks as [$description, $passed, $detail]) {
    echo sprintf('[%s] %s%s', $passed ? 'OK' : 'FALLO', $description, $detail ? " - {$detail}" : '') . PHP_EOL;
}

$failed = count(array_filter($checks, static fn(array $check): bool => !$check[1]));
echo PHP_EOL . 'Resultado: ' . (count($checks) - $failed) . '/' . count($checks) . ' comprobaciones correctas.' . PHP_EOL;
echo 'Limpieza: transaccion revertida; no se conservaron datos de simulacion.' . PHP_EOL;

exit($failed === 0 ? 0 : 1);
