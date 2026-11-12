<?php

namespace Tests\Unit;

use App\Models\ValidationRule;
use App\Services\ValidationRules\ClinicalRuleEvaluator;
use App\Services\ValidationRules\CompositionRuleEngine;
use App\Services\ValidationRules\MathematicalRuleEngine;
use ReflectionClass;
use Tests\TestCase;

class ClinicalRuleSuggestionTest extends TestCase
{
    public function test_direct_dose_limit_explains_target_and_amount_to_reduce(): void
    {
        $rule = new ValidationRule([
            'configuration' => [
                'source_variable' => 'groups.lipids.dose_g_kg_day',
                'formula_template' => 'direct_value',
                'operator' => '<=',
                'threshold_value' => 2.5,
                'unit' => 'g/kg/día',
            ],
        ]);
        $facts = ['group_component_names' => ['lipids' => ['lípidos']]];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 7.98], 'lipids');

        $this->assertStringContainsString('lípidos', $suggestion);
        $this->assertStringContainsString('7.98 g/kg/día', $suggestion);
        $this->assertStringContainsString('2.5 g/kg/día', $suggestion);
        $this->assertStringContainsString('disminuir 5.48 g/kg/día', $suggestion);
    }

    public function test_percentage_correction_uses_visible_name_current_value_target_and_difference(): void
    {
        $rule = new ValidationRule([
            'configuration' => [
                'source_variable' => 'groups.dextrose.dose_g_kg_day',
                'formula_template' => 'dose_times_weight_percentage_of_volume',
                'operator' => 'between',
                'threshold_min' => 5,
                'threshold_max' => 35,
            ],
        ]);
        $facts = [
            'patient' => ['weight_kg' => 77],
            'mixture' => ['total_volume_ml' => 3441.92],
            'groups' => ['dextrose' => ['dose_g_kg_day' => 155.9993 / 77]],
            'group_component_names' => ['dextrose' => ['SOLUCION GLUCOSADA AL 50%']],
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 4.532334], 'dextrose');

        $this->assertStringContainsString('SOLUCION GLUCOSADA AL 50%', $suggestion);
        $this->assertStringContainsString('155.9993 g', $suggestion);
        $this->assertStringContainsString('172.096 g', $suggestion);
        $this->assertStringContainsString('aumentar 16.0967 g', $suggestion);
        $this->assertStringNotContainsString('i_8_g', $suggestion);
    }

    public function test_weighted_percentage_correction_returns_the_value_in_the_captured_unit(): void
    {
        $rule = new ValidationRule([
            'configuration' => [
                'source_variable' => 'groups.dextrose.dose_g_kg_day',
                'formula_template' => 'dose_times_weight_percentage_of_volume',
                'operator' => 'between',
                'threshold_min' => 5,
                'threshold_max' => 35,
            ],
        ]);
        $facts = [
            'patient' => ['weight_kg' => 1.99],
            'mixture' => ['total_volume_ml' => 298],
            'groups' => ['dextrose' => ['dose_g_kg_day' => 7.2]],
            'group_component_names' => ['dextrose' => ['SOLUCION GLUCOSADA AL 50%']],
            'group_captured_values' => ['dextrose' => [7.2]],
            'group_input_units' => ['dextrose' => ['g/Kg']],
            'group_weighted_inputs' => ['dextrose' => [true]],
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 4.808054], 'dextrose');

        $this->assertStringContainsString('7.2 g/Kg', $suggestion);
        $this->assertStringContainsString('7.4875 g/Kg', $suggestion);
        $this->assertStringContainsString('aumentar 0.2875 g/Kg', $suggestion);
        $this->assertStringContainsString('14.9 g totales', $suggestion);
        $this->assertStringNotContainsString('Captura el nuevo total', $suggestion);
    }

    public function test_strict_minimum_percentage_adds_a_safe_margin(): void
    {
        $rule = new ValidationRule(['configuration' => [
            'source_variable' => 'groups.amino_acids.dose_g_kg_day',
            'formula_template' => 'dose_times_weight_percentage_of_volume',
            'operator' => '>',
            'threshold_value' => 2.5,
        ]]);
        $facts = [
            'patient' => ['weight_kg' => 2],
            'mixture' => ['total_volume_ml' => 300],
            'groups' => ['amino_acids' => ['dose_g_kg_day' => 3.5]],
            'group_component_names' => ['amino_acids' => ['AMINOACIDOS PEDIATRICOS 10%']],
            'group_captured_values' => ['amino_acids' => [3.5]],
            'group_input_units' => ['amino_acids' => ['g/Kg']],
            'group_weighted_inputs' => ['amino_acids' => [true]],
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 2.333333], 'amino_acids');

        $this->assertStringContainsString('3.7501 g/Kg', $suggestion);
        $this->assertStringContainsString('aumentar 0.2501 g/Kg', $suggestion);
    }

    public function test_water_percentage_explains_the_maximum_volume(): void
    {
        $rule = new ValidationRule(['configuration' => [
            'source_variable' => 'groups.water.volume_ml',
            'formula_template' => 'percentage_of_volume',
            'operator' => '<=',
            'threshold_value' => 60,
        ]]);
        $facts = [
            'mixture' => ['total_volume_ml' => 1000],
            'groups' => ['water' => ['volume_ml' => 900]],
            'group_component_names' => ['water' => ['AGUA INYECTABLE']],
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 90], 'water');

        $this->assertStringContainsString('máximo 600 mL', $suggestion);
        $this->assertStringContainsString('disminuir 300 mL', $suggestion);
    }

    public function test_calculated_water_percentage_recommends_the_complete_total_volume(): void
    {
        $rule = new ValidationRule(['configuration' => [
            'source_variable' => 'groups.water.volume_ml',
            'formula_template' => 'percentage_of_volume',
            'operator' => '<=',
            'threshold_value' => 60,
        ]]);
        $facts = [
            'mixture' => [
                'total_volume_ml' => 240,
                'component_volume_ml' => 87.4861,
            ],
            'groups' => ['water' => ['volume_ml' => 152.5139]],
            'group_component_names' => ['water' => ['AGUA INYECTABLE DE AFORO']],
            'calculated_water' => true,
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 63.547458], 'water');

        $this->assertSame('El volumen total sugerido es de 218.7152 mL.', $suggestion);
    }

    public function test_water_failure_is_shown_before_calcium_phosphate_and_defers_its_adjustment(): void
    {
        $reflection = new ReflectionClass(ClinicalRuleEvaluator::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('prioritizeDependentFindings');
        $method->setAccessible(true);

        $findings = $method->invoke($service, [
            ['rule_code' => 'NP.R6.CHEM.CALCIUM_PHOSPHATE.PER1000', 'suggestion' => 'Reduce calcio y fosfato.'],
            ['rule_code' => 'NP.R6.CHEM.WATER.PERCENT', 'suggestion' => 'El volumen total sugerido es de 166.4367 mL.'],
        ]);

        $this->assertSame('NP.R6.CHEM.WATER.PERCENT', $findings[0]['rule_code']);
        $this->assertSame('NP.R6.CHEM.CALCIUM_PHOSPHATE.PER1000', $findings[1]['rule_code']);
    }

    public function test_volume_dependent_suggestion_uses_water_adjusted_volume(): void
    {
        $rule = new ValidationRule(['configuration' => [
            'source_variable' => 'groups.dextrose.dose_g_kg_day',
            'formula_template' => 'dose_times_weight_percentage_of_volume',
            'operator' => 'between',
            'threshold_min' => 5,
            'threshold_max' => 35,
            'unit' => '%',
        ]]);
        $facts = [
            'patient' => ['weight_kg' => 1.1],
            'mixture' => ['total_volume_ml' => 300],
            'suggested_total_volume_ml' => 229.7728,
            'groups' => ['dextrose' => ['dose_g_kg_day' => 9]],
            'group_component_names' => ['dextrose' => ['SOLUCION GLUCOSADA AL 50%']],
            'group_captured_values' => ['dextrose' => [9]],
            'group_input_units' => ['dextrose' => ['g/Kg']],
            'group_weighted_inputs' => ['dextrose' => [true]],
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 3.3], 'dextrose');

        $this->assertStringContainsString('volumen total sugerido de 229.7728 mL', $suggestion);
        $this->assertStringContainsString('aproximadamente 10.4443 g/Kg', $suggestion);
        $this->assertStringNotContainsString('13.6364 g/Kg', $suggestion);
    }

    public function test_conflicting_suggestions_for_same_component_are_consolidated(): void
    {
        $reflection = new ReflectionClass(ClinicalRuleEvaluator::class);
        $service = new ClinicalRuleEvaluator(new CompositionRuleEngine(), new MathematicalRuleEngine());
        $method = $reflection->getMethod('coordinateConflictingSuggestions');
        $method->setAccessible(true);
        $findings = $method->invoke($service, [[
            'rule_code' => 'NP.R6.CHEM.AA.PERCENT',
            'field' => 'i_10_g/Kg',
            'suggestion' => 'Aumentar aminoácidos.',
            '_group' => 'amino_acids',
            '_configuration' => ['formula_template' => 'dose_times_weight_percentage_of_volume', 'operator' => '>', 'threshold_value' => 2.5, 'unit' => '%'],
            '_result' => ['value' => 2.256196],
        ], [
            'rule_code' => 'NP.R6.PED.CLIN.AA.NEONATE',
            'suggestion' => 'Disminuir aminoácidos.',
            '_group' => 'amino_acids',
            '_configuration' => ['formula_template' => 'direct_value', 'operator' => '<=', 'threshold_value' => 4, 'unit' => 'g/kg/día'],
            '_result' => ['value' => 6.0727],
        ]], [
            'patient' => ['weight_kg' => 1.1],
            'mixture' => ['total_volume_ml' => 296.2],
            'group_component_names' => ['amino_acids' => ['AMINOACIDOS PEDIATRICOS 10%']],
        ]);

        $this->assertStringContainsString('Ajuste conjunto para AMINOACIDOS PEDIATRICOS 10%', $findings[0]['suggestion']);
        $this->assertStringContainsString('4 g/kg/día', $findings[0]['suggestion']);
        $this->assertStringContainsString('volumen total máximo 175.9999 mL', $findings[0]['suggestion']);
        $this->assertSame([
            ['field' => 'i_10_g/Kg', 'value' => 4.0],
            ['field' => 'volumen_total', 'value' => 175.9999],
        ], $findings[0]['suggested_changes']);
        $this->assertSame('', $findings[1]['suggestion']);
        $this->assertSame([], $findings[1]['suggested_changes']);
    }

    public function test_the_projected_volume_uses_the_strictest_conflicting_component_limit(): void
    {
        $service = new ClinicalRuleEvaluator(new CompositionRuleEngine(), new MathematicalRuleEngine());
        $method = (new ReflectionClass($service))->getMethod('suggestedTotalVolume');
        $rules = collect([
            new ValidationRule(['configuration' => [
                'source_variable' => 'groups.amino_acids.dose_g_kg_day',
                'formula_template' => 'direct_value', 'operator' => '<=', 'threshold_value' => 4,
                'applicability' => ['required_group' => 'amino_acids'],
            ]]),
            new ValidationRule(['configuration' => [
                'source_variable' => 'groups.amino_acids.dose_g_kg_day',
                'formula_template' => 'dose_times_weight_percentage_of_volume', 'operator' => '>', 'threshold_value' => 2.5,
                'applicability' => ['required_group' => 'amino_acids'],
            ]]),
        ]);
        $facts = [
            'patient' => ['weight_kg' => 1.1],
            'mixture' => ['total_volume_ml' => 296.2, 'component_volume_ml' => 80],
            'groups' => ['amino_acids' => ['dose_g_kg_day' => 6.0727]],
            'active_groups' => ['amino_acids'], 'calculated_water' => false,
            'patient_class' => 'neonate', 'clinical_text' => '', 'unavailable_sources' => [],
        ];

        $this->assertSame(175.9999, $method->invoke($service, $rules, $facts));

        $facts['groups']['amino_acids']['dose_g_kg_day'] = 4;
        $this->assertSame(175.9999, $method->invoke($service, $rules, $facts));
    }

    public function test_a_clinical_dose_adjustment_also_applies_the_compatible_volume(): void
    {
        $service = new ClinicalRuleEvaluator(new CompositionRuleEngine(), new MathematicalRuleEngine());
        $method = (new ReflectionClass($service))->getMethod('finding');
        $rule = new ValidationRule([
            'engine' => 'mathematical', 'severity' => 'authorization', 'name' => 'Aminoácidos pediátricos',
            'code' => 'NP.R6.PED.CLIN.AA.NEONATE', 'version' => 1,
            'configuration' => [
                'source_variable' => 'groups.amino_acids.dose_g_kg_day',
                'formula_template' => 'direct_value', 'operator' => '<=', 'threshold_value' => 4,
                'unit' => 'g/kg/día',
            ],
        ]);
        $finding = $method->invoke($service, $rule, 0, [
            'patient' => ['weight_kg' => 1.1],
            'mixture' => ['total_volume_ml' => 195.4],
            'suggested_total_volume_ml' => 175.9999,
            'group_fields' => ['amino_acids' => 'i_10_g/Kg'],
            'group_component_names' => ['amino_acids' => ['AMINOACIDOS PEDIATRICOS 10%']],
            'group_captured_values' => ['amino_acids' => [4.4409]],
            'group_input_units' => ['amino_acids' => ['g/Kg']],
            'group_weighted_inputs' => ['amino_acids' => [true]],
        ], ['value' => 4.4409, 'unit' => 'g/kg/día', 'message' => 'Exceso de aminoácidos.'], 'C20');

        $this->assertSame([
            ['field' => 'i_10_g/Kg', 'value' => 4.0],
            ['field' => 'volumen_total', 'value' => 175.9999],
        ], $finding['suggested_changes']);
        $this->assertStringContainsString('captura 4 g/kg/día y un volumen total máximo de 175.9999 mL', $finding['suggestion']);
    }

    public function test_calcium_phosphate_calculation_explains_sum_capacity_and_formula(): void
    {
        $reflection = new ReflectionClass(ClinicalRuleEvaluator::class);
        $service = new ClinicalRuleEvaluator(new CompositionRuleEngine(), new MathematicalRuleEngine());
        $method = $reflection->getMethod('mathematicalCalculation');
        $method->setAccessible(true);

        $message = $method->invoke($service, [
            'formula_template' => 'volume_capacity_per_1000_ml',
            'threshold_value' => 45,
        ], [
            'value' => 15.4,
            'comparison_value' => 13.5,
            'unit' => 'mEq',
        ]);

        $this->assertSame(
            'La suma de mEq capturados: 15.4 mEq; capacidad máxima es de: 13.5 mEq = (volumen total × 45) ÷ 1000.',
            $message
        );
    }

    public function test_combined_electrolyte_limit_distributes_the_total_across_inputs(): void
    {
        $rule = new ValidationRule(['configuration' => [
            'source_variable' => 'groups.chloride.dose_meq_kg_day',
            'formula_template' => 'direct_value',
            'operator' => '<=',
            'threshold_value' => 3,
            'unit' => 'mEq/kg/día',
        ]]);
        $facts = [
            'group_component_names' => ['chloride' => [
                'CLORURO DE SODIO AL 17.7%',
                'CLORURO DE POTASIO',
            ]],
            'group_captured_values' => ['chloride' => [3, 3]],
            'group_input_units' => ['chloride' => ['mEq/Kg', 'mEq/Kg']],
        ];

        $suggestion = $this->suggestion($rule, $facts, ['value' => 6], 'chloride');

        $this->assertStringContainsString('suma de los componentes', $suggestion);
        $this->assertStringContainsString('CLORURO DE SODIO AL 17.7%: 1.5 mEq/Kg', $suggestion);
        $this->assertStringContainsString('CLORURO DE POTASIO: 1.5 mEq/Kg', $suggestion);
        $this->assertStringContainsString('suma no exceda el límite', $suggestion);
    }

    public function test_composition_suggestion_includes_each_alternative_identifier(): void
    {
        $reflection = new ReflectionClass(ClinicalRuleEvaluator::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('compositionExplanation');
        $method->setAccessible(true);

        $explanation = $method->invoke($service, [
            'detected_component_names' => ['SOLUCION GLUCOSADA AL 50%'],
            'detected_groups' => ['dextrose'],
            'closest_alternatives' => [[
                'code' => 'AD2-094',
                'label' => 'Aminoácidos + dextrosa + agua',
                'missing_groups' => ['amino_acids', 'water'],
            ]],
        ]);

        $this->assertStringContainsString(
            'AD2-094 — Aminoácidos + dextrosa + agua',
            $explanation['suggestion']
        );
    }

    private function suggestion(ValidationRule $rule, array $facts, array $result, string $group): string
    {
        $reflection = new ReflectionClass(ClinicalRuleEvaluator::class);
        $service = new ClinicalRuleEvaluator(new CompositionRuleEngine(), new MathematicalRuleEngine());
        $method = $reflection->getMethod('mathematicalSuggestion');
        $method->setAccessible(true);

        return $method->invoke($service, $rule, $facts, $result, $group);
    }
}
