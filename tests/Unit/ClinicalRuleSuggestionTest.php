<?php

namespace Tests\Unit;

use App\Models\ValidationRule;
use App\Services\ValidationRules\ClinicalRuleEvaluator;
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

    private function suggestion(ValidationRule $rule, array $facts, array $result, string $group): string
    {
        $reflection = new ReflectionClass(ClinicalRuleEvaluator::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('mathematicalSuggestion');
        $method->setAccessible(true);

        return $method->invoke($service, $rule, $facts, $result, $group);
    }
}
