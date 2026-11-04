<?php

namespace Tests\Unit;

use App\Support\PediatricNutritionalCompositionCatalogV2;
use PHPUnit\Framework\TestCase;

class PediatricNutritionalCompositionCatalogV2Test extends TestCase
{
    public function test_catalog_contains_all_pediatric_document_combinations(): void
    {
        $compositions = PediatricNutritionalCompositionCatalogV2::compositions();

        $this->assertCount(156, $compositions);
        $this->assertCount(156, array_unique(array_column($compositions, 'code')));
        $this->assertTrue(collect($compositions)->every(
            fn (array $composition) => $composition['component_terms'] === ['aa_pediatric_10']
                && in_array('amino_acids', $composition['groups'], true)
        ));
    }

    public function test_catalog_preserves_pediatric_groups_and_insulin_exception(): void
    {
        $compositions = collect(PediatricNutritionalCompositionCatalogV2::compositions())->keyBy('code');

        $this->assertSame(
            ['amino_acids', 'water', 'saline'],
            $compositions['PED2-049']['groups']
        );
        $this->assertSame(
            ['insulin'],
            $compositions['PED2-056']['forbidden_component_terms']
        );
        $this->assertSame(
            ['amino_acids', 'dextrose', 'water', 'saline', 'lipids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'],
            $compositions['PED2-193']['groups']
        );
    }
}
