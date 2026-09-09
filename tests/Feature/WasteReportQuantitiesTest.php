<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SuperAdministratorController;
use App\Models\MedicineRemainder;
use App\Models\MedicineRemainderMovement;
use App\Models\Oncologicos\MedicinePresentation;
use Tests\TestCase;

class WasteReportQuantitiesTest extends TestCase
{
    public function test_remainder_waste_uses_mg_for_oncology_and_ml_for_nutrition(): void
    {
        foreach (['oncologico', 'nutricional'] as $domain) {
            $presentation = new MedicinePresentation(['cantidad_medicamento' => 100, 'volumen_diluyente' => 20]);
            $presentation->setRelation('catalog', null);
            $remainder = new MedicineRemainder(['domain' => $domain]);
            $remainder->setRelations([
                'oncologicPresentation' => $presentation, 'nutritionPresentation' => $presentation,
                'oncologicBatch' => null, 'nutritionStock' => null, 'laboratory' => null, 'warehouse' => null,
            ]);
            $movement = new MedicineRemainderMovement(['quantity_ml' => 2.5]);
            $movement->setRelations(['remainder' => $remainder, 'user' => null]);
            $method = new \ReflectionMethod(SuperAdministratorController::class, 'mapRemainderWaste');
            $record = $method->invoke(app(SuperAdministratorController::class), $movement);
            $this->assertSame($domain === 'oncologico' ? ['mg' => 12.5] : ['mL' => 2.5], $record['units']);
            $presentation->volumen_diluyente = null;
            $record = $method->invoke(app(SuperAdministratorController::class), $movement);
            $this->assertSame($domain === 'oncologico' ? ['mg' => null] : ['mL' => 2.5], $record['units']);
        }
    }
}
