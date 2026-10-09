<?php

namespace Tests\Feature;

use App\Models\ValidationRule;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\Category;
use App\Models\Oncologicos\Diluent;
use App\Services\Clinical\ClinicalPayload;
use App\Services\ValidationRules\ClinicalRuleEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalRuleEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_mathematical_rule_generates_a_deterministic_finding(): void
    {
        $this->rule([
            'code' => 'TEST.MATH.DEXTROSE',
            'engine' => 'mathematical',
            'severity' => 'authorization',
            'configuration' => [
                'source_variable' => 'groups.dextrose.dose_g_kg_day',
                'formula_template' => 'direct_value',
                'operator' => '<=',
                'threshold_value' => 7.2,
                'unit' => 'g/kg/día',
                'failure_message' => 'La dosis de dextrosa excede el límite configurado.',
            ],
        ]);

        $result = app(ClinicalRuleEvaluator::class)->evaluate($this->adultCase([
            $this->componentData(8, 2, 'SOLUCION GLUCOSADA AL 50%', 400, 400, 'i_8_g/Kg'),
        ]));

        $this->assertCount(1, $result['findings']);
        $this->assertSame('TEST.MATH.DEXTROSE', $result['findings'][0]['rule_code']);
        $this->assertSame('authorization', $result['findings'][0]['severity']);
        $this->assertStringContainsString('8', $result['findings'][0]['calculation']);
        $this->assertSame('failed', $result['evaluations'][0]['status']);
    }

    public function test_percentage_rejection_explains_the_exact_value_and_difference_to_capture(): void
    {
        $this->rule([
            'code' => 'NP.R6.CHEM.DEXTROSE.PERCENT',
            'name' => 'Concentración final de dextrosa',
            'engine' => 'mathematical',
            'configuration' => [
                'source_variable' => 'groups.dextrose.dose_g_kg_day',
                'formula_template' => 'dose_times_weight_percentage_of_volume',
                'operator' => 'between',
                'threshold_min' => 5,
                'threshold_max' => 35,
                'unit' => '%',
                'failure_message' => 'La concentración final de dextrosa debe estar entre 5% y 35%.',
                'applicability' => ['required_group' => 'dextrose'],
            ],
        ]);

        $case = $this->adultCase([
            $this->componentData(8, 2, 'SOLUCION GLUCOSADA AL 50%', 155.9993, 155.9993, 'i_8_g/Kg'),
        ]);
        $case['patient']['weight_kg'] = 77;
        $case['mixtures'][0]['volume_ml'] = 3441.92;

        $finding = app(ClinicalRuleEvaluator::class)->evaluate($case)['findings'][0];

        $this->assertStringContainsString('SOLUCION GLUCOSADA AL 50%', $finding['suggestion']);
        $this->assertStringContainsString('155.9993 g', $finding['suggestion']);
        $this->assertStringContainsString('172.096 g', $finding['suggestion']);
        $this->assertStringContainsString('aumentar 16.0967 g', $finding['suggestion']);
        $this->assertStringNotContainsString('i_8_g', $finding['suggestion']);
    }

    public function test_active_allowed_composition_is_evaluated_from_catalog_groups(): void
    {
        $this->rule([
            'code' => 'TEST.COMP.CATALOG',
            'engine' => 'composition',
            'configuration' => [
                'mode' => 'allowed_catalog',
                'allow_any_single_component' => false,
                'allowed_compositions' => [[
                    'code' => 'C01',
                    'groups' => ['amino_acids', 'dextrose'],
                    'component_terms' => [],
                    'forbidden_component_terms' => [],
                ]],
            ],
        ]);

        $result = app(ClinicalRuleEvaluator::class)->evaluate($this->adultCase([
            $this->componentData(4, 1, 'AMINOACIDOS CRISTALINOS AL 10%', 50, 50, 'i_4_g/Kg'),
            $this->componentData(8, 2, 'SOLUCION GLUCOSADA AL 50%', 250, 250, 'i_8_g/Kg'),
        ]));

        $this->assertSame([], $result['findings']);
        $this->assertSame('passed', $result['evaluations'][0]['status']);
    }

    public function test_missing_clinical_stage_is_not_inferred(): void
    {
        $this->rule([
            'code' => 'TEST.MATH.STAGE',
            'engine' => 'mathematical',
            'configuration' => [
                'source_variable' => 'groups.amino_acids.dose_g_kg_day',
                'formula_template' => 'direct_value',
                'operator' => '<=',
                'threshold_value' => 0.8,
                'unit' => 'g/kg/día',
                'failure_message' => 'Excede el límite.',
                'applicability' => ['clinical_stage' => 'metabólicamente estable'],
            ],
        ]);

        $result = app(ClinicalRuleEvaluator::class)->evaluate($this->adultCase([
            $this->componentData(4, 1, 'AMINOACIDOS CRISTALINOS AL 10%', 100, 100, 'i_4_g/Kg'),
        ]));

        $this->assertSame([], $result['findings']);
        $this->assertSame('not_evaluable', $result['evaluations'][0]['status']);
    }

    public function test_water_is_not_inferred_from_unfilled_volume(): void
    {
        $this->rule([
            'code' => 'TEST.MATH.WATER',
            'engine' => 'mathematical',
            'configuration' => [
                'source_variable' => 'groups.water.volume_ml',
                'formula_template' => 'percentage_of_volume',
                'operator' => '<=',
                'threshold_value' => 60,
                'unit' => '%',
                'failure_message' => 'El agua excede el límite.',
                'applicability' => ['required_group' => 'lipids'],
            ],
        ]);

        $result = app(ClinicalRuleEvaluator::class)->evaluate($this->adultCase([
            $this->componentData(9, 3, 'LIPIDOS 20%', 50, 50, 'i_9_g/Kg'),
        ]));

        $this->assertSame([], $result['findings']);
        $this->assertSame('not_evaluable', $result['evaluations'][0]['status']);
    }

    public function test_payload_adds_the_same_automatic_fill_water_as_the_request_flow(): void
    {
        Category::forceCreate(['id' => 1, 'name' => 'Macronutrientes']);
        Input::forceCreate([
            'id' => 9001, 'description' => 'AMINOACIDOS DE PRUEBA 10%', 'is_active' => true,
            'tipo_input' => 'ambos', 'orden_enum' => 1, 'category_id' => 1,
            'unidad' => 'g/Kg', 'mult' => 100, 'div' => 10,
        ]);

        $case = app(ClinicalPayload::class)->normalize('nutricionales', [
            'peso' => 50, 'fecha_nacimiento' => '1990-01-01', 'npt' => 'ADULT',
            'volumen_total' => 1000, 'via_administracion' => 'Central', 'i_9001_g/Kg' => 50,
        ]);

        $water = collect($case['mixtures'][0]['components'])->firstWhere('composition_group', 'water');
        $this->assertSame(500.0, $case['mixtures'][0]['calculated_water_ml']);
        $this->assertSame(500.0, $water['volume_ml']);
        $this->assertTrue($water['calculated']);
    }

    public function test_payload_uses_explicit_nutrition_diluent_without_adding_fill_water(): void
    {
        Category::forceCreate(['id' => 1, 'name' => 'Macronutrientes']);
        Input::forceCreate([
            'id' => 9002, 'description' => 'AMINOACIDOS DE PRUEBA 10%', 'is_active' => true,
            'tipo_input' => 'ambos', 'orden_enum' => 1, 'category_id' => 1,
            'unidad' => 'g/Kg', 'mult' => 100, 'div' => 10,
        ]);
        $diluent = Diluent::create(['denominacion_generica' => 'AGUA INYECTABLE', 'available_for_nutrition' => true]);

        $case = app(ClinicalPayload::class)->normalize('nutricionales', [
            'peso' => 50, 'fecha_nacimiento' => '1990-01-01', 'npt' => 'ADULT',
            'volumen_total' => 1000, 'via_administracion' => 'Central', 'i_9002_g/Kg' => 50,
            'nutrition_diluents' => [$diluent->id => ['volume_ml' => 100]],
        ]);

        $water = collect($case['mixtures'][0]['components'])->firstWhere('composition_group', 'water');
        $this->assertSame(0.0, $case['mixtures'][0]['calculated_water_ml']);
        $this->assertSame(100.0, $water['volume_ml']);
        $this->assertArrayNotHasKey('calculated', $water);
    }

    private function rule(array $attributes): ValidationRule
    {
        return ValidationRule::create(array_merge([
            'name' => 'Regla sintética de prueba',
            'population' => 'adult',
            'severity' => 'blocking',
            'status' => 'active',
            'version' => 1,
            'is_enforced' => true,
        ], $attributes));
    }

    private function adultCase(array $components): array
    {
        return [
            'patient' => ['weight_kg' => 50, 'age_days' => 30 * 365],
            'clinical_context' => ['patient_factors' => ''],
            'mixtures' => [[
                'mode' => 'ADULT',
                'volume_ml' => 1000,
                'components' => $components,
            ]],
        ];
    }

    private function componentData(int $id, int $category, string $name, float $quantity, float $total, string $field): array
    {
        return [
            'input_id' => $id,
            'category_id' => $category,
            'medicine' => $name,
            'quantity' => $quantity,
            'total_amount' => $total,
            'volume_ml' => $quantity,
            'weight_factor_applied' => false,
            'field' => $field,
        ];
    }
}
