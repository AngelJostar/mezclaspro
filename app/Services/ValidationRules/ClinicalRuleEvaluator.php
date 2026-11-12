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
            array_push($findings, ...$this->prioritizeDependentFindings($mixtureFindings));
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
                    $facts['group_component_names'][$electrolyte][] = trim((string) ($component['medicine'] ?? ''));
                    $facts['group_captured_values'][$electrolyte][] = (float) ($component['quantity'] ?? 0);
                    $facts['group_input_units'][$electrolyte][] = trim((string) ($component['unit'] ?? ''));
                    $facts['group_weighted_inputs'][$electrolyte][] = (bool) ($component['weight_factor_applied'] ?? false);
                }
            }
        }

        $facts['groups']['calcium_phosphate']['total_meq'] =
            $facts['groups']['calcium']['total_meq'] + $facts['groups']['phosphate']['total_meq'];
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

        return [
            'field' => $field,
            'severity' => $rule->severity,
            'category' => 'deterministic_rule',
            'message' => $rule->engine === 'composition'
                ? $compositionExplanation['message']
                : $rule->name.': '.($result['message'] ?? $rule->description ?? 'La regla no se cumple.'),
            'calculation' => trim($calculation),
            'suggestion' => $compositionExplanation['suggestion'] ?? $mathematicalSuggestion,
            'source_ids' => ['SYSTEM'],
            'rule_code' => $rule->code,
            'composition_code' => $matchedCompositionCode,
            'rule_version' => $rule->version,
            'mixture_index' => $mixtureIndex,
        ];
    }

    private function mathematicalSuggestion(ValidationRule $rule, array $facts, array $result, ?string $group): string
    {
        $configuration = $rule->configuration;
        if (!is_numeric($result['value'] ?? null)) return '';

        $formula = $configuration['formula_template'] ?? null;
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

                return 'Este límite aplica a la suma de los componentes indicados: '.round($value, 4).' '.$unit
                    .'. El total combinado debe ajustarse a '.round($target, 4).' '.$unit.'. '
                    .'Una distribución proporcional es '.implode('; ', $distributed)
                    .'. También puedes redistribuirlos de otra forma, siempre que la suma no exceda el límite.';
            }

            return 'La dosis de '.$component.' actualmente es '.round($value, 4).' '.$unit
                .'. Para cumplir esta regla debe ajustarse a '.round($target, 4).' '.$unit.' ('
                .$action.' '.round(abs($difference), 4).' '.$unit
                .'). Captura el valor ajustado en '.$component.' y confirma el cambio con el profesional responsable.';
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

            return 'Para cumplir el límite de '.$targetPercent.'%, '.$component.' debe quedar en '
                .($increase ? 'al menos ' : 'máximo ').round($target, 4).' mL ('
                .$action.' '.round(abs($difference), 4).' mL respecto al valor actual).';
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

            return 'Para cumplir el límite de '.$targetPercent.'%, cambia el valor capturado de '.$component
                .' de '.round($captured, 4).' '.$inputUnit.' a aproximadamente '.round($targetCaptured, 4).' '.$inputUnit.' ('
                .$capturedAction.' '.round(abs($capturedDifference), 4).' '.$inputUnit
                .'). El cálculo interno equivale a '.round($target, 4).' g totales.';
        }

        return 'Para cumplir el límite de '.$targetPercent.'%, el valor de '.$component
            .' tendría que cambiar de '.round($actual, 4).' g a aproximadamente '.round($target, 4).' g ('
            .$action.' '.round(abs($difference), 4).' g). Captura el nuevo total en el campo de '.$component
            .' y confirma el ajuste con el profesional responsable.';
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

        foreach ($findings as &$finding) {
            if (str_ends_with((string) ($finding['rule_code'] ?? ''), '.CHEM.CALCIUM_PHOSPHATE.PER1000')) {
                $finding['suggestion'] = 'Ajusta primero el volumen total de acuerdo con el error de agua y vuelve a validar. '
                    .'La capacidad máxima de calcio y fosfato se recalculará con el nuevo volumen total.';
            }
        }
        unset($finding);

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
