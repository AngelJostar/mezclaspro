<?php

namespace App\Services\Clinical;

use App\Models\Nutricionales\Input;
use App\Models\Oncologicos\MedicinesCatalog;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\AdministrationRoute;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClinicalPayload
{
    public const CONTEXT_FIELDS = [
        'allergies' => 'Alergias y reacciones conocidas',
        'clinical_indication' => 'Indicacion clinica y protocolo terapeutico',
        'concomitant_medication' => 'Medicacion concomitante y otras infusiones',
        'organ_function_labs' => 'Funcion renal/hepatica y laboratorios relevantes (fecha y unidades)',
        'patient_factors' => 'Otros factores relevantes del paciente (si se conocen)',
        'preparation_storage' => 'Envase, diluyente, conservacion, tiempos hasta administracion y condiciones asepticas',
    ];

    public function normalize(string $kind, array $data): array
    {
        $this->validatePatient($kind, $data);
        // Only this allowlist leaves the server. General notes and direct identifiers stay local.
        $patient = ['weight_kg' => (float) $data['peso'],
            'age_days' => CarbonImmutable::parse($data['fecha_nacimiento'])->diffInDays(CarbonImmutable::today()),
            'sex' => in_array($data['sexo'] ?? '', ['M', 'F', 'Masculino', 'Femenino'], true) ? $data['sexo'] : null];
        if ($kind !== 'nutricionales') {
            $patient['height_cm'] = (float) $data['talla'];
            $patient['body_surface_m2'] = (float) $data['superficie_corporal'];
        }
        Validator::make($data, ['clinical_context' => 'nullable|array', 'clinical_context.*' => 'nullable|string|max:1500'])->validate();
        $context = []; $missing = []; $missingFields = [];
        foreach (self::CONTEXT_FIELDS as $key => $label) {
            $context[$key] = trim($data['clinical_context'][$key] ?? '');
            // Required anthropometry is validated in structured fields, not duplicated in free text.
            if ($context[$key] === '' && $key !== 'patient_factors') { $missing[] = $label; $missingFields[] = 'clinical_context['.$key.']'; }
        }
        $base = ['category' => $kind, 'patient' => $patient, 'clinical_context' => $context,
            'missing_context' => $missing, 'missing_fields' => $missingFields,
            'fields' => array_merge(['peso', 'fecha_nacimiento', 'observaciones'], $kind === 'nutricionales' ? [] : ['talla', 'superficie_corporal'], array_map(fn ($key) => 'clinical_context['.$key.']', array_keys(self::CONTEXT_FIELDS))),
            'calculations' => []];
        return $kind === 'nutricionales' ? $this->nutrition($base, $data) : $this->oncology($base, $data);
    }

    public function validatePatient(string $kind, array $data): void
    {
        $rules = ['peso' => 'required|numeric|gt:0|max:1000',
            'fecha_nacimiento' => 'required|date_format:Y-m-d|before_or_equal:today'];
        if ($kind !== 'nutricionales') $rules += ['talla' => 'required|numeric|gt:0|max:300',
            'superficie_corporal' => 'required|numeric|gt:0|max:10'];
        Validator::make($data, $rules, [
            'required' => 'El campo :attribute es obligatorio.',
            'numeric' => 'Captura un numero valido en :attribute.',
            'gt' => 'El campo :attribute debe ser mayor que cero.',
        ], ['peso' => 'peso', 'fecha_nacimiento' => 'fecha de nacimiento', 'talla' => 'talla (cm)',
            'superficie_corporal' => 'superficie corporal (m2)'])->validate();
    }

    private function nutrition(array $base, array $data): array
    {
        Validator::make($data, ['npt' => 'required|in:ADULT,INF'])->validate();
        $base['manual_selection'] = NutritionManual::selection($data['npt']);
        if (!empty($data['clinical_quotation_id'])) return $this->quotedNutrition($base, $data);
        Validator::make($data, ['volumen_total' => 'required|numeric|gt:0|max:100000',
            'sobrellenado_ml' => 'nullable|numeric|min:0|max:100000',
            'via_administracion' => 'required|in:Central,Periférica', 'tiempo_infusion_min' => 'nullable|numeric|gt:0|max:1000',
            'velocidad_infusion' => 'nullable|numeric|gt:0|max:100000'])->validate();
        $components = []; $seen = []; $volume = 0;
        foreach ($data as $field => $value) {
            if (!preg_match('/^i_(\d+)_([^\s]+)$/', $field, $matches)) continue;
            Validator::make([$field => $value], [$field => 'nullable|numeric|min:0|max:1000000'])->validate();
            $id = (int) $matches[1];
            if (isset($seen[$id])) throw ValidationException::withMessages([$field => 'Componente duplicado.']);
            $seen[$id] = true;
            if (!(float) $value) continue;
            $input = Input::find($id);
            if (!$input || $matches[2] !== $input->unidad) throw ValidationException::withMessages([$field => 'Componente o unidad no reconocidos.']);
            if (in_array((int) $input->category_id, [6, 10, 11])) continue;
            if ((float) $input->div <= 0 || (float) $input->mult <= 0) throw ValidationException::withMessages([$field => 'Falta el factor de conversion del catalogo.']);
            $weighted = $data['npt'] === 'INF' && in_array((int) $input->category_id, [1, 2, 3, 4, 8]);
            $total = (float) $value * ($weighted ? $base['patient']['weight_kg'] : 1);
            $ml = $total * (float) $input->mult / (float) $input->div;
            $unit = $input->unidad;
            if ($data['npt'] === 'ADULT' && in_array((int) $input->category_id, [1, 2, 3])) $unit = 'g/dia';
            if ($data['npt'] === 'ADULT' && (int) $input->category_id === 4) $unit = 'mEq/dia';
            $components[] = ['field' => $field, 'medicine' => $input->description, 'quantity' => (float) $value,
                'unit' => $unit, 'weight_factor_applied' => $weighted, 'total_amount' => $total,
                'catalog_multiplier' => (float) $input->mult, 'catalog_divisor' => (float) $input->div,
                'volume_ml' => round($ml, 6)];
            $base['fields'][] = $field;
            $volume += $ml;
        }
        if (!$components) throw ValidationException::withMessages(['observaciones' => 'Captura al menos un componente nutricional.']);
        $base['fields'] = array_merge($base['fields'], ['npt', 'volumen_total', 'sobrellenado_ml', 'via_administracion', 'tiempo_infusion_min', 'velocidad_infusion']);
        $base['mixtures'] = [['components' => $components, 'mode' => $data['npt'],
            'volume_ml' => (float) $data['volumen_total'], 'route' => $data['via_administracion'],
            'overfill_ml' => (float) ($data['sobrellenado_ml'] ?? 0),
            // The existing field name says min, but this form and storage use hours.
            'infusion_hours' => !empty($data['tiempo_infusion_min']) ? (float) $data['tiempo_infusion_min'] : null,
            'infusion_ml_hour' => !empty($data['velocidad_infusion']) ? (float) $data['velocidad_infusion'] : null]];
        $base['calculations'][] = ['field' => 'volumen_total', 'formula' => 'Suma(cantidad * factor_peso * multiplicador_catalogo / divisor_catalogo)',
            'result' => round($volume, 6), 'unit' => 'mL', 'assumptions' => 'Factores del catalogo local; no equivalen a validacion de fichas tecnicas. Agua de aforo no inferida.'];
        $declaredVolume = (float) $data['volumen_total'];
        $base['local_blockers'] = $volume > $declaredVolume + .0001
            ? [['field' => 'volumen_total', 'message' => 'El volumen total es menor que la suma de componentes capturados.',
                'calculation' => round($volume, 4).' mL de componentes - '.round($declaredVolume, 4).' mL de volumen total = '.round($volume - $declaredVolume, 4).' mL de diferencia.',
                'suggestion' => 'Volumen total capturado: '.round($declaredVolume, 4).' mL. Para contener los componentes registrados se requieren al menos '.round($volume, 4).' mL para esta comprobacion aritmetica. '
                    .'Corrige el volumen total solo si corresponde a la orden medica. Si deben mantenerse los '.round($declaredVolume, 4).' mL prescritos, se requiere una formulacion revisada que quepa en ese volumen; no reduzcas dosis ni agregues agua automaticamente.']] : [];
        return $base;
    }

    private function quotedNutrition(array $base, array $data): array
    {
        $quotation = \App\Models\RequestQuotation::findOrFail($data['clinical_quotation_id']);
        $service = app(\App\Services\QuotationPreparationService::class);
        $groups = $service->groupItems($service->items($quotation));
        Validator::make($data, ['quoted_volumes' => 'required|array', 'quoted_volumes.*' => 'required|numeric|gt:0|max:100000',
            'via_administracion' => 'required|in:Central,Periférica', 'tiempo_infusion_min' => 'nullable|numeric|gt:0|max:1000'])->validate();
        $base['mixtures'] = []; $base['local_blockers'] = [];
        $base['fields'][] = 'npt';
        foreach ($groups as $group) {
            $components = []; $volume = 0;
            foreach ($group as $index => $item) {
                $ml = $data['quoted_volumes'][$index] ?? null;
                if (!$ml) throw ValidationException::withMessages(['quoted_volumes.'.$index => 'Falta volumen cotizado.']);
                $input = Input::findOrFail($item['input_id']);
                $field = 'quoted_volumes.'.$index;
                if ((float) $input->mult <= 0 || (float) $input->div <= 0) {
                    throw ValidationException::withMessages([$field => 'Falta el factor de conversion del catalogo.']);
                }
                $components[] = ['field' => $field, 'medicine' => $input->description, 'quantity' => (float) $ml,
                    'unit' => 'mL', 'presentation' => $item['presentation'], 'total_amount' => (float) $ml * $input->div / $input->mult,
                    'amount_unit' => preg_replace('~/Kg~i', '', $input->unidad), 'volume_ml' => (float) $ml];
                $base['fields'][] = $field;
                $volume += (float) $ml;
            }
            $base['mixtures'][] = ['components' => $components, 'mode' => $data['npt'], 'volume_ml' => $volume, 'route' => $data['via_administracion'] ?? null,
                'infusion_hours' => $data['tiempo_infusion_min'] ?? null, 'container' => null];
            $base['calculations'][] = ['field' => 'observaciones', 'formula' => 'Suma de volumenes cotizados', 'result' => $volume,
                'unit' => 'mL', 'assumptions' => 'Sin agua adicional; cantidades activas calculadas con factores de catalogo pendientes de verificar.'];
        }
        return $base;
    }

    private function oncology(array $base, array $data): array
    {
        $mixtures = is_string($data['mezclas'] ?? null) ? json_decode($data['mezclas'], true) : null;
        Validator::make(['mezclas' => $mixtures], ['mezclas' => 'required|array|min:1|max:99',
            'mezclas.*.volumen_dilucion' => 'required|numeric|gt:0|max:100000',
            'mezclas.*.tiempo_infusion' => 'required|numeric|gt:0|max:100000',
            'mezclas.*.medicamentos' => 'required|array|min:1|max:50',
            'mezclas.*.medicamentos.*.medicamento_id' => 'required|integer|min:1',
            'mezclas.*.medicamentos.*.dosis' => 'required|numeric|gt:0|max:1000000',
            'mezclas.*.medicamentos.*.diluyente_id' => 'required|integer|min:1',
            'mezclas.*.medicamentos.*.via_administracion_id' => 'required|integer|min:1'])->validate();
        $base['mixtures'] = []; $base['local_blockers'] = [];
        foreach ($mixtures as $i => $mix) {
            $components = [];
            foreach ($mix['medicamentos'] as $j => $row) {
                $field = "mezclas.$i.medicamentos.$j.dosis";
                $medicine = MedicinesCatalog::find($row['medicamento_id']);
                $diluent = Diluent::find($row['diluyente_id']);
                $route = AdministrationRoute::find($row['via_administracion_id']);
                if (!$medicine || !$diluent || !$route) throw ValidationException::withMessages([$field => 'Medicamento, diluyente o via no reconocidos.']);
                if ($medicine->catalog_category !== $base['category']) throw ValidationException::withMessages([$field => 'El medicamento no corresponde al tipo de solicitud.']);
                $components[] = ['field' => $field, 'medicine' => $medicine->denominacion, 'quantity' => (float) $row['dosis'],
                    'unit' => 'mg', 'diluent' => $diluent->denominacion_generica, 'route' => $route->name,
                    'presentation' => null, 'container' => null];
                $base['fields'][] = $field;
                $base['calculations'][] = ['field' => $field, 'formula' => $row['dosis'].' mg / '.$mix['volumen_dilucion'].' mL',
                    'result' => round((float) $row['dosis'] / (float) $mix['volumen_dilucion'], 6), 'unit' => 'mg/mL',
                    'assumptions' => 'Volumen final capturado, pendiente de verificar desplazamiento y presentacion.'];
            }
            $base['mixtures'][] = ['components' => $components, 'volume_ml' => (float) $mix['volumen_dilucion'], 'infusion_minutes' => (float) $mix['tiempo_infusion']];
            $base['fields'][] = "mezclas.$i.volumen_dilucion";
            $base['fields'][] = "mezclas.$i.tiempo_infusion";
        }
        return $base;
    }

    public function fromTarget(array $target): array
    {
        $model = $target['model'];
        if ($target['kind'] === 'nutricionales') {
            $model->loadMissing(['solicitud_patient', 'solicitud_detail', 'input.input']);
            $data = array_merge($model->solicitud_patient?->getAttributes() ?? [], $model->solicitud_detail?->getAttributes() ?? []);
            foreach ($model->input as $row) if ($row->input) $data['i_'.$row->input_id.'_'.$row->input->unidad] = $row->valor;
        } else {
            $model->loadMissing(['solicitud', 'medicamentos.medicamentoOnco']);
            $data = $model->solicitud->getAttributes();
            $data['mezclas'] = json_encode([['volumen_dilucion' => $model->volumen_dilucion, 'tiempo_infusion' => $model->tiempo_infusion,
                'medicamentos' => $model->medicamentos->map(fn ($m) => ['medicamento_id' => $m->medicamentoOnco?->catalog_id,
                    'dosis' => $m->dosis, 'diluyente_id' => $m->diluyente_id, 'via_administracion_id' => $m->via_administracion_id])->all()]], JSON_THROW_ON_ERROR);
        }
        $review = \App\Models\ClinicalReview::where('record_type', $target['kind'] === 'nutricionales' ? 'solicituds' : 'solicitud_oncos')
            ->where('record_id', $target['kind'] === 'nutricionales' ? $model->id : $model->solicitud->id)->whereNotNull('used_at')->latest()->first();
        $data['clinical_context'] = $review?->clinical_context ?? [];
        $case = $this->normalize($target['kind'], $data);
        $case['context_recorded_at'] = $review?->created_at?->toIso8601String();
        return $case;
    }
}
