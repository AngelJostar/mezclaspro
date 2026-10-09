<?php

namespace Database\Seeders;

use App\Models\ValidationRule;
use Illuminate\Database\Seeder;

class ClinicalValidationRulesR5Seeder extends Seeder
{
    public function run(): void
    {
        $this->upsertRule('NP.R5.ADULT.COMP.CATALOG', 'Combinaciones permitidas R5 para adulto', 'composition', 'adult', 'blocking', [
            'mode' => 'allowed_catalog',
            'allow_any_single_component' => true,
            'manual_declared_count' => 39,
            'allowed_compositions' => $this->adultCompositions(),
            'review_notes' => ['La combinación 31 repite la combinación 21 y parece omitir solución salina. Confirmar con el área química.'],
        ], 'Catálogo textual de 39 combinaciones del Manual Maestro R5. Incluye la excepción indicada para solicitudes de un solo componente.');

        $this->upsertRule('NP.R5.PEDIATRIC.COMP.CATALOG', 'Combinaciones permitidas R5 para pediatría', 'composition', 'pediatric', 'blocking', [
            'mode' => 'allowed_catalog',
            'allow_any_single_component' => true,
            'manual_declared_count' => 36,
            'allowed_compositions' => $this->pediatricCompositions(),
            'review_notes' => ['La combinación 28 repite la combinación 18 y parece omitir solución salina. Confirmar con el área química.'],
        ], 'Catálogo textual de 36 combinaciones del Manual Maestro R5. Incluye la excepción indicada para solicitudes de un solo componente.');

        foreach ($this->mathematicalRules() as $rule) {
            $this->upsertRule(...$rule);
        }
    }

    private function upsertRule(string $code, string $name, string $engine, string $population, string $severity, array $configuration, string $description): void
    {
        // Se conserva el nombre de esta clase para compatibilidad con instalaciones previas.
        // Las reglas vigentes pertenecen a la revisión R6.
        $code = str_replace('NP.R5.', 'NP.R6.', $code);
        $name = str_replace([' R5 ', ' R5'], [' R6 ', ' R6'], $name);
        $description = str_replace(['Manual Maestro R5', 'Manual Maestro de Validación V4'], 'Manual Maestro R6', $description);

        ValidationRule::query()->updateOrCreate(['code' => $code], [
            'name' => $name,
            'engine' => $engine,
            'population' => $population,
            'severity' => $severity,
            'status' => 'review',
            'description' => $description,
            'configuration' => $configuration,
            'version' => 1,
            'is_enforced' => false,
        ]);
    }

