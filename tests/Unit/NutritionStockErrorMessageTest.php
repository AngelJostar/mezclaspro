<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Nutricionales\SolicitudController;
use App\Models\Hospital;
use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\MedicineLaboratoryStock;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class NutritionStockErrorMessageTest extends TestCase
{
    public function test_it_identifies_the_input_presentation_lot_and_available_inventory(): void
    {
        $input = new Input(['description' => 'Bolsa EVA']);
        $category = new Category(['name' => 'BOLSA EVA']);
        $catalog = new NutritionMedicineCatalog(['denominacion_generica' => 'Bolsa para nutrición parenteral']);
        $catalog->setRelation('input', $input);
        $catalog->setRelation('category', $category);

        $presentation = new NutritionMedicinePresentation([
            'denominacion_comercial' => 'OPTIMA',
            'presentacion' => 'Bolsa 500 mL',
        ]);
        $presentation->id = 27;
        $presentation->setRelation('catalog', $catalog);

        $warehouse = new Warehouse(['name' => 'Almacén principal']);
        $stock = new MedicineLaboratoryStock([
            'lote' => 'LOT-OPT-01',
            'frascos_actuales' => 0,
            'caducidad' => '2027-10-30',
        ]);
        $stock->setRelation('warehouse', $warehouse);

        $method = new ReflectionMethod(SolicitudController::class, 'mensajeStockInsuficiente');
        $message = $method->invoke(
            new SolicitudController(),
            new Hospital(['name' => 'CBTA']),
            $presentation,
            1,
            'pieza(s)',
            new Collection([$stock]),
            'LOT-SELECCIONADO'
        );

        $this->assertStringContainsString('insumo "Bolsa EVA"', $message);
        $this->assertStringContainsString('sección: BOLSA EVA', $message);
        $this->assertStringContainsString('OPTIMA — Bolsa 500 mL [ID 27]', $message);
        $this->assertStringContainsString('Lote seleccionado: LOT-SELECCIONADO', $message);
        $this->assertStringContainsString('Requerido: 1 pieza(s); existencia vigente total: 0 pieza(s)', $message);
        $this->assertStringContainsString('LOT-OPT-01 (0 pieza(s), Almacén principal, cad. 30/10/2027)', $message);
    }
}
