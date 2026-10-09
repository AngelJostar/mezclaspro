<?php

namespace Tests\Unit;

use App\Services\ValidationRules\CompositionRuleEngine;
use App\Services\ValidationRules\MathematicalRuleEngine;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ValidationRuleEnginesTest extends TestCase
{
    public function test_exact_composition_reports_unexpected_groups(): void
    {
        $result = (new CompositionRuleEngine())->evaluate([
            'match_mode' => 'exact',
            'required_groups' => ['amino_acids', 'water'],
            'forbidden_groups' => [],
            'required_components' => [],
            'forbidden_components' => [],
            'count_calculated_water' => true,
        ], [
            'active_groups' => ['amino_acids', 'lipids'],
            'active_component_ids' => [1, 2],
            'calculated_water' => true,
        ]);

        $this->assertFalse($result['passed']);
        $this->assertSame(['lipids'], $result['result']['unexpected_groups']);
    }

    public function test_mathematical_template_calculates_amount_per_weight(): void
    {
        $result = (new MathematicalRuleEngine())->evaluate([
            'source_variable' => 'component.amount',
            'formula_template' => 'amount_per_weight',
            'operator' => '<=',
            'threshold_value' => 3,
            'unit' => 'g/kg/día',
            'failure_message' => 'Supera el máximo.',
        ], ['component' => ['amount' => 20], 'patient' => ['weight_kg' => 10]]);

        $this->assertTrue($result['passed']);
        $this->assertSame(2.0, $result['value']);
    }

    public function test_allowed_catalog_accepts_any_matching_alternative(): void
    {
        $result = (new CompositionRuleEngine())->evaluate([
            'mode' => 'allowed_catalog',
            'allow_any_single_component' => false,
            'allowed_compositions' => [
                ['code' => 'C01', 'groups' => ['amino_acids', 'water'], 'component_terms' => [], 'forbidden_component_terms' => []],
                ['code' => 'C02', 'groups' => ['amino_acids', 'dextrose'], 'component_terms' => [], 'forbidden_component_terms' => []],
            ],
        ], [
            'active_groups' => ['amino_acids', 'dextrose'],
            'active_component_ids' => [10, 11],
            'active_component_terms' => [],
        ]);

        $this->assertTrue($result['passed']);
        $this->assertSame('C02', $result['result']['matched_code']);
    }

    public function test_allowed_catalog_reports_detected_components_and_closest_alternatives(): void
    {
        $result = (new CompositionRuleEngine())->evaluate([
            'mode' => 'allowed_catalog',
            'allowed_compositions' => [
                ['code' => 'C01', 'label' => 'Aminoácidos + vitaminas', 'groups' => ['amino_acids', 'vitamins']],
                ['code' => 'C02', 'label' => 'Aminoácidos + electrolitos', 'groups' => ['amino_acids', 'electrolytes']],
            ],
        ], [
            'active_groups' => ['vitamins', 'medications'],
            'active_component_ids' => [10, 11, 12],
            'active_component_names' => ['ALBÚMINA 20%', 'VITAMINA K', 'INSULINA'],
            'active_component_terms' => ['insulin'],
        ]);

        $this->assertFalse($result['passed']);
        $this->assertSame(['ALBÚMINA 20%', 'VITAMINA K', 'INSULINA'], $result['result']['detected_component_names']);
        $this->assertSame('C01', $result['result']['closest_alternatives'][0]['code']);
        $this->assertSame(['amino_acids'], $result['result']['closest_alternatives'][0]['missing_groups']);
        $this->assertSame(['medications'], $result['result']['closest_alternatives'][0]['unexpected_groups']);
    }

    public function test_mathematical_engine_marks_missing_weight_as_incomplete(): void
    {
        $result = (new MathematicalRuleEngine())->evaluate([
            'source_variable' => 'component.amount',
            'formula_template' => 'amount_per_weight',
            'operator' => '<=',
            'threshold_value' => 3,
        ], ['component' => ['amount' => 20]]);

        $this->assertTrue($result['incomplete']);
        $this->assertStringContainsString('peso', $result['message']);
    }

    public function test_mathematical_engine_rejects_unknown_templates(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new MathematicalRuleEngine())->evaluate([
            'source_variable' => 'component.amount',
            'formula_template' => 'php_expression',
        ], ['component' => ['amount' => 20]]);
    }
}