    private function adultCompositions(): array
    {
        $items = [
            $this->composition(1, 'Aminoácidos adultos 10% + 8.5%', ['amino_acids'], ['aa_adult_10', 'aa_adult_8_5'], ['aa_adult_8']),
            $this->composition(2, 'Aminoácidos adultos 10% + 8%', ['amino_acids'], ['aa_adult_10', 'aa_adult_8'], ['aa_adult_8_5']),
            $this->composition(3, 'Aminoácidos adultos 8.5% + 8%', ['amino_acids'], ['aa_adult_8_5', 'aa_adult_8'], ['aa_adult_10']),
        ];
        $groups = [
            ['Aminoácidos + agua', ['amino_acids', 'water'], [], []],
            ['Aminoácidos + electrolitos', ['amino_acids', 'electrolytes'], [], []],
            ['Aminoácidos + electrolitos + elementos traza', ['amino_acids', 'electrolytes', 'trace_elements'], [], []],
            ['Aminoácidos + electrolitos + elementos traza + vitaminas', ['amino_acids', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ['Aminoácidos + electrolitos + elementos traza + vitaminas + medicamentos excepto insulina', ['amino_acids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], ['insulin']],
            ['Aminoácidos + dextrosa', ['amino_acids', 'dextrose'], [], []],
            ['Aminoácidos + dextrosa + lípidos', ['amino_acids', 'dextrose', 'lipids'], [], []],
            ['Aminoácidos + dextrosa + electrolitos', ['amino_acids', 'dextrose', 'electrolytes'], [], []],
            ['Aminoácidos + dextrosa + lípidos + electrolitos', ['amino_acids', 'dextrose', 'lipids', 'electrolytes'], [], []],
            ['Aminoácidos + dextrosa + electrolitos + elementos traza', ['amino_acids', 'dextrose', 'electrolytes', 'trace_elements'], [], []],
            ['Aminoácidos + dextrosa + lípidos + electrolitos + elementos traza', ['amino_acids', 'dextrose', 'lipids', 'electrolytes', 'trace_elements'], [], []],
            ['Aminoácidos + dextrosa + electrolitos + elementos traza + vitaminas', ['amino_acids', 'dextrose', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ['Aminoácidos + dextrosa + lípidos + electrolitos + elementos traza + vitaminas', ['amino_acids', 'dextrose', 'lipids', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ['Aminoácidos + dextrosa + electrolitos + elementos traza + vitaminas + medicamentos', ['amino_acids', 'dextrose', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], []],
            ['Aminoácidos + dextrosa + lípidos + electrolitos + elementos traza + vitaminas + medicamentos', ['amino_acids', 'dextrose', 'lipids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], []],
            ['Únicamente lípidos', ['lipids'], [], []],
        ];
        foreach ($groups as $row) {
            $items[] = $this->composition(count($items) + 1, ...$row);
        }
        foreach ($this->waterSeries() as $row) {
            $items[] = $this->composition(count($items) + 1, ...$row);
        }
        foreach ($this->salineSeries() as $row) {
            $items[] = $this->composition(count($items) + 1, ...$row);
        }

        return $items;
    }

    private function pediatricCompositions(): array
    {
        $items = [
            $this->composition(1, 'Únicamente aminoácidos pediátricos 10%', ['amino_acids'], ['aa_pediatric_10']),
            $this->composition(2, 'Aminoácidos pediátricos 10% + electrolitos', ['amino_acids', 'electrolytes'], ['aa_pediatric_10']),
            $this->composition(3, 'Aminoácidos pediátricos 10% + electrolitos + elementos traza', ['amino_acids', 'electrolytes', 'trace_elements'], ['aa_pediatric_10']),
            $this->composition(4, 'Aminoácidos pediátricos 10% + electrolitos + elementos traza + vitaminas', ['amino_acids', 'electrolytes', 'trace_elements', 'vitamins'], ['aa_pediatric_10']),
            $this->composition(5, 'Aminoácidos pediátricos 10% + electrolitos + elementos traza + vitaminas + medicamentos excepto insulina', ['amino_acids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], ['aa_pediatric_10'], ['insulin']),
        ];
        $base = [
            ['Aminoácidos pediátricos 10% + dextrosa', ['amino_acids', 'dextrose']],
            ['Aminoácidos pediátricos 10% + dextrosa + lípidos', ['amino_acids', 'dextrose', 'lipids']],
            ['Aminoácidos pediátricos 10% + dextrosa + electrolitos', ['amino_acids', 'dextrose', 'electrolytes']],
            ['Aminoácidos pediátricos 10% + dextrosa + lípidos + electrolitos', ['amino_acids', 'dextrose', 'lipids', 'electrolytes']],
            ['Aminoácidos pediátricos 10% + dextrosa + electrolitos + elementos traza', ['amino_acids', 'dextrose', 'electrolytes', 'trace_elements']],
            ['Aminoácidos pediátricos 10% + dextrosa + lípidos + electrolitos + elementos traza', ['amino_acids', 'dextrose', 'lipids', 'electrolytes', 'trace_elements']],
            ['Aminoácidos pediátricos 10% + dextrosa + electrolitos + elementos traza + vitaminas', ['amino_acids', 'dextrose', 'electrolytes', 'trace_elements', 'vitamins']],
            ['Aminoácidos pediátricos 10% + dextrosa + lípidos + electrolitos + elementos traza + vitaminas', ['amino_acids', 'dextrose', 'lipids', 'electrolytes', 'trace_elements', 'vitamins']],
            ['Aminoácidos pediátricos 10% + dextrosa + electrolitos + elementos traza + vitaminas + medicamentos', ['amino_acids', 'dextrose', 'electrolytes', 'trace_elements', 'vitamins', 'medications']],
            ['Aminoácidos pediátricos 10% + dextrosa + lípidos + electrolitos + elementos traza + vitaminas + medicamentos', ['amino_acids', 'dextrose', 'lipids', 'electrolytes', 'trace_elements', 'vitamins', 'medications']],
            ['Únicamente lípidos', ['lipids']],
        ];
        foreach ($base as [$label, $groups]) {
            $items[] = $this->composition(count($items) + 1, $label, $groups, str_contains($label, 'Aminoácidos') ? ['aa_pediatric_10'] : []);
        }
        foreach ($this->waterSeries('Aminoácidos pediátricos 10%') as $row) {
            $items[] = $this->composition(count($items) + 1, $row[0], $row[1], ['aa_pediatric_10']);
        }
        foreach ($this->salineSeries('Aminoácidos pediátricos 10%') as $row) {
            $items[] = $this->composition(count($items) + 1, $row[0], $row[1], ['aa_pediatric_10']);
        }

        return $items;
    }

    private function waterSeries(string $amino = 'Aminoácidos'): array
    {
        return [
            ["{$amino} + dextrosa + agua", ['amino_acids', 'dextrose', 'water'], [], []],
            ["{$amino} + dextrosa + agua + lípidos", ['amino_acids', 'dextrose', 'water', 'lipids'], [], []],
            ["{$amino} + dextrosa + agua + electrolitos", ['amino_acids', 'dextrose', 'water', 'electrolytes'], [], []],
            ["{$amino} + dextrosa + agua + electrolitos + lípidos", ['amino_acids', 'dextrose', 'water', 'electrolytes', 'lipids'], [], []],
            ["{$amino} + dextrosa + agua + electrolitos + elementos traza", ['amino_acids', 'dextrose', 'water', 'electrolytes', 'trace_elements'], [], []],
            ["{$amino} + dextrosa + agua + lípidos + electrolitos + elementos traza", ['amino_acids', 'dextrose', 'water', 'lipids', 'electrolytes', 'trace_elements'], [], []],
            ["{$amino} + dextrosa + agua + electrolitos + elementos traza + vitaminas", ['amino_acids', 'dextrose', 'water', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ["{$amino} + dextrosa + agua + lípidos + electrolitos + elementos traza + vitaminas", ['amino_acids', 'dextrose', 'water', 'lipids', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ["{$amino} + dextrosa + agua + electrolitos + elementos traza + vitaminas + medicamentos", ['amino_acids', 'dextrose', 'water', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], []],
            ["{$amino} + dextrosa + agua + lípidos + electrolitos + elementos traza + vitaminas + medicamentos", ['amino_acids', 'dextrose', 'water', 'lipids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], []],
        ];
    }

    private function salineSeries(string $amino = 'Aminoácidos'): array
    {
        return [
            ["{$amino} + dextrosa + agua + solución salina", ['amino_acids', 'dextrose', 'water', 'saline'], [], []],
            ["{$amino} + dextrosa + agua + lípidos (duplicada en la sección salina)", ['amino_acids', 'dextrose', 'water', 'lipids'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + electrolitos", ['amino_acids', 'dextrose', 'water', 'saline', 'electrolytes'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + electrolitos + lípidos", ['amino_acids', 'dextrose', 'water', 'saline', 'electrolytes', 'lipids'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + electrolitos + elementos traza", ['amino_acids', 'dextrose', 'water', 'saline', 'electrolytes', 'trace_elements'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + lípidos + electrolitos + elementos traza", ['amino_acids', 'dextrose', 'water', 'saline', 'lipids', 'electrolytes', 'trace_elements'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + electrolitos + elementos traza + vitaminas", ['amino_acids', 'dextrose', 'water', 'saline', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + lípidos + electrolitos + elementos traza + vitaminas", ['amino_acids', 'dextrose', 'water', 'saline', 'lipids', 'electrolytes', 'trace_elements', 'vitamins'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + electrolitos + elementos traza + vitaminas + medicamentos", ['amino_acids', 'dextrose', 'water', 'saline', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], []],
            ["{$amino} + dextrosa + agua + solución salina + lípidos + electrolitos + elementos traza + vitaminas + medicamentos", ['amino_acids', 'dextrose', 'water', 'saline', 'lipids', 'electrolytes', 'trace_elements', 'vitamins', 'medications'], [], []],
        ];
    }

    private function composition(int $number, string $label, array $groups, array $terms = [], array $forbiddenTerms = []): array
    {
        return ['code' => sprintf('C%02d', $number), 'label' => $label, 'groups' => $groups,
            'component_terms' => $terms, 'forbidden_component_terms' => $forbiddenTerms];
    }

    private function mathematicalRules(): array
    {
        $rules = [];
        $add = function (string $code, string $name, string $population, string $severity, string $source, string $formula, string $operator, array $limits, string $unit, string $message, array $applicability = []) use (&$rules): void {
            $rules[] = [$code, $name, 'mathematical', $population, $severity, array_merge([
                'source_variable' => $source, 'formula_template' => $formula, 'operator' => $operator,
                'unit' => $unit, 'failure_message' => $message,
            ], $limits, $applicability ? ['applicability' => $applicability] : []), 'Regla parametrizada a partir del Manual Maestro R5.'];
        };

        $add('NP.R5.CHEM.LIPIDS.PERCENT', 'Concentración química de lípidos', 'both', 'blocking', 'groups.lipids.dose_g_kg_day', 'dose_times_weight_percentage_of_volume', 'between', ['threshold_min' => 1.5, 'threshold_max' => 10], '%', 'La concentración de lípidos debe estar entre 1.5% y 10%.', ['required_group' => 'lipids']);
        $add('NP.R5.CHEM.AA.PERCENT', 'Concentración final de aminoácidos', 'both', 'blocking', 'groups.amino_acids.dose_g_kg_day', 'dose_times_weight_percentage_of_volume', '>', ['threshold_value' => 2.5], '%', 'La concentración final de aminoácidos debe ser mayor a 2.5%.', ['required_group' => 'amino_acids']);
        $add('NP.R5.CHEM.DEXTROSE.PERCENT', 'Concentración final de dextrosa', 'both', 'blocking', 'groups.dextrose.dose_g_kg_day', 'dose_times_weight_percentage_of_volume', 'between', ['threshold_min' => 5, 'threshold_max' => 35], '%', 'La concentración final de dextrosa debe estar entre 5% y 35%.', ['required_group' => 'dextrose']);
        foreach ([
            ['SODIUM', 'Sodio', 'groups.sodium.total_meq', 180], ['POTASSIUM', 'Potasio', 'groups.potassium.total_meq', 100],
            ['MAGNESIUM', 'Magnesio', 'groups.magnesium.total_meq', 15], ['CALCIUM_PHOSPHATE', 'Calcio y fosfato combinados', 'groups.calcium_phosphate.total_meq', 45],
            ['CHLORIDE', 'Cloro', 'groups.chloride.total_meq', 180], ['ACETATE', 'Acetato', 'groups.acetate.total_meq', 85],
        ] as [$key, $label, $source, $limit]) {
            $add("NP.R5.CHEM.{$key}.PER1000", "Concentración final de {$label}", 'both', 'blocking', $source, 'amount_per_1000_ml', $key === 'CALCIUM_PHOSPHATE' ? '<=' : '<', ['threshold_value' => $limit], 'mEq/1000 mL', "La concentración de {$label} excede el límite químico permitido.");
        }
        $add('NP.R5.CHEM.WATER.PERCENT', 'Agua máxima cuando hay lípidos', 'both', 'blocking', 'groups.water.volume_ml', 'percentage_of_volume', '<=', ['threshold_value' => 60], '%', 'El agua no debe exceder el 60% del volumen total cuando la mezcla contiene lípidos.', ['required_group' => 'lipids']);

        foreach ([['STABLE', 'metabólicamente estable', .8], ['HOSPITALIZED', 'hospitalizado', 1.2], ['STRESSED', 'estresado clínicamente', 2.0]] as [$key, $stage, $limit]) {
            $add("NP.R5.ADULT.CLIN.AA.{$key}", "Aminoácidos adulto {$stage}", 'adult', 'authorization', 'groups.amino_acids.dose_g_kg_day', 'direct_value', '<=', ['threshold_value' => $limit], 'g/kg/día', 'Exceso de aminoácidos: riesgo clínico de acidosis metabólica o colestasis.', ['clinical_stage' => $stage]);
        }
        $add('NP.R5.ADULT.CLIN.DEXTROSE.11_20', 'Dextrosa de 11 a 20 años', 'adult', 'authorization', 'groups.dextrose.dose_g_kg_day', 'direct_value', '<=', ['threshold_value' => 10], 'g/kg/día', 'Exceso de dextrosa: riesgo de hiperglucemia, esteatosis hepática o sobrecarga respiratoria.', ['age_group' => '11-20 años']);
        $add('NP.R5.ADULT.CLIN.DEXTROSE.ADULT', 'Dextrosa en adulto', 'adult', 'authorization', 'groups.dextrose.dose_g_kg_day', 'direct_value', '<=', ['threshold_value' => 7.2], 'g/kg/día', 'Exceso de dextrosa: riesgo de hiperglucemia, esteatosis hepática o sobrecarga respiratoria.', ['age_group' => 'adulto']);
        $add('NP.R5.ADULT.CLIN.LIPIDS', 'Lípidos en adulto', 'adult', 'authorization', 'groups.lipids.dose_g_kg_day', 'direct_value', '<=', ['threshold_value' => 2.5], 'g/kg/día', 'Exceso de lípidos: riesgo de hipertrigliceridemia o síndrome de sobrecarga de lípidos.');
        foreach ([['SODIUM','Sodio',150,'groups.sodium.total_meq','mEq/día'],['POTASSIUM','Potasio',150,'groups.potassium.total_meq','mEq/día'],['MAGNESIUM','Magnesio',30,'groups.magnesium.total_meq','mEq/día'],['CALCIUM','Calcio',20,'groups.calcium.total_meq','mEq/día'],['PHOSPHATE','Fosfato',60,'groups.phosphate.total_meq','mEq/día'],['CHLORIDE','Cloro',150,'groups.chloride.total_meq','mEq/día'],['ACETATE','Acetato',4,'groups.acetate.dose_meq_kg_day','mEq/kg/día']] as [$key,$label,$limit,$source,$unit]) {
            $add("NP.R5.ADULT.CLIN.{$key}", "Límite clínico de {$label} en adulto", 'adult', 'authorization', $source, 'direct_value', '<=', ['threshold_value'=>$limit], $unit, "Exceso de {$label}: requiere valoración médica.");
        }

        foreach ([['NEONATE','neonato',4],['INFANT','lactante de 1 a 12 meses',3],['CHILD','infante',2],['ADOLESCENT','adolescente',2]] as [$key,$stage,$limit]) {
            $add("NP.R5.PED.CLIN.AA.{$key}", "Aminoácidos pediátricos en {$stage}", 'pediatric', 'authorization', 'groups.amino_acids.dose_g_kg_day', 'direct_value', '<=', ['threshold_value'=>$limit], 'g/kg/día', 'Exceso de aminoácidos: riesgo clínico de acidosis metabólica o colestasis.', ['patient_group'=>$stage]);
        }
        $add('NP.R5.PED.CLIN.DEXTROSE.UNDER11', 'Dextrosa en menores de 11 años', 'pediatric', 'authorization', 'groups.dextrose.dose_g_kg_day', 'direct_value', '<=', ['threshold_value'=>20.1], 'g/kg/día', 'Exceso de dextrosa: riesgo de hiperglucemia, esteatosis hepática o sobrecarga respiratoria.', ['age_group'=>'menor de 11 años']);
        $add('NP.R5.PED.CLIN.DEXTROSE.11_20', 'Dextrosa de 11 a 20 años', 'pediatric', 'authorization', 'groups.dextrose.dose_g_kg_day', 'direct_value', '<=', ['threshold_value'=>10], 'g/kg/día', 'Exceso de dextrosa: riesgo de hiperglucemia, esteatosis hepática o sobrecarga respiratoria.', ['age_group'=>'11-20 años']);
        $add('NP.R5.PED.CLIN.LIPIDS.NEONATE_INFANT', 'Lípidos en neonatos y lactantes', 'pediatric', 'authorization', 'groups.lipids.dose_g_kg_day', 'direct_value', '<=', ['threshold_value'=>3], 'g/kg/día', 'Exceso de lípidos: riesgo de hipertrigliceridemia o síndrome de sobrecarga de lípidos.', ['patient_group'=>'neonato o lactante']);
        $add('NP.R5.PED.CLIN.LIPIDS.CHILD', 'Lípidos en pacientes infantiles', 'pediatric', 'authorization', 'groups.lipids.dose_g_kg_day', 'direct_value', '<=', ['threshold_value'=>2.5], 'g/kg/día', 'Exceso de lípidos: riesgo de hipertrigliceridemia o síndrome de sobrecarga de lípidos.', ['patient_group'=>'infantil']);
        foreach ([['SODIUM','Sodio',5],['POTASSIUM','Potasio',4],['MAGNESIUM','Magnesio',.5],['CALCIUM','Calcio',4],['PHOSPHATE','Fosfato',2.8],['CHLORIDE','Cloro',3],['ACETATE','Acetato',4]] as [$key,$label,$limit]) {
            $add("NP.R5.PED.CLIN.{$key}.NEONATE_INFANT", "{$label} en neonato, lactante o infantil", 'pediatric', 'authorization', "groups.".strtolower($key).".dose_meq_kg_day", 'direct_value', '<=', ['threshold_value'=>$limit], 'mEq/kg/día', "Exceso de {$label}: requiere valoración médica.", ['patient_group'=>'neonato, lactante o infantil']);
        }
        foreach ([['SODIUM','Sodio',2,'dose_meq_kg_day','mEq/kg/día'],['POTASSIUM','Potasio',2,'dose_meq_kg_day','mEq/kg/día'],['MAGNESIUM','Magnesio',30,'total_meq','mEq/día'],['CALCIUM','Calcio',20,'total_meq','mEq/día'],['PHOSPHATE','Fosfato',56,'total_meq','mEq/día'],['CHLORIDE','Cloro',3,'dose_meq_kg_day','mEq/kg/día'],['ACETATE','Acetato',4,'dose_meq_kg_day','mEq/kg/día']] as [$key,$label,$limit,$field,$unit]) {
            $add("NP.R5.PED.CLIN.{$key}.ADOLESCENT", "{$label} en infantil menor de 50 kg o adolescente", 'pediatric', 'authorization', "groups.".strtolower($key).".{$field}", 'direct_value', '<=', ['threshold_value'=>$limit], $unit, "Exceso de {$label}: requiere valoración médica.", ['patient_group'=>'infantil menor de 50 kg o adolescente']);
        }

        return $rules;
    }
}
