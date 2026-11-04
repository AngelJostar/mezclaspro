<?php

namespace Tests\Unit;

use App\Support\AdultNutritionalCompositionCatalogV2;
use PHPUnit\Framework\TestCase;

class AdultNutritionalCompositionCatalogV2Test extends TestCase
{
    public function test_catalog_contains_all_adult_document_combinations(): void
    {
        $compositions = AdultNutritionalCompositionCatalogV2::compositions();

        $this->assertCount(156, $compositions);
        $this->assertCount(156, array_unique(array_column($compositions, 'code')));
        $this->assertTrue(collect($compositions)->every(
            fn (array $composition) => in_array('amino_acids', $composition['groups'], true)
        ));
    }

    public function test_catalog_preserves_water_saline_and_insulin_exception(): void
    {
        $compositions = collect(AdultNutritionalCompositionCatalogV2::compositions())->keyBy('code');

        $this->assertSame(
            ['amino_acids', 'water', 'saline'],
            $compositions['AD2-049']['groups']
        );
        $this->assertSame(
            ['insulin'],
            $compositions['AD2-056']['forbidden_component_terms']
        );
        $this->assertSame(
            ['amino_acids', 'dextrose', 'water', 'saline', 'lipids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'],
            $compositions['AD2-193']['groups']
        );
    }
}
