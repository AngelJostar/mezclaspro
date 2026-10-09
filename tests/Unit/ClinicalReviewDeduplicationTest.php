<?php

namespace Tests\Unit;

use App\Services\Clinical\ClinicalReviewService;
use ReflectionClass;
use Tests\TestCase;

class ClinicalReviewDeduplicationTest extends TestCase
{
    public function test_ai_finding_that_repeats_a_deterministic_rule_code_is_removed(): void
    {
        $findings = $this->deduplicate([
            [
                // Composition engines can associate the aggregate rule with a
                // different active component. The exact rule code is authoritative.
                'field' => 'i_33_mg',
                'message' => 'La composicion no esta permitida.',
                'calculation' => 'Regla NP.R6.ADULT.COMP.CATALOG fallida.',
                'suggestion' => 'Ajusta la albumina.',
            ],
        ], [$this->deterministicFinding()]);

        $this->assertSame([], $findings);
    }

    public function test_ai_finding_that_repeats_the_deterministic_rule_name_is_removed(): void
    {
        $findings = $this->deduplicate([
            [
                'field' => 'i_21_g',
                'message' => 'Combinaciones permitidas R6 para adulto: la mezcla no coincide.',
                'calculation' => '',
                'suggestion' => '',
            ],
        ], [$this->deterministicFinding()]);

        $this->assertSame([], $findings);
    }

    public function test_distinct_finding_on_the_same_field_is_preserved(): void
    {
        $finding = [
            'field' => 'i_21_g',
            'message' => 'La ficha tecnica reporta una condicion cualitativa diferente.',
            'calculation' => '',
            'suggestion' => '',
        ];

        $this->assertSame([$finding], $this->deduplicate([$finding], [$this->deterministicFinding()]));
    }

    public function test_rephrased_composition_catalog_duplicate_is_removed(): void
    {
        $findings = $this->deduplicate([
            [
                'field' => 'i_33_mg',
                'message' => 'La composición capturada no está permitida por el catálogo activo.',
                'calculation' => '',
                'suggestion' => 'Corrige la combinación de componentes conforme al catálogo.',
            ],
        ], [$this->deterministicFinding()]);

        $this->assertSame([], $findings);
    }

    public function test_ai_correction_is_transferred_to_the_deterministic_finding_before_deduplication(): void
    {
        $deterministic = [[
            'field' => 'i_2_g',
            'message' => 'Concentración final de dextrosa: debe estar entre 5% y 35%.',
            'suggestion' => '',
            'rule_code' => 'NP.R6.CHEM.DEXTROSE.PERCENT',
        ]];
        $ai = [[
            'field' => 'i_2_g',
            'message' => 'Concentración final de dextrosa fuera de rango.',
            'calculation' => 'Regla NP.R6.CHEM.DEXTROSE.PERCENT fallida.',
            'suggestion' => 'Aumenta la dextrosa total a por lo menos 172.096 g para alcanzar 5% en 3441.92 mL.',
        ]];

        $reflection = new ReflectionClass(ClinicalReviewService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('enrichDeterministicFindings');
        $method->setAccessible(true);
        $enriched = $method->invoke($service, $deterministic, $ai);

        $this->assertSame($ai[0]['suggestion'], $enriched[0]['suggestion']);
        $this->assertSame([], $this->deduplicate($ai, $enriched));
    }

    public function test_internal_field_identifiers_are_replaced_with_generic_component_names(): void
    {
        $finding = [
            'field' => 'i_10_g',
            'message' => 'Exceso detectado en i_10_g/Kg.',
            'calculation' => 'i_10_g/Kg = 7.98 g/kg/día.',
            'suggestion' => 'Campo i_10_g/Kg debe ajustarse a 7 g/kg/día.',
        ];
        $case = ['mixtures' => [['components' => [[
            'field' => 'i_10_g',
            'composition_group' => 'lipids',
            'medicine' => 'EMULSION LIPIDICA 20%',
        ]]]]];

        $reflection = new ReflectionClass(ClinicalReviewService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('replaceInternalIdentifiers');
        $method->setAccessible(true);
        $result = $method->invoke($service, $finding, $case);

        $this->assertSame('i_10_g', $result['field']);
        $this->assertStringContainsString('lípidos', $result['message']);
        $this->assertStringContainsString('Campo lípidos', $result['suggestion']);
        $this->assertStringNotContainsString('i_10_g', implode(' ', [$result['message'], $result['calculation'], $result['suggestion']]));
    }

    private function deterministicFinding(): array
    {
        return [
            'field' => 'i_21_g',
            'message' => 'Combinaciones permitidas R6 para adulto: la regla no se cumple.',
            'rule_code' => 'NP.R6.ADULT.COMP.CATALOG',
        ];
    }

    private function deduplicate(array $findings, array $deterministic): array
    {
        $reflection = new ReflectionClass(ClinicalReviewService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('withoutDeterministicDuplicates');
        $method->setAccessible(true);

        return $method->invoke($service, $findings, $deterministic);
    }
}
