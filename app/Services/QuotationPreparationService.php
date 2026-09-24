<?php

namespace App\Services;

use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudInput;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\SolicitudOnco;
use App\Models\RequestQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationPreparationService
{
    public const LOCKED_MESSAGE = 'Los medicamentos y cantidades deben respetar la cotizacion autorizada. Los cambios requieren otra cotizacion.';

    public function guardChanges(\Illuminate\Database\Eloquent\Model $target, Request $request): void
    {
        if (!$target->quotation_pricing_snapshot) return;
        $action = $request->input('accion', 'actualizar');
        if (in_array($action, ['rechazar', 'cancelar', 'preparar', 'revisar', 'entregar'], true)) return;
        $this->require($action !== 'ajustar');
        if ($target instanceof Solicitud) {
            $target->loadMissing('input.input', 'solicitud_detail', 'solicitud_patient');
            $this->require((float) $request->input('sobrellenado_ml', 0) === 0.0
                && (float) $request->input('volumen_total', 0) === 0.0);
            $expected = $target->input->keyBy('input_id');
            $submitted = [];
            foreach ($request->all() as $key => $value) {
                if (preg_match('/^i_(\d+)$/', $key, $matches) && (float) $value !== 0.0) $submitted[(int) $matches[1]] = (float) $value;
            }
            $this->require(count($submitted) === $expected->count());
            foreach ($expected as $id => $row) {
                $weight = $request->input('npt') !== 'ADULT' && in_array((int) $row->input->category_id, [1, 8, 2, 3, 4], true)
                    ? (float) $request->input('peso') : 1;
                $volume = ($submitted[$id] ?? 0) * $row->input->mult / $row->input->div * $weight;
                $this->require(abs($volume - (float) $row->valor_ml) < 0.001
                    && (int) $request->input('p_'.$id) === (int) $row->nutrition_medicine_presentation_id);
            }
            return;
        }
        $payload = json_decode($request->input('mezcla_json', ''), true);
        $this->require(is_array($payload));
        $meds = $payload['medicamentos'] ?? [];
        $target->loadMissing('medicamentos.medicamentoOnco');
        $this->require(count($meds) === $target->medicamentos->count());
        foreach ($target->medicamentos->values() as $index => $medicine) {
            $med = $meds[$index] ?? [];
            $this->require((int) ($med['medicamento_id'] ?? 0) === (int) $medicine->medicamentoOnco->catalog_id
                && abs((float) ($med['dosis'] ?? 0) - (float) $medicine->dosis) < 0.00005);
            $line = $target->quotation_pricing_snapshot['lines'][$index];
            $payload['medicamentos'][$index]['charge_by'] = $line['unit'] === 'frasco' ? 'frasco' : 'mg';
            $payload['medicamentos'][$index]['precio_mg'] = $line['unit'] === 'mg' ? $line['unit_price'] : null;
            $payload['medicamentos'][$index]['precio_frasco'] = $line['unit'] === 'frasco' ? $line['unit_price'] : null;
            foreach ($med['presentaciones'] ?? [] as $presentation) {
                $this->require(DB::table('medicine_batches')->where('id', $presentation['batch_id'] ?? 0)
                    ->where('medicine_presentation_id', $line['presentation_id'])->exists());
            }
        }
        $this->require(empty($payload['infusor_id']) && empty($payload['set_infusion']));
        $request->merge(['mezcla_json' => json_encode($payload, JSON_THROW_ON_ERROR)]);
    }

    public function items(RequestQuotation $quotation): array
    {
        $data = $quotation->clinical_data ?? [];
        $nutrition = $quotation->category === 'nutricionales';
        $source = $data['items'] ?? $data[$nutrition ? 'components' : 'rows'] ?? [];
        $lines = array_values(array_filter($quotation->pricing_snapshot['lines'] ?? [], fn ($line) => $line['unit'] !== 'servicio'));
        $this->require(count($source) > 0 && count($source) === count($lines), 'La cotizacion no contiene el detalle necesario para generar una solicitud.');
        $items = [];
        foreach ($source as $index => $row) {
            $presentation = $nutrition
                ? NutritionMedicinePresentation::with('catalog.input')->find($row['presentation_id'])
                : MedicinePresentation::with('catalog')->find($row['presentation_id']);
            $this->require($presentation && $presentation->is_available && $presentation->catalog, 'Una presentacion cotizada ya no esta disponible. Se requiere otra cotizacion.');
            $line = $lines[$index];
            $count = (int) ($line['mixtures'] ?? 1);
            $this->require($count > 0 && $count <= 99 && (!$nutrition || $count === 1), 'Cantidad de mezclas cotizadas no valida.');
            $capacity = array_key_exists('presentation_content', $line) ? $line['presentation_content']
                : ($nutrition ? (float) $presentation->presentacion_ml : $presentation->contentInMilligrams());
            $capacity = is_numeric($capacity) && $capacity > 0 ? (float) $capacity : null;
            $quotedConcentration = $line['unit'] === 'frasco'
                ? ($capacity === null ? null : round((float) $line['quantity'] * $capacity, 4))
                : (float) $line['quantity'];
            $clinicalQuantity = $row['dose_mg'] ?? $row['quoted_volume_ml'] ?? $row['volume_ml']
                ?? ($line['unit'] !== 'frasco' ? $line['quantity'] : null);
            if ($nutrition) $this->require($presentation->catalog->input && $presentation->catalog->input->mult > 0
                && $presentation->catalog->input->div > 0, 'El componente cotizado no tiene una conversion nutricional configurada.');
            for ($copy = 0; $copy < $count; $copy++) {
                $singleLine = $line;
                $singleLine['mixtures'] = 1;
                foreach (['subtotal', 'vat'] as $field) $singleLine[$field] = $this->share((float) $line[$field], $copy, $count);
                $singleLine['total'] = round($singleLine['subtotal'] + $singleLine['vat'], 2);
                $items[] = [
                    'mixture_number' => (int) ($line['mixture_number'] ?? ($nutrition ? 1 : count($items) + 1)),
                    'presentation_id' => $presentation->id,
                    'catalog_id' => (int) ($nutrition ? $presentation->nutrition_medicine_catalog_id : $presentation->catalog_id),
                    'input_id' => $nutrition ? $presentation->catalog->input->id : null,
                    'name' => $line['description'], 'presentation' => $line['presentation'],
                    'quantity' => (float) $line['quantity'], 'unit' => $line['unit'],
                    'clinical_quantity' => $clinicalQuantity === null ? null : (float) $clinicalQuantity,
                    'quoted_concentration' => $quotedConcentration,
                    'concentration_unit' => $nutrition ? 'mL' : 'mg',
                    'capacity' => $capacity, 'line' => $singleLine,
                    'diluent_id' => $row['diluent_id'] ?? null,
                    'dilution_ml' => $row['dilution_ml'] ?? null,
                    'infusion_minutes' => $row['infusion_minutes'] ?? null,
                ];
            }
        }
        $this->require($nutrition || count($items) <= 99, 'La cotizacion supera las 99 mezclas por solicitud.');
        // Keep each presentation and its frozen price together when grouping clinical rows.
        $items = collect($this->groupItems($items))->flatMap(fn ($group) => $group->groupBy('catalog_id')->flatten(1))->values()->all();
        if ($nutrition) foreach ($this->groupItems($items) as $group) {
            $this->require($group->pluck('input_id')->unique()->count() === $group->count(),
                'Una mezcla repite un componente nutricional. Se requiere una presentacion por componente en cada mezcla.');
        }
        return $items;
    }

    public function groupItems(array $items): array
    {
        return collect($items)->groupBy('mixture_number', preserveKeys: true)->sortKeys()->all();
    }

    public function medicationGroups(array $items): array
    {
        return collect($items)->groupBy('catalog_id', preserveKeys: true)->map(function ($rows) {
            $first = $rows->first();
            return [
                'catalog_id' => $first['catalog_id'], 'name' => $first['name'],
                'concentration_unit' => $first['concentration_unit'], 'items' => $rows->all(),
                'quoted_concentration' => $rows->contains(fn ($row) => $row['quoted_concentration'] === null)
                    ? null : round($rows->sum('quoted_concentration'), 4),
            ];
        })->values()->all();
    }

    public function defaults(RequestQuotation $quotation, array $items): array
    {
        $data = $quotation->clinical_data ?? [];
        $defaults = [
            'paciente_nombre' => $quotation->patient_name,
            'nombre_paciente' => $data['patient_name'] ?? '',
            'apellidos_paciente' => trim(($data['patient_paternal_surname'] ?? '').' '.($data['patient_maternal_surname'] ?? '')),
            'registro' => $data['patient_platform_id'] ?? $data['record_number'] ?? '',
            'servicio' => $data['service'] ?? '', 'sexo' => $data['sex'] ?? '',
            'fecha_nacimiento' => $data['birth_date'] ?? '', 'peso' => $data['weight_kg'] ?? '',
            'diagnostico' => $data['diagnosis'] ?? '', 'observaciones' => $data['observations'] ?? '',
            'medico_nombre' => $data['doctor_name'] ?? '', 'nombre_medico' => $data['doctor_name'] ?? '',
            'medico_cedula' => $data['doctor_license'] ?? '', 'cedula' => $data['doctor_license'] ?? '',
        ];
        $defaults['mezclas'] = [];
        foreach ($this->groupItems($items) as $group) {
            $item = $group->first();
            $defaults['mezclas'][] = [
                'volumen_dilucion' => $item['dilution_ml'], 'tiempo_infusion' => $item['infusion_minutes'],
                'fechas_entrega' => [''], 'set_infusion' => false, 'infusor_id' => null,
                'medicamentos' => $group->map(fn ($medicine) => ['medicamento_id' => $medicine['catalog_id'], 'nombre' => $medicine['name'],
                    'dosis' => $medicine['clinical_quantity'], 'diluyente_id' => $medicine['diluent_id'], 'via_administracion_id' => null])->values()->all(),
            ];
        }
        return $defaults;
    }

    public function validateOncology(RequestQuotation $quotation, Request $request): void
    {
        $request->validate(['mezclas' => 'required|string|json', 'tipo_solicitud' => 'required|string',
            'cantidad_mezclas' => 'required|integer|min:1|max:99']);
        $groups = array_values($this->groupItems($this->items($quotation)));
        $mixtures = json_decode($request->input('mezclas', ''), true);
        $this->require($request->input('tipo_solicitud') === $quotation->category
            && is_array($mixtures) && array_is_list($mixtures) && count($mixtures) === count($groups)
            && (int) $request->input('cantidad_mezclas') === count($groups));
        foreach ($groups as $index => $group) {
            $mixture = $mixtures[$index];
            $this->require(is_array($mixture));
            $meds = $mixture['medicamentos'] ?? [];
            $this->require(is_array($meds) && array_is_list($meds) && count($meds) === $group->count()
                && is_array($mixture['fechas_entrega'] ?? null) && count($mixture['fechas_entrega']) === 1);
            foreach ($group->values() as $medicineIndex => $item) {
                $med = $meds[$medicineIndex];
                $this->require(is_array($med) && (int) ($med['medicamento_id'] ?? 0) === (int) $item['catalog_id']);
                $this->validateClinicalQuantity($item, $med['dosis'] ?? null);
                foreach (['diluent_medicine_catalog' => ['diluent_id', 'diluyente_id'],
                    'administration_route_medicine_catalog' => ['administration_route_id', 'via_administracion_id']] as $table => [$column, $field]) {
                    $this->require(DB::table($table)->where('medicine_catalog_id', $item['catalog_id'])
                        ->where($column, $med[$field] ?? null)->exists(), 'Selecciona un diluyente y una via de administracion validos para cada medicamento.');
                }
                $mixtures[$index]['medicamentos'][$medicineIndex]['nombre'] = $item['name'];
                $mixtures[$index]['medicamentos'][$medicineIndex]['charge_by'] = $item['unit'] === 'frasco' ? 'frasco' : 'mg';
            }
            $this->require(empty($mixture['infusor_id']) && empty($mixture['set_infusion']),
                'Los insumos adicionales requieren una cotizacion que los incluya.');
        }
        $request->merge(['mezclas' => json_encode($mixtures, JSON_THROW_ON_ERROR)]);
    }

    private function validateClinicalQuantity(array $item, mixed $quantity): void
    {
        $this->require(is_numeric($quantity) && is_finite((float) $quantity) && $quantity > 0,
            'Completa la dosis o volumen clinico de cada medicamento.');
        if ($item['clinical_quantity'] !== null) {
            $this->require(abs((float) $quantity - $item['clinical_quantity']) < 0.00005);
        } else {
            $this->require($item['capacity'] > 0, 'Falta el contenido por frasco de una presentacion cotizada.');
            $this->require($quantity <= $item['quantity'] * $item['capacity'] + 0.00005,
                'La dosis o volumen clinico supera los frascos cotizados. Se requiere otra cotizacion.');
        }
    }

    public function attachOncology(RequestQuotation $quotation, SolicitudOnco $request): void
    {
        $groups = array_values($this->groupItems($this->items($quotation)));
        $mixtures = $request->mezclas()->orderBy('id')->get();
        $this->require($mixtures->count() === count($groups));
        foreach ($mixtures as $index => $mixture) {
            $group = $groups[$index]->values();
            $mixture->forceFill(['quotation_pricing_snapshot' => $this->groupSnapshot($quotation, $group->all(), $index, count($groups))])->save();
            $medicines = $mixture->medicamentos()->orderBy('id')->get();
            $this->require($medicines->count() === $group->count());
            foreach ($medicines as $medicineIndex => $medicine) {
                $item = $group[$medicineIndex];
                $medicine->update([
                    'precio_mg_snapshot' => $item['unit'] === 'mg' ? $item['line']['unit_price'] : null,
                    'denominacion_snapshot' => $item['name'], 'marca_snapshot' => $item['presentation'],
                ]);
            }
        }
        $this->link($quotation, $request);
    }

    public function attachNutrition(RequestQuotation $quotation, Solicitud $solicitud, Request $request): void
    {
        $items = $this->items($quotation);
        $volumes = $request->input('quoted_volumes', []);
        $this->require(is_array($volumes) && count($volumes) === count($items));
        $request->validate(['peso' => 'required|numeric|gt:0|max:500', 'npt' => 'required|in:ADULT,INF',
            'sobrellenado_ml' => 'nullable|numeric|in:0', 'volumen_total' => 'prohibited']);
        foreach ($request->all() as $key => $value) {
            if (preg_match('/^i_\d+_/', $key)) $this->require(empty($value));
        }
        foreach ($items as $index => $item) $this->validateClinicalQuantity($item, $volumes[$index] ?? null);
        $groups = array_values($this->groupItems($items));
        foreach ($groups as $groupIndex => $group) {
            $target = $solicitud;
            if ($groupIndex > 0) {
                $patient = $solicitud->solicitud_patient->replicate(); $patient->save();
                $detail = $solicitud->solicitud_detail->replicate(); $detail->save();
                $target = $solicitud->replicate();
                $target->forceFill(['solicitud_patient_id' => $patient->id, 'solicitud_detail_id' => $detail->id])->save();
                $target->setRelation('solicitud_detail', $detail);
            }
            $totalVolume = 0;
            foreach ($group as $index => $item) {
                $volume = $volumes[$index] ?? null;
                $input = NutritionMedicinePresentation::with('catalog.input')->findOrFail($item['presentation_id'])->catalog->input;
                $weight = $request->input('npt') !== 'ADULT' && in_array((int) $input->category_id, [1, 8, 2, 3, 4], true)
                    ? (float) $request->input('peso') : 1;
                SolicitudInput::create([
                    'solicitud_id' => $target->id, 'input_id' => $item['input_id'],
                    'nutrition_medicine_presentation_id' => $item['presentation_id'],
                    'valor_ml' => $volume, 'valor' => (float) $volume * $input->div / ($input->mult * $weight),
                    'precio_ml' => $item['unit'] === 'ml' ? $item['line']['unit_price'] : 0,
                ]);
                $totalVolume += (float) $volume;
            }
            $target->solicitud_detail->update(['suma_volumen' => $totalVolume, 'volumen_total_final' => $totalVolume]);
            $target->forceFill(['hospital_id' => $quotation->hospital_id, 'request_quotation_id' => $quotation->id,
                'quotation_pricing_snapshot' => $this->groupSnapshot($quotation, $group->values()->all(), $groupIndex, count($groups))])->save();
        }
        $this->link($quotation, $solicitud);
    }

    private function groupSnapshot(RequestQuotation $quotation, array $group, int $index, int $count): array
    {
        $number = $group[0]['mixture_number'];
        $lines = array_map(fn ($item) => $item['line'] + ['presentation_id' => $item['presentation_id']], $group);
        foreach ($quotation->pricing_snapshot['lines'] as $line) {
            if ($line['unit'] !== 'servicio') continue;
            if (isset($line['mixture_number'])) {
                if ((int) $line['mixture_number'] !== $number) continue;
            } else {
                $line['quantity'] = 1;
                foreach (['subtotal', 'vat'] as $field) $line[$field] = $this->share((float) $line[$field], $index, $count);
                $line['total'] = round($line['subtotal'] + $line['vat'], 2);
            }
            $lines[] = $line;
        }
        return ['quotation_id' => $quotation->id, 'folio' => $quotation->folio, 'mixture_number' => $number,
            'lines' => $lines, 'total' => round(array_sum(array_column($lines, 'total')), 2)];
    }

    private function link(RequestQuotation $quotation, Solicitud|SolicitudOnco $request): void
    {
        $request->forceFill(['request_quotation_id' => $quotation->id])->save();
        $quotation->forceFill(['request_id' => $request->id, 'status' => 'preparacion'])->save();
    }

    private function share(float $amount, int $index, int $count): float
    {
        $cents = (int) round($amount * 100);
        return (intdiv($cents, $count) + ($index < $cents % $count ? 1 : 0)) / 100;
    }

    private function require(bool $condition, string $message = self::LOCKED_MESSAGE): void
    {
        if (!$condition) throw ValidationException::withMessages(['quotation' => $message]);
    }
}
