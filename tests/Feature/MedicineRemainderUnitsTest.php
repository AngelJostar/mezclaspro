<?php

namespace Tests\Feature;

use App\Models\MedicineRemainder;
use App\Models\Oncologicos\MedicineBatch;
use App\Models\Oncologicos\MedicineBatchMovement;
use App\Models\Oncologicos\MedicinePresentation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\RemainderInventory;
use Tests\TestCase;

class MedicineRemainderUnitsTest extends TestCase
{
    public function test_inventory_converts_only_available_remainders_for_both_categories_and_preserves_storage(): void
    {
        RemainderInventory::seed();
        $before = DB::table('medicine_remainders')->get()->toJson();
        foreach (['oncologicos' => 62.5, 'antibioticos' => 625.0] as $category => $expected) {
            $rows = RemainderInventory::viewData($category)['rows'];
            $this->assertCount(5, $rows);
            $this->assertSame(2.5, $rows[0]->remanente_ml);
            $this->assertSame($expected, $rows[0]->remanente_mg);
            $this->assertSame(0.1234, $rows[1]->remanente_ml);
            $this->assertEqualsWithDelta($expected / 2.5 * 0.1234, $rows[1]->remanente_mg, 0.000001);
            $this->assertSame(0.0, $rows[2]->remanente_mg);
            $this->assertNull($rows[3]->remanente_mg);
            $this->assertSame(0.0, $rows[4]->remanente_mg);
        }
        $this->assertSame($before, DB::table('medicine_remainders')->get()->toJson());
    }

    public function test_oncology_remainder_history_displays_initial_and_available_mg(): void
    {
        $presentation = new MedicinePresentation(['cantidad_medicamento' => 250, 'volumen_diluyente' => 10]);
        $presentation->setRelation('catalog', null);
        $batch = new MedicineBatch(['lote' => 'LOTE-PRUEBA', 'stock_actual' => 2, 'stock_ml_actual' => 20]);
        $batch->setRelation('presentation', $presentation);
        $remainder = new MedicineRemainder(['initial_ml' => 4, 'current_ml' => 2, 'is_active' => true]);
        $movement = new MedicineBatchMovement(['quantity_ml' => 0.5, 'reference_type' => 'MermaRemanente', 'movement_type' => 'merma']);
        $movement->setRelation('user', null);
        $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/oncologicos/inventory/movimientos.blade.php')));
        $html = Blade::render($source, [
            'batch' => $batch, 'remainders' => collect([$remainder]), 'movements' => new LengthAwarePaginator([$movement], 1, 25),
        ]);
        $this->assertStringContainsString('100.00 mg', $html);
        $this->assertStringContainsString('50.00 mg', $html);
        $this->assertStringContainsString('12.50 mg', $html);
        $this->assertStringNotContainsString('4.00 mL', $html);
    }

    public function test_nutritional_dispensing_keeps_the_actual_remainder_in_milliliters(): void
    {
        $html = view('admin.nutricionales.solicitudes.partials.presentacion-lote-select', [
            'input' => (object) ['input_id' => 1, 'presentations_disponibles' => collect([(object) [
                'id' => 1, 'remanente_disponible_ml' => 2.5, 'denominacion_comercial' => 'Nutricional de prueba', 'presentacion' => 'Frasco',
            ]])],
            'row' => ['presentationId' => 1, 'loteValue' => '', 'caducidadValue' => ''],
        ])->render();
        $this->assertStringContainsString('Remanente disponible: 2.50 mL', $html);
        $this->assertStringNotContainsString('2.50 mg', $html);
    }

    public function test_nutritional_inventory_keeps_the_remainder_in_milliliters(): void
    {
        $presentation = (object) [
            'id' => 1, 'denominacion_comercial' => 'Marca', 'presentacion' => 'Frasco', 'presentacion_ml' => 100,
            'frascos_total' => 0, 'stock_total_ml' => 0, 'remanente_total_ml' => 2.5, 'stocks' => collect(),
        ];
        $catalog = (object) [
            'id' => 1, 'denominacion_generica' => 'Nutricional de prueba', 'category' => null,
            'presentations' => collect([$presentation]),
        ];
        $source = str_replace(['<x-admin-layout>', '</x-admin-layout>'], '', file_get_contents(resource_path('views/admin/nutricionales/stocks/index.blade.php')));
        $html = Blade::render($source, [
            'catalogs' => collect([$catalog]), 'activeSelections' => [], 'laboratoryId' => 1, 'warehouseId' => 1,
            'warehouse' => (object) ['name' => 'Almacen de prueba'], 'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]);
        $this->assertStringContainsString('2.50 ml de remanente vigente', $html);
        $this->assertStringNotContainsString('2.50 mg', $html);
    }
}
