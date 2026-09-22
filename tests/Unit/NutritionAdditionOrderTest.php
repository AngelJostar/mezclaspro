<?php

namespace Tests\Unit;

use App\Support\NutritionAdditionOrder;
use PHPUnit\Framework\TestCase;

class NutritionAdditionOrderTest extends TestCase
{
    public function test_it_sorts_components_in_the_auditable_addition_order(): void
    {
        $items = collect([
            $this->item(1, 'VITAMINA K (10mg/mL)'),
            $this->item(2, 'CLORURO DE POTASIO (4mEq/mL)'),
            $this->item(3, 'AMINOACIDOS CRISTALINOS AL 10%'),
            $this->item(4, 'OLIGOELEMENTOS'),
            $this->item(5, 'SOLUCION GLUCOSADA AL 50%'),
            $this->item(6, 'MULTIVITAMINICO ADULTO'),
        ]);

        $ordered = NutritionAdditionOrder::sort($items)
            ->pluck('input.description')
            ->all();

        $this->assertSame([
            'AMINOACIDOS CRISTALINOS AL 10%',
            'SOLUCION GLUCOSADA AL 50%',
            'CLORURO DE POTASIO (4mEq/mL)',
            'OLIGOELEMENTOS',
            'MULTIVITAMINICO ADULTO',
            'VITAMINA K (10mg/mL)',
        ], $ordered);
    }

    private function item(int $id, string $description): object
    {
        return (object) [
            'id' => $id,
            'presentation' => null,
            'input' => (object) [
                'description' => $description,
                'nutritionMedicineCatalog' => null,
            ],
        ];
    }
}
