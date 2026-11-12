<?php

namespace App\Services\ValidationRules;

use App\Models\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ClinicalRuleEvaluator
{
    public function __construct(
        private CompositionRuleEngine $composition,
        private MathematicalRuleEngine $mathematical,
    ) {}

    public function evaluate(array $case): array
    {
        if (! Schema::hasTable('validation_rules')) {
            return ['findings' => [], 'evaluations' => []];
        }

        $mode = $case['mixtures'][0]['mode'] ?? null;
        $population = $mode === 'INF' ? 'pediatric' : ($mode === 'ADULT' ? 'adult' : null);
        if (! $population) {
            return ['findings' => [], 'evaluations' => []];
        }

        $rules = ValidationRule::query()
            ->where('is_enforced', true)
            ->where('status', 'active')
            ->whereIn('population', [$population, 'both'])
            ->orderBy('engine')
            ->orderBy('code')
            ->get();

        $findings = [];
        $evaluations = [];
        foreach ($case['mixtures'] ?? [] as $mixtureIndex => $mixture) {
            $firstFindingIndex = count($findings);
            $facts = $this->facts($case, $mixture);
            $facts['suggested_total_volume_ml'] = $this->suggestedTotalVolume($rules, $facts);
            $matchedCompositionCode = null;
            foreach ($rules as $rule) {
                $applicable = $this->applicable($rule->configuration['applicability'] ?? [], $facts);
                if ($applicable !== true) {
                    $evaluations[] = $this->audit($rule, $mixtureIndex, $applicable === false ? 'not_applicable' : 'not_evaluable');
                    continue;
                }

                $source = $rule->configuration['source_variable'] ?? null;
                if ($rule->engine === 'mathematical' && $source
                    && in_array($source, $facts['unavailable_sources'], true)) {
                    $evaluations[] = $this->audit($rule, $mixtureIndex, 'not_evaluable', [
                        'message' => 'El dato de origen requiere una conversión o captura explícita que no está disponible.',
                    ]);
                    continue;
                }

                $result = $rule->engine === 'composition'
                    ? $this->composition->evaluate($rule->configuration, $facts)
                    : $this->mathematical->evaluate($rule->configuration, $facts);

                if ($result['incomplete'] ?? false) {
                    $evaluations[] = $this->audit($rule, $mixtureIndex, 'not_evaluable', $result);
                    continue;
                }

                $evaluations[] = $this->audit($rule, $mixtureIndex, $result['passed'] ? 'passed' : 'failed', $result);
                if ($rule->engine === 'composition' && $result['passed']) {
                    $matchedCode = trim((string) data_get($result, 'result.matched_code', ''));
                    if ($matchedCode !== '' && $matchedCode !== 'single-component-exception') {
                        $matchedCompositionCode = $matchedCode;
                    }
                }
                if (! $result['passed']) {
                    $findings[] = $this->finding($rule, $mixtureIndex, $facts, $result, $matchedCompositionCode);
                }
            }

            $mixtureFindings = array_splice($findings, $firstFindingIndex);
            $mixtureFindings = $this->prioritizeDependentFindings($mixtureFindings);
            $mixtureFindings = $this->coordinateConflictingSuggestions($mixtureFindings, $facts);
            foreach ($mixtureFindings as &$finding) {
                unset($finding['_group'], $finding['_configuration'], $finding['_result']);
            }
            unset($finding);
            array_push($findings, ...$mixtureFindings);
        }

        return ['findings' => $findings, 'evaluations' => $evaluations];
    }

    private function facts(array $case, array $mixture): array
    {
        $weight = (float) data_get($case, 'patient.weight_kg', 0);
        $ageDays = (int) data_get($case, 'patient.age_days', 0);
        $facts = [
            'patient' => ['weight_kg' => $weight, 'age_days' => $ageDays],
            'mixture' => [
                'total_volume_ml' => (float) ($mixture['volume_ml'] ?? 0),
                'component_volume_ml' => 0.0,
            ],
            'active_groups' => [],
            'active_component_ids' => [],
            'active_component_terms' => [],
            'active_component_names' => [],
            'group_component_names' => [],
            'group_fields' => [],
            'group_input_fields' => [],
            'group_captured_values' => [],
            'group_input_units' => [],
            'group_weighted_inputs' => [],
            'unavailable_sources' => [],
            'groups' => [
                'amino_acids' => ['dose_g_kg_day' => 0.0],
                'dextrose' => ['dose_g_kg_day' => 0.0],
                'lipids' => ['dose_g_kg_day' => 0.0],
                'sodium' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'potassium' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'magnesium' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'calcium' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'phosphate' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'calcium_phosphate' => ['total_meq' => 0.0],
                'chloride' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'acetate' => ['total_meq' => 0.0, 'dose_meq_kg_day' => 0.0],
                'water' => ['volume_ml' => 0.0],
            ],
        ];

        foreach ($mixture['components'] ?? [] as $component) {
            $category = (int) ($component['category_id'] ?? 0);
            $name = $this->normalize((string) ($component['medicine'] ?? ''));
            $group = $component['composition_group'] ?? $this->group($category, $name);
            $field = (string) ($component['field'] ?? 'observaciones');
            $total = (float) ($component['total_amount'] ?? $component['quantity'] ?? 0);
            $dose = ($component['weight_factor_applied'] ?? false)
                ? (float) ($component['quantity'] ?? 0)
                : ($weight > 0 ? $total / $weight : 0.0);

            if ($group) {
                $facts['active_groups'][] = $group;
                $facts['group_fields'][$group] ??= $field;
                $facts['group_input_fields'][$group][] = $field;
                $facts['group_captured_values'][$group][] = (float) ($component['quantity'] ?? 0);
                $facts['group_input_units'][$group][] = trim((string) ($component['unit'] ?? ''));
                $facts['group_weighted_inputs'][$group][] = (bool) ($component['weight_factor_applied'] ?? false);
            }
            if (isset($component['input_id'])) {
                $facts['active_component_ids'][] = (int) $component['input_id'];
            } elseif (isset($component['rule_component_id'])) {
                $facts['active_component_ids'][] = (int) $component['rule_component_id'];
            }
            if (($componentName = trim((string) ($component['medicine'] ?? ''))) !== '') {
                $facts['active_component_names'][] = $componentName;
                if ($group) $facts['group_component_names'][$group][] = $componentName;
            }
            array_push($facts['active_component_terms'], ...$this->terms($name));

            if (in_array($group, ['amino_acids', 'dextrose', 'lipids'], true)) {
                $facts['groups'][$group]['dose_g_kg_day'] += $dose;
            }
            if ($group === 'water') {
                $facts['groups']['water']['volume_ml'] += (float) ($component['volume_ml'] ?? $component['quantity'] ?? 0);
            } else {
                $facts['mixture']['component_volume_ml'] += (float) ($component['volume_ml'] ?? 0);
            }
            if ($category === 4) {
                foreach ($this->electrolytes($name) as $electrolyte) {
                    $facts['groups'][$electrolyte]['total_meq'] += $total;
                    $facts['groups'][$electrolyte]['dose_meq_kg_day'] += $weight > 0 ? $total / $weight : 0.0;
                    $facts['group_fields'][$electrolyte] ??= $field;
                    $facts['group_input_fields'][$electrolyte][] = $field;
                    $facts['group_component_names'][$electrolyte][] = trim((string) ($component['medicine'] ?? ''));
                    $facts['group_captured_values'][$electrolyte][] = (float) ($component['quantity'] ?? 0);
                    $facts['group_input_units'][$electrolyte][] = trim((string) ($component['unit'] ?? ''));
                    $facts['group_weighted_inputs'][$electrolyte][] = (bool) ($component['weight_factor_applied'] ?? false);
                }
            }
        }

        $facts['groups']['calcium_phosphate']['total_meq'] =
            $facts['groups']['calcium']['total_meq'] + $facts['groups']['phosphate']['total_meq'];
        foreach (['group_input_fields', 'group_captured_values', 'group_input_units', 'group_weighted_inputs'] as $key) {
            $facts[$key]['calcium_phosphate'] = array_merge(
                $facts[$key]['calcium'] ?? [],
                $facts[$key]['phosphate'] ?? []
            );
        }
        $facts['active_groups'] = array_values(array_unique($facts['active_groups']));
        $facts['active_component_ids'] = array_values(array_unique($facts['active_component_ids']));
        $facts['active_component_terms'] = array_values(array_unique($facts['active_component_terms']));
        $facts['active_component_names'] = array_values(array_unique($facts['active_component_names']));
        foreach ($facts['group_component_names'] as &$names) {
            $names = array_values(array_unique($names));
        }
        unset($names);
        $facts['calculated_water'] = (float) ($mixture['calculated_water_ml'] ?? 0) > 0;
        if (! in_array('water', $facts['active_groups'], true)) {
            $facts['unavailable_sources'][] = 'groups.water.volume_ml';
        }
        if (in_array('saline', $facts['active_groups'], true)) {
            $facts['unavailable_sources'][] = 'groups.sodium.total_meq';
            $facts['unavailable_sources'][] = 'groups.sodium.dose_meq_kg_day';
            $facts['unavailable_sources'][] = 'groups.chloride.total_meq';
            $facts['unavailable_sources'][] = 'groups.chloride.dose_meq_kg_day';
        }
        $facts['patient_class'] = $this->patientClass($ageDays);
        $facts['clinical_text'] = $this->normalize((string) data_get($case, 'clinical_context.patient_factors', ''));

        return $facts;
    }

    private function applicable(array $conditions, array $facts): ?bool
    {
        if (($group = $conditions['required_group'] ?? null) && ! in_array($group, $facts['active_groups'], true)) {
            return false;
        }
        if (($ageGroup = $conditions['age_group'] ?? null) && ! $this->matchesAgeGroup($ageGroup, $facts)) {
            return false;
        }
        if (($patientGroup = $conditions['patient_group'] ?? null) && ! $this->matchesPatientGroup($patientGroup, $facts)) {
            return false;
        }
        if ($stage = $conditions['clinical_stage'] ?? null) {
            return str_contains($facts['clinical_text'], $this->normalize($stage)) ?: null;
        }

        return true;
    }

    private function matchesAgeGroup(string $ageGroup, array $facts): bool
    {
        $years = $facts['patient']['age_days'] / 365.2425;
        $ageGroup = $this->normalize($ageGroup);

        return match (true) {
            str_contains($ageGroup, 'MENOR DE 11') => $years < 11,
            str_contains($ageGroup, '11-20') => $years >= 11 && $years <= 20,
            $ageGroup === 'ADULTO' => $years > 20,
            default => false,
        };
    }

    private function matchesPatientGroup(string $patientGroup, array $facts): bool
    {
        $group = $this->normalize($patientGroup);
        $class = $facts['patient_class'];
        if (str_contains($group, 'INFANTIL MENOR DE 50 KG O ADOLESCENTE')) {
            return ($class === 'child' && $facts['patient']['weight_kg'] < 50) || $class === 'adolescent';
        }
        if (str_contains($group, 'NEONATO, LACTANTE O INFANTIL')) return in_array($class, ['neonate', 'infant', 'child'], true);
        if (str_contains($group, 'NEONATO O LACTANTE')) return in_array($class, ['neonate', 'infant'], true);
        if (str_contains($group, 'LACTANTE')) return $class === 'infant';
        if (str_contains($group, 'NEONATO')) return $class === 'neonate';
        if (str_contains($group, 'ADOLESCENTE')) return $class === 'adolescent';
        if (str_contains($group, 'INFANTE') || str_contains($group, 'INFANTIL')) return $class === 'child';

        return false;
    }

    private function patientClass(int $ageDays): string
    {
        if ($ageDays <= 28) return 'neonate';
        if ($ageDays <= 365) return 'infant';
        if ($ageDays < 11 * 365.2425) return 'child';
        if ($ageDays <= 20 * 365.2425) return 'adolescent';
        return 'adult';
    }

    private function group(int $category, string $name): ?string
    {
        if ($category === 5) {
            if (str_contains($name, 'OLIGOELEMENT') || str_contains($name, 'OLIGOMETAL')
                || preg_match('/\b(COBRE|CROMO|MANGANESO|SELENIO|ZINC)\b/', $name)) {
                return 'trace_elements';
            }

            return str_contains($name, 'VITAMIN') ? 'vitamins' : 'medications';
        }

        return match ($category) {
            1 => 'amino_acids', 2 => 'dextrose', 3 => 'lipids', 4 => 'electrolytes',
            7 => 'water', 8 => 'saline',
            default => null,
        };
    }

    private function terms(string $name): array
    {
        $terms = [];
        if (str_contains($name, 'AMINOACIDOS PEDIATRICOS') && str_contains($name, '10%')) $terms[] = 'aa_pediatric_10';
        elseif (str_contains($name, 'AMINOACIDOS') && str_contains($name, '10%')) $terms[] = 'aa_adult_10';
        if (str_contains($name, 'AMINOACIDOS') && str_contains($name, '8.5%')) $terms[] = 'aa_adult_8_5';
        if (str_contains($name, 'AMINOACIDOS') && preg_match('/(?:^|\D)8%(?:\D|$)/', $name)) $terms[] = 'aa_adult_8';
        if (str_contains($name, 'INSULINA')) $terms[] = 'insulin';
        return $terms;
    }

    private function electrolytes(string $name): array
    {
        $items = [];
        if (str_contains($name, 'SODIO')) $items[] = 'sodium';
        if (str_contains($name, 'POTASIO')) $items[] = 'potassium';
        if (str_contains($name, 'MAGNESIO')) $items[] = 'magnesium';
        if (str_contains($name, 'CALCIO')) $items[] = 'calcium';
        if (str_contains($name, 'FOSFATO')) $items[] = 'phosphate';
        if (str_contains($name, 'CLORURO')) $items[] = 'chloride';
        if (str_contains($name, 'ACETATO')) $items[] = 'acetate';
        return $items;
    }

    private function finding(
        ValidationRule $rule,
        int $mixtureIndex,
        array $facts,
        array $result,
        ?string $matchedCompositionCode = null
    ): array
    {
        $source = (string) ($rule->configuration['source_variable'] ?? '');
        preg_match('/^groups\.([^.]+)/', $source, $match);
        $field = $facts['group_fields'][$match[1] ?? ''] ?? 'observaciones';
        $compositionExplanation = $rule->engine === 'composition'
            ? $this->compositionExplanation($result['result'] ?? [])
            : null;
        $calculation = $rule->engine === 'mathematical'
            ? $this->mathematicalCalculation($rule->configuration, $result)
            : $compositionExplanation['calculation'];
        $mathematicalSuggestion = $rule->engine === 'mathematical'
            ? $this->mathematicalSuggestion($rule, $facts, $result, $match[1] ?? null)
            : '';
        $suggestion = $compositionExplanation['suggestion'] ?? $mathematicalSuggestion;
        $suggestedChanges = $rule->engine === 'mathematical'
            ? $this->suggestedChangesFromText($suggestion, $field, $facts, $match[1] ?? null)
            : [];
        $projectedVolume = (float) ($facts['suggested_total_volume_ml'] ?? 0);
        $currentVolume = (float) data_get($facts, 'mixture.total_volume_ml', 0);
        if ($rule->engine === 'mathematical' && ($match[1] ?? null) !== 'water'
            && $projectedVolume > 0 && $projectedVolume < $currentVolume
            && collect($suggestedChanges)->contains(fn (array $change) => $change['field'] !== 'volumen_total')) {
            $suggestedChanges[] = ['field' => 'volumen_total', 'value' => round($projectedVolume, 4)];
            $componentChange = collect($suggestedChanges)->first(fn (array $change) => $change['field'] !== 'volumen_total');
            $component = implode(' / ', $facts['group_component_names'][$match[1] ?? ''] ?? [])
                ?: $this->groupLabel((string) ($match[1] ?? ''));
            $unit = trim((string) ($rule->configuration['unit'] ?? ''));
            $suggestion = 'Ajuste conjunto para '.$component.': captura '.$componentChange['value'].' '.$unit
                .' y un volumen total máximo de '.round($projectedVolume, 4).' mL.';
        }

        return [
            'field' => $field,
            'severity' => $rule->severity,
            'category' => 'deterministic_rule',
            'message' => $rule->engine === 'composition'
                ? $compositionExplanation['message']
                : $rule->name.': '.($result['message'] ?? $rule->description ?? 'La regla no se cumple.'),
            'calculation' => trim($calculation),
            'suggestion' => $suggestion,
            'suggested_changes' => $suggestedChanges,
            'source_ids' => ['SYSTEM'],
            'rule_code' => $rule->code,
            'composition_code' => $matchedCompositionCode,
            'rule_version' => $rule->version,
            'mixture_index' => $mixtureIndex,
            '_group' => $match[1] ?? null,
            '_configuration' => $rule->configuration,
            '_result' => $result,
        ];
    }

    private function mathematicalSuggestion(ValidationRule $rule, array $facts, array $result, ?string $group): string
    {
        $configuration = $rule->configuration;
        if (!is_numeric($result['value'] ?? null)) return '';

        $formula = $configuration['formula_template'] ?? null;
        $projectedVolume = (float) ($facts['suggested_total_volume_ml'] ?? 0);
        $projectionPrefix = '';
        $withProjection = static fn (string $message): string => $message;
        if ($group !== 'water' && $projectedVolume > 0
            && in_array($formula, ['percentage_of_volume', 'amount_per_1000_ml', 'volume_capacity_per_1000_ml', 'dose_times_weight_percentage_of_volume'], true)) {
            $projectedFacts = $facts;
            data_set($projectedFacts, 'mixture.total_volume_ml', $projectedVolume);
            $projectedResult = $this->mathematical->evaluate($configuration, $projectedFacts);
            if (!($projectedResult['incomplete'] ?? false)) {
                $unit = trim((string) ($configuration['unit'] ?? $projectedResult['unit'] ?? ''));
                if ($projectedResult['passed']) {
                    return 'Primero ajusta el volumen total a '.round($projectedVolume, 4).' mL. '
                        .'Con ese volumen, el resultado proyectado es '.round((float) $projectedResult['value'], 4).' '.$unit
                        .' y cumple el criterio '.$this->criterion($configuration)
                        .'; no necesitas modificar este componente por esta regla.';
                }
                $facts = $projectedFacts;
                $result = $projectedResult;
                $projectionPrefix = 'Considerando primero el volumen total sugerido de '.round($projectedVolume, 4).' mL, ';
                $withProjection = static fn (string $message): string => $projectionPrefix.lcfirst($message);
            }
        }

        $operator = $configuration['operator'] ?? null;
        $value = (float) $result['value'];
        $component = implode(' / ', $facts['group_component_names'][$group] ?? []) ?: $this->groupLabel((string) $group);

        if (in_array($formula, ['direct_value', 'volume_capacity_per_1000_ml'], true)) {
            $rawTarget = $formula === 'volume_capacity_per_1000_ml'
                ? ($result['comparison_value'] ?? null)
                : match ($operator) {
                '<', '<=', '>', '>=' => isset($configuration['threshold_value']) ? (float) $configuration['threshold_value'] : null,
                'between' => $value < (float) ($configuration['threshold_min'] ?? 0)
                    ? (float) $configuration['threshold_min']
                    : ($value > (float) ($configuration['threshold_max'] ?? 0) ? (float) $configuration['threshold_max'] : null),
                default => null,
            };
            if ($rawTarget === null) return '';

            $increase = $value < $rawTarget;
            $strict = in_array($operator, ['<', '>'], true);
            $target = $this->safeCaptureTarget($rawTarget, $increase, $strict);

            $difference = $target - $value;
            $action = $difference >= 0 ? 'aumentar' : 'disminuir';
            $unit = trim((string) ($configuration['unit'] ?? $result['unit'] ?? ''));

            if ($formula === 'volume_capacity_per_1000_ml') {
                return $withProjection('La suma de calcio y fosfato es '.round($value, 4).' '.$unit
                    .' y la capacidad máxima con ese volumen es '.round($target, 4).' '.$unit
                    .'. Ajusta la suma a un máximo de '.round($target, 4).' '.$unit
                    .' (disminuir '.round(abs($difference), 4).' '.$unit.').');
            }

            $capturedValues = $facts['group_captured_values'][$group] ?? [];
            $componentNames = $facts['group_component_names'][$group] ?? [];
            $inputUnits = $facts['group_input_units'][$group] ?? [];
            if (count($capturedValues) > 1 && count($capturedValues) === count($componentNames) && $value > 0) {
                $scale = $target / $value;
                $distributed = [];
                $distributedTotal = 0.0;
                $lastIndex = array_key_last($capturedValues);
                foreach ($capturedValues as $index => $capturedValue) {
                    if ($index === $lastIndex) {
                        $suggestedValue = max(0, round($target - $distributedTotal, 4));
                    } else {
                        $rawSuggested = (float) $capturedValue * $scale;
                        $suggestedValue = $difference < 0
                            ? floor($rawSuggested * 10000) / 10000
                            : ceil($rawSuggested * 10000) / 10000;
                        $distributedTotal += $suggestedValue;
                    }
                    $inputUnit = trim((string) ($inputUnits[$index] ?? $unit));
                    $distributed[] = $componentNames[$index].': '.round($suggestedValue, 4).' '.$inputUnit;
                }

                return $withProjection('Este límite aplica a la suma de los componentes indicados: '.round($value, 4).' '.$unit
                    .'. El total combinado debe ajustarse a '.round($target, 4).' '.$unit.'. '
                    .'Una distribución proporcional es '.implode('; ', $distributed)
                    .'. También puedes redistribuirlos de otra forma, siempre que la suma no exceda el límite.');
            }

            return $withProjection('La dosis de '.$component.' actualmente es '.round($value, 4).' '.$unit
                .'. Para cumplir esta regla debe ajustarse a '.round($target, 4).' '.$unit.' ('
                .$action.' '.round(abs($difference), 4).' '.$unit
                .'). Captura el valor ajustado en '.$component.' y confirma el cambio con el profesional responsable.');
        }

        if ($formula === 'percentage_of_volume') {
            $volume = (float) data_get($facts, 'mixture.total_volume_ml', 0);
            $source = (float) data_get($facts, (string) ($configuration['source_variable'] ?? ''), 0);
            if ($volume <= 0 || $source <= 0) return '';

            $targetPercent = $this->failedPercentageTarget($configuration, $value);
            if ($targetPercent === null) return '';

            $increase = $value < $targetPercent;
            $strict = in_array($operator, ['<', '>'], true);

            if ($group === 'water' && ($facts['calculated_water'] ?? false) && $targetPercent > 0 && $targetPercent < 100) {
                $componentVolume = (float) data_get($facts, 'mixture.component_volume_ml', 0);
                if ($componentVolume <= 0) {
                    $componentVolume = max(0, $volume - $source);
                }
                if ($componentVolume > 0) {
                    $rawWaterTarget = $componentVolume * $targetPercent / (100 - $targetPercent);
                    $waterTarget = $this->safeCaptureTarget($rawWaterTarget, $increase, $strict);
                    $totalVolumeTarget = $componentVolume + $waterTarget;
                    return 'El volumen total sugerido es de '.round($totalVolumeTarget, 4).' mL.';
                }
            }

            $rawTarget = $volume * $targetPercent / 100;
            $target = $this->safeCaptureTarget($rawTarget, $increase, $strict);
            $difference = $target - $source;
            $action = $difference >= 0 ? 'aumentar' : 'disminuir';

            return $withProjection('Para cumplir el límite de '.$targetPercent.'%, '.$component.' debe quedar en '
                .($increase ? 'al menos ' : 'máximo ').round($target, 4).' mL ('
                .$action.' '.round(abs($difference), 4).' mL respecto al valor actual).');
        }

        if ($formula === 'amount_per_1000_ml') {
            $volume = (float) data_get($facts, 'mixture.total_volume_ml', 0);
            $targetConcentration = $this->failedPercentageTarget($configuration, $value);
            if ($volume <= 0 || $targetConcentration === null) return '';

            $increase = $value < $targetConcentration;
            $strict = in_array($operator, ['<', '>'], true);
            $rawTargetTotal = $volume * $targetConcentration / 1000;
            $targetTotal = $this->safeCaptureTarget($rawTargetTotal, $increase, $strict);
            $capturedValues = $facts['group_captured_values'][$group] ?? [];
            $weightedInputs = $facts['group_weighted_inputs'][$group] ?? [];
            $allInputsAreWeighted = $weightedInputs !== [] && !in_array(false, $weightedInputs, true);
            $weight = (float) data_get($facts, 'patient.weight_kg', 0);
            $captured = array_sum($capturedValues);
            $targetCaptured = $allInputsAreWeighted && $weight > 0
                ? $this->safeCaptureTarget($rawTargetTotal / $weight, $increase, $strict)
                : $targetTotal;
            $inputUnits = array_values(array_filter($facts['group_input_units'][$group] ?? []));
            $inputUnit = count(array_unique($inputUnits)) === 1 ? $inputUnits[0] : trim((string) ($configuration['unit'] ?? ''));
            $difference = $targetCaptured - $captured;

            return $withProjection('Para cumplir el límite de '.$targetConcentration.' '.($configuration['unit'] ?? '')
                .', el valor capturado de '.$component.' debe ajustarse de '.round($captured, 4).' '.$inputUnit
                .' a '.round($targetCaptured, 4).' '.$inputUnit.' ('
                .($difference >= 0 ? 'aumentar ' : 'disminuir ').round(abs($difference), 4).' '.$inputUnit.').');
        }

        if ($formula !== 'dose_times_weight_percentage_of_volume') return '';

        $volume = (float) data_get($facts, 'mixture.total_volume_ml', 0);
        $weight = (float) data_get($facts, 'patient.weight_kg', 0);
        $dose = (float) data_get($facts, (string) ($configuration['source_variable'] ?? ''), 0);
        if ($volume <= 0 || $weight <= 0 || $dose <= 0) return '';

        $actual = $dose * $weight;
        $targetPercent = $this->failedPercentageTarget($configuration, $value);
        if ($targetPercent === null) return '';

        $increase = $value < $targetPercent;
        $strict = in_array($operator, ['<', '>'], true);
        $rawTarget = $volume * $targetPercent / 100;
        $target = $this->safeCaptureTarget($rawTarget, $increase, $strict);
        $difference = $target - $actual;
        $action = $difference >= 0 ? 'aumentar' : 'disminuir';

        $weightedInputs = $facts['group_weighted_inputs'][$group] ?? [];
        $capturedValues = $facts['group_captured_values'][$group] ?? [];
        $inputUnits = array_values(array_filter($facts['group_input_units'][$group] ?? []));
        $allInputsAreWeighted = $weightedInputs !== [] && ! in_array(false, $weightedInputs, true);
        if ($allInputsAreWeighted && $capturedValues !== []) {
            $captured = array_sum($capturedValues);
            $rawTargetCaptured = $rawTarget / $weight;
            $targetCaptured = $this->safeCaptureTarget($rawTargetCaptured, $increase, $strict);
            $capturedDifference = $targetCaptured - $captured;
            $capturedAction = $capturedDifference >= 0 ? 'aumentar' : 'disminuir';
            $inputUnit = count(array_unique($inputUnits)) === 1 ? $inputUnits[0] : 'g/kg';

            return $withProjection('Para cumplir el límite de '.$targetPercent.'%, cambia el valor capturado de '.$component
                .' de '.round($captured, 4).' '.$inputUnit.' a aproximadamente '.round($targetCaptured, 4).' '.$inputUnit.' ('
                .$capturedAction.' '.round(abs($capturedDifference), 4).' '.$inputUnit
                .'). El cálculo interno equivale a '.round($target, 4).' g totales.');
        }

        return $withProjection('Para cumplir el límite de '.$targetPercent.'%, el valor de '.$component
            .' tendría que cambiar de '.round($actual, 4).' g a aproximadamente '.round($target, 4).' g ('
            .$action.' '.round(abs($difference), 4).' g). Captura el nuevo total en el campo de '.$component
            .' y confirma el ajuste con el profesional responsable.');
    }

    private function failedPercentageTarget(array $configuration, float $value): ?float
    {
        return match ($configuration['operator'] ?? null) {
            '<', '<=', '>', '>=' => isset($configuration['threshold_value'])
                ? (float) $configuration['threshold_value']
                : null,
            'between' => $value < (float) ($configuration['threshold_min'] ?? 0)
                ? (float) $configuration['threshold_min']
                : ($value > (float) ($configuration['threshold_max'] ?? 0)
                    ? (float) $configuration['threshold_max']
                    : null),
            default => null,
        };
    }

    private function safeCaptureTarget(float $target, bool $increase, bool $strict, int $precision = 4): float
    {
        $factor = 10 ** $precision;
        if ($increase) {
            $scaled = $strict ? floor($target * $factor) + 1 : ceil(($target * $factor) - 0.0000001);
        } else {
            $scaled = $strict ? ceil($target * $factor) - 1 : floor(($target * $factor) + 0.0000001);
        }

        return $scaled / $factor;
    }

    private function compositionExplanation(array $details): array
    {
        $componentNames = $details['detected_component_names'] ?? [];
        $groups = array_map(fn (string $group) => $this->groupLabel($group), $details['detected_groups'] ?? []);
        $detected = $componentNames
            ? implode(', ', $componentNames)
            : ($groups ? implode(', ', $groups) : 'sin componentes reconocidos');

        $alternatives = collect($details['closest_alternatives'] ?? [])->map(function (array $alternative) {
            $changes = [];
            if ($missing = $this->labels($alternative['missing_groups'] ?? [])) {
                $changes[] = 'agregar '.implode(', ', $missing);
            }
            if ($unexpected = $this->labels($alternative['unexpected_groups'] ?? [])) {
                $changes[] = 'retirar '.implode(', ', $unexpected);
            }
            if ($missingTerms = $this->termLabels($alternative['missing_terms'] ?? [])) {
                $changes[] = 'incluir '.implode(', ', $missingTerms);
            }
            if ($forbiddenTerms = $this->termLabels($alternative['forbidden_terms_present'] ?? [])) {
                $changes[] = 'retirar '.implode(', ', $forbiddenTerms);
            }

            $code = trim((string) ($alternative['code'] ?? ''));
            $label = trim((string) ($alternative['label'] ?? 'Alternativa permitida'));
            $identifiedLabel = $code !== '' ? $code.' — '.$label : $label;

            return $identifiedLabel
                .($changes ? ' ('.implode('; ', $changes).')' : '');
        })->values()->all();

        return [
            'message' => 'La combinación capturada no está permitida. Componentes detectados: '.$detected.'.',
            'calculation' => $groups
                ? 'Grupos identificados: '.implode(', ', $groups).'.'
                : 'No fue posible identificar grupos válidos para la composición.',
            'suggestion' => $alternatives
                ? 'Alternativas permitidas más cercanas: '.implode(' | ', $alternatives).'.'
                : 'Consulta el catálogo R6 y selecciona una combinación permitida.',
        ];
    }

    private function labels(array $groups): array
    {
        return array_map(fn (string $group) => $this->groupLabel($group), $groups);
    }

    private function groupLabel(string $group): string
    {
        return [
            'amino_acids' => 'aminoácidos',
            'dextrose' => 'dextrosa',
            'lipids' => 'lípidos',
            'electrolytes' => 'electrolitos',
            'trace_elements' => 'elementos traza',
            'vitamins' => 'vitaminas',
            'water' => 'agua',
            'saline' => 'solución salina',
            'medications' => 'medicamentos',
            'additives' => 'aditivos',
        ][$group] ?? str_replace('_', ' ', $group);
    }

    private function termLabels(array $terms): array
    {
        $labels = [
            'aa_adult_10' => 'aminoácidos adultos 10%',
            'aa_adult_8_5' => 'aminoácidos adultos 8.5%',
            'aa_adult_8' => 'aminoácidos adultos 8%',
            'aa_pediatric_10' => 'aminoácidos pediátricos 10%',
            'insulin' => 'insulina',
        ];

        return array_map(fn (string $term) => $labels[$term] ?? str_replace('_', ' ', $term), $terms);
    }

    private function criterion(array $configuration): string
    {
        $unit = $configuration['unit'] ?? '';
        return ($configuration['operator'] ?? null) === 'between'
            ? 'entre '.$configuration['threshold_min'].' y '.$configuration['threshold_max'].' '.$unit
            : ($configuration['operator'] ?? '').' '.($configuration['threshold_value'] ?? '').' '.$unit;
    }

    private function mathematicalCalculation(array $configuration, array $result): string
    {
        $value = $result['value'] ?? 'no disponible';
        $unit = $result['unit'] ?? '';
        if (($configuration['formula_template'] ?? null) === 'volume_capacity_per_1000_ml') {
            return 'La suma de mEq capturados: '.$value.' '.$unit.'; capacidad máxima es de: '
                .($result['comparison_value'] ?? 'no disponible').' '.$unit
                .' = (volumen total × '.($configuration['threshold_value'] ?? '—').') ÷ 1000.';
        }

        return 'Resultado: '.$value.' '.$unit.'; criterio: '.$this->criterion($configuration).'.';
    }

    private function prioritizeDependentFindings(array $findings): array
    {
        $waterFailure = collect($findings)->first(
            fn (array $finding) => str_ends_with((string) ($finding['rule_code'] ?? ''), '.CHEM.WATER.PERCENT')
        );

        if (! $waterFailure) return $findings;

        usort($findings, function (array $left, array $right): int {
            $priority = static function (array $finding): int {
                $code = (string) ($finding['rule_code'] ?? '');
                if (str_ends_with($code, '.CHEM.WATER.PERCENT')) return 0;
                if (str_ends_with($code, '.CHEM.CALCIUM_PHOSPHATE.PER1000')) return 2;
                return 1;
            };

            return $priority($left) <=> $priority($right);
        });

        return $findings;
    }

    private function suggestedTotalVolumeFromWater(iterable $rules, array $facts): ?float
    {
        if (!($facts['calculated_water'] ?? false)) return null;

        foreach ($rules as $rule) {
            $configuration = $rule->configuration;
            if (($configuration['formula_template'] ?? null) !== 'percentage_of_volume'
                || ($configuration['source_variable'] ?? null) !== 'groups.water.volume_ml') {
                continue;
            }
            if ($this->applicable($configuration['applicability'] ?? [], $facts) !== true) continue;

            $result = $this->mathematical->evaluate($configuration, $facts);
            if (($result['passed'] ?? false) || ($result['incomplete'] ?? false)) return null;

            $targetPercent = $this->failedPercentageTarget($configuration, (float) $result['value']);
            $componentVolume = (float) data_get($facts, 'mixture.component_volume_ml', 0);
            if (!$targetPercent || $targetPercent <= 0 || $targetPercent >= 100 || $componentVolume <= 0) return null;

            $increase = (float) $result['value'] < $targetPercent;
            $strict = in_array($configuration['operator'] ?? null, ['<', '>'], true);
            $waterTarget = $this->safeCaptureTarget(
                $componentVolume * $targetPercent / (100 - $targetPercent),
                $increase,
                $strict
            );

            return round($componentVolume + $waterTarget, 4);
        }

        return null;
    }

    private function suggestedTotalVolume(iterable $rules, array $facts): ?float
    {
        $targetVolume = $this->suggestedTotalVolumeFromWater($rules, $facts);
        $referenceVolume = $targetVolume ?: (float) data_get($facts, 'mixture.total_volume_ml', 0);
        $weight = (float) data_get($facts, 'patient.weight_kg', 0);
        if ($referenceVolume <= 0 || $weight <= 0) return $targetVolume;

        $rulesByGroup = collect($rules)->groupBy(function (ValidationRule $rule) {
            preg_match('/^groups\.([^.]+)/', (string) ($rule->configuration['source_variable'] ?? ''), $match);
            return $match[1] ?? '';
        });
        foreach ($rulesByGroup as $group => $groupRules) {
            if ($group === '') continue;
            $directRule = $groupRules->first(fn (ValidationRule $rule) => ($rule->configuration['formula_template'] ?? null) === 'direct_value'
                && $this->applicable($rule->configuration['applicability'] ?? [], $facts) === true);
            $percentageRule = $groupRules->first(fn (ValidationRule $rule) => ($rule->configuration['formula_template'] ?? null) === 'dose_times_weight_percentage_of_volume'
                && $this->applicable($rule->configuration['applicability'] ?? [], $facts) === true);
            if (!$directRule || !$percentageRule) continue;

            $directConfig = $directRule->configuration;
            $directOperator = $directConfig['operator'] ?? null;
            $directUpper = match ($directOperator) {
                '<', '<=' => isset($directConfig['threshold_value']) ? (float) $directConfig['threshold_value'] : null,
                'between' => isset($directConfig['threshold_max']) ? (float) $directConfig['threshold_max'] : null,
                default => null,
            };
            $percentageConfig = $percentageRule->configuration;
            $operator = $percentageConfig['operator'] ?? null;
            $percentTarget = match ($operator) {
                '>', '>=' => isset($percentageConfig['threshold_value']) ? (float) $percentageConfig['threshold_value'] : null,
                'between' => isset($percentageConfig['threshold_min']) ? (float) $percentageConfig['threshold_min'] : null,
                default => null,
            };
            if ($directUpper === null || !$percentTarget) continue;

            $doseTarget = $this->safeCaptureTarget($directUpper, false, $directOperator === '<');
            $requiredDose = $referenceVolume * $percentTarget / (100 * $weight);
            if ($requiredDose <= $doseTarget) continue;
            $maximumVolume = $this->safeCaptureTarget(
                round($doseTarget * $weight * 100 / $percentTarget, 8),
                false,
                $operator === '>'
            );
            $targetVolume = $targetVolume === null ? $maximumVolume : min($targetVolume, $maximumVolume);
            $referenceVolume = $targetVolume;
        }

        return $targetVolume === null ? null : round($targetVolume, 4);
    }

    private function coordinateConflictingSuggestions(array $findings, array $facts): array
    {
        $groups = array_values(array_unique(array_filter(array_map(
            fn (array $finding) => $finding['_group'] ?? null,
            $findings
        ))));
        foreach ($groups as $group) {
            if ($group === '') continue;

            $percentageIndex = null;
            $directIndex = null;
            foreach ($findings as $index => $finding) {
                if (($finding['_group'] ?? '') !== $group) continue;
                $formula = $finding['_configuration']['formula_template'] ?? null;
                if ($formula === 'dose_times_weight_percentage_of_volume') $percentageIndex = $index;
                if ($formula === 'direct_value') $directIndex = $index;
            }
            if ($percentageIndex === null || $directIndex === null) continue;

            $percentage = $findings[$percentageIndex];
            $direct = $findings[$directIndex];
            $percentageConfig = $percentage['_configuration'];
            $directConfig = $direct['_configuration'];
            $currentDose = (float) data_get($direct, '_result.value', 0);
            $directTarget = $this->failedNumericTarget($directConfig, $currentDose);
            $percentTarget = $this->failedPercentageTarget($percentageConfig, (float) data_get($percentage, '_result.value', 0));
            $volume = (float) data_get($facts, 'mixture.total_volume_ml', 0);
            $weight = (float) data_get($facts, 'patient.weight_kg', 0);
            if ($currentDose <= 0 || $directTarget === null || $percentTarget === null || $volume <= 0 || $weight <= 0) continue;

            $percentageTargetDose = $volume * $percentTarget / (100 * $weight);
            $directDirection = $directTarget <=> $currentDose;
            $percentageDirection = $percentageTargetDose <=> $currentDose;
            if ($directDirection === 0 || $percentageDirection === 0 || $directDirection === $percentageDirection) continue;

            $strictDirect = in_array($directConfig['operator'] ?? null, ['<', '>'], true);
            $doseTarget = $this->safeCaptureTarget($directTarget, $directDirection > 0, $strictDirect);
            $totalAmount = $doseTarget * $weight;
            $percentOperator = $percentageConfig['operator'] ?? null;
            $volumeCondition = '';
            $volumeTarget = null;
            if (in_array($percentOperator, ['>', '>='], true)) {
                $maximumVolume = $this->safeCaptureTarget(
                    round($totalAmount * 100 / (float) $percentageConfig['threshold_value'], 8),
                    false,
                    $percentOperator === '>'
                );
                $volumeTarget = $maximumVolume;
                $volumeCondition = 'máximo '.round($maximumVolume, 4).' mL';
            } elseif (in_array($percentOperator, ['<', '<='], true)) {
                $minimumVolume = $this->safeCaptureTarget(
                    $totalAmount * 100 / (float) $percentageConfig['threshold_value'],
                    true,
                    $percentOperator === '<'
                );
                $volumeTarget = $minimumVolume;
                $volumeCondition = 'al menos '.round($minimumVolume, 4).' mL';
            } elseif ($percentOperator === 'between') {
                $minimumVolume = $totalAmount * 100 / (float) $percentageConfig['threshold_max'];
                $maximumVolume = $totalAmount * 100 / (float) $percentageConfig['threshold_min'];
                $volumeCondition = 'entre '.round($minimumVolume, 4).' y '.round($maximumVolume, 4).' mL';
            }
            if ($volumeCondition === '') continue;

            $component = implode(' / ', $facts['group_component_names'][$group] ?? []) ?: $this->groupLabel((string) $group);
            $unit = trim((string) ($directConfig['unit'] ?? '')) ?: 'g/kg/día';
            $findings[$percentageIndex]['suggestion'] = 'Ajuste conjunto para '.$component.': captura '
                .round($doseTarget, 4).' '.$unit.' y un volumen total '.$volumeCondition.'.';
            if ($volumeTarget !== null) {
                $findings[$percentageIndex]['suggested_changes'] = [
                    ['field' => $findings[$percentageIndex]['field'], 'value' => round($doseTarget, 4)],
                    ['field' => 'volumen_total', 'value' => round($volumeTarget, 4)],
                ];
            }
            $findings[$directIndex]['suggestion'] = '';
            $findings[$directIndex]['suggested_changes'] = [];
        }

        return $findings;
    }

    private function failedNumericTarget(array $configuration, float $value): ?float
    {
        return match ($configuration['operator'] ?? null) {
            '<', '<=', '>', '>=' => isset($configuration['threshold_value'])
                ? (float) $configuration['threshold_value']
                : null,
            'between' => $value < (float) ($configuration['threshold_min'] ?? 0)
                ? (float) $configuration['threshold_min']
                : ($value > (float) ($configuration['threshold_max'] ?? 0)
                    ? (float) $configuration['threshold_max']
                    : null),
            default => null,
        };
    }

    private function suggestedChangesFromText(string $suggestion, string $field, array $facts, ?string $group): array
    {
        if ($suggestion === '') return [];

        $changes = [];
        if (preg_match('/(?:volumen total sugerido (?:es de|de)|ajusta el volumen total a)\s*([0-9]+(?:\.[0-9]+)?)\s*mL/i', $suggestion, $match)) {
            $changes[] = ['field' => 'volumen_total', 'value' => (float) $match[1]];
        }

        $patterns = [
            '/cambia el valor capturado.+?\ba aproximadamente\s*([0-9]+(?:\.[0-9]+)?)/iu',
            '/debe ajustarse de\s*[0-9]+(?:\.[0-9]+)?\s*\S+\s+a\s*([0-9]+(?:\.[0-9]+)?)/iu',
            '/debe ajustarse a\s*([0-9]+(?:\.[0-9]+)?)/iu',
            '/debe quedar en\s*(?:al menos|máximo)\s*([0-9]+(?:\.[0-9]+)?)/iu',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $suggestion, $match)) {
                $changes[] = ['field' => $field, 'value' => (float) $match[1]];
                break;
            }
        }

        if ($group && preg_match('/(?:total combinado debe ajustarse a|Ajusta la suma a un máximo de)\s*([0-9]+(?:\.[0-9]+)?)/iu', $suggestion, $match)) {
            $target = (float) $match[1];
            $fields = $facts['group_input_fields'][$group] ?? [];
            $values = $facts['group_captured_values'][$group] ?? [];
            $current = array_sum($values);
            if ($target >= 0 && $current > 0 && count($fields) === count($values)) {
                $changes = array_values(array_filter($changes, fn (array $change) => $change['field'] === 'volumen_total'));
                $scale = $target / $current;
                foreach ($fields as $index => $inputField) {
                    $changes[] = ['field' => $inputField, 'value' => round((float) $values[$index] * $scale, 4)];
                }
            }
        }

        return collect($changes)->unique('field')->values()->all();
    }

    private function audit(ValidationRule $rule, int $mixtureIndex, string $status, array $result = []): array
    {
        return array_filter([
            'rule_code' => $rule->code,
            'rule_version' => $rule->version,
            'engine' => $rule->engine,
            'severity' => $rule->severity,
            'mixture_index' => $mixtureIndex,
            'status' => $status,
            'value' => $result['value'] ?? null,
            'unit' => $result['unit'] ?? null,
            'message' => $result['message'] ?? null,
        ], fn ($value) => $value !== null);
    }

    private function normalize(string $value): string
    {
        return Str::upper(Str::ascii(trim($value)));
    }
}
