<?php

namespace App\Services\Integrations\DrSam;

use App\Models\ExternalMixtureRequest;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\Solicitud;
use App\Models\Nutricionales\SolicitudDetail;
use App\Models\Nutricionales\SolicitudInput;
use App\Models\Nutricionales\SolicitudPatient;
use App\Models\Oncologicos\MedicineOnco;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\MezclaMedicamento;
use App\Models\Oncologicos\SolicitudOnco;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ExternalMixtureMaterializer
{
    public function materialize(ExternalMixtureRequest $external): bool
    {
        if ($external->materialized_id) {
            if ($external->materialized_type === 'npt') {
                $request = Solicitud::query()->find($external->materialized_id);
                if ($request) {
                    try {
                        $this->normalizeNptInputValues($external, $request);
                        $this->attachDefaultEvaBag($external, $request);
                        $this->attachInfusionSet($external, $request);
                    } catch (Throwable $exception) {
                        $external->update(['last_error' => mb_substr($exception->getMessage(), 0, 4000)]);

                        return false;
                    }
                }
            }

            return true;
        }

        try {
            DB::transaction(function () use ($external): void {
                $locked = ExternalMixtureRequest::query()->lockForUpdate()->findOrFail($external->id);
                if ($locked->materialized_id) {
                    return;
                }

                $domain = $locked->catalog_type === 'npt'
                    ? $this->materializeNpt($locked)
                    : $this->materializeOncology($locked);

                $locked->update([
                    'status' => 'materialized',
                    'materialized_type' => $locked->catalog_type,
                    'materialized_id' => $domain->getKey(),
                    'materialized_at' => now(),
                    'last_error' => null,
                ]);
            });

            $external->refresh();

            return true;
        } catch (Throwable $exception) {
            $external->update([
                'status' => 'materialization_failed',
                'last_error' => mb_substr($exception->getMessage(), 0, 4000),
            ]);

            return false;
        }
    }

    private function materializeNpt(ExternalMixtureRequest $external): Solicitud
    {
        $payload = $external->payload;
        $format = data_get($payload, 'clinical.format', []);
        $user = $this->operationalUser($external);
        [$firstName, $lastName] = $this->splitName(data_get($payload, 'patient.name'));
        $birthDate = data_get($format, 'birth_date') ?: now()->subYears(18)->toDateString();
        $weight = (float) (data_get($format, 'weight') ?: 1);

        $patient = SolicitudPatient::query()->create([
            'nombre_paciente' => $firstName,
            'apellidos_paciente' => $lastName,
            'servicio' => data_get($format, 'clinical_service', data_get($payload, 'clinical.service', 'Nutricion clinica')),
            'cama' => data_get($format, 'bed'),
            'piso' => data_get($format, 'floor'),
            'registro' => data_get($format, 'registration', data_get($payload, 'patient.external_id')),
            'diagnostico' => data_get($payload, 'clinical.diagnosis'),
            'fecha_nacimiento' => $birthDate,
            'edad' => Carbon::parse($birthDate)->age,
            'peso' => $weight,
            'sexo' => $this->nptSex(data_get($format, 'sex')),
        ]);
        $detail = SolicitudDetail::query()->create([
            'via_administracion' => data_get($format, 'route') === 'Central' ? 'Central' : 'Periférica',
            'tiempo_infusion_min' => (int) round(((float) data_get($format, 'infusion_hours', 24)) * 60),
            'sobrellenado_ml' => data_get($format, 'overfill'),
            'volumen_total' => data_get($format, 'total_volume'),
            'npt' => data_get($format, 'npt_type') === 'Pediatrica' ? 'INF' : 'ADULT',
            'nombre_medico' => data_get($format, 'doctor_name', data_get($payload, 'clinical.doctor', 'Medico remitente')),
            'cedula' => data_get($format, 'professional_license', 'Sin cedula'),
            'fecha_hora_entrega' => data_get($format, 'delivery_at', now()->addDay()),
            'observaciones' => data_get($payload, 'clinical.notes'),
            'velocidad_infusion' => data_get($format, 'infusion_rate'),
            'hospital_destino' => data_get($format, 'destination_hospital', $external->hospital->name),
        ]);
        $request = Solicitud::query()->create([
            'user_id' => $user->id,
            'solicitud_detail_id' => $detail->id,
            'solicitud_patient_id' => $patient->id,
            'estado' => 'pendiente',
        ]);

        foreach ($payload['items'] as $item) {
            $presentation = NutritionMedicinePresentation::query()
                ->with('catalog')
                ->where('external_code', $item['presentation_code'])
                ->firstOrFail();
            $inputId = $presentation->catalog?->input_id;
            if (! $inputId) {
                throw new RuntimeException('La presentacion NPT no tiene insumo operativo asociado.');
            }
            $price = (float) ($presentation->listItems()
                ->where('nutri_medicine_list_id', $external->hospital->nutri_medicine_list_id)
                ->value('precio_ml') ?? 0);
            $quantity = (float) $item['quantity'];
            $input = $presentation->catalog?->input;
            $storedValue = $this->nptInputValue($quantity, $weight, data_get($format, 'npt_type'), $input);
            SolicitudInput::query()->create([
                'solicitud_id' => $request->id,
                'input_id' => $inputId,
                'nutrition_medicine_presentation_id' => $presentation->id,
                'valor' => $storedValue,
                'valor_ml' => $quantity,
                'precio_ml' => round($quantity * $price, 4),
            ]);
        }

        $this->attachDefaultEvaBag($external, $request);
        $this->attachInfusionSet($external, $request);

        return $request;
    }

    private function nptInputValue(float $quantityMl, float $weight, ?string $nptType, $input): float
    {
        $pediatric = $nptType === 'Pediatrica';
        $weightBasedCategory = in_array((int) ($input?->category_id), [1, 2, 3, 4, 8], true);

        if (! $pediatric || ! $weightBasedCategory || $weight <= 0) {
            return $quantityMl;
        }

        $multiplier = (float) ($input?->mult ?: 1);
        $divisor = (float) ($input?->div ?: 1);

        return round(($quantityMl * $divisor) / ($weight * $multiplier), 6);
    }

    private function normalizeNptInputValues(ExternalMixtureRequest $external, Solicitud $request): void
    {
        $weight = (float) (data_get($external->payload, 'clinical.format.weight') ?: 1);
        $nptType = data_get($external->payload, 'clinical.format.npt_type');

        foreach (data_get($external->payload, 'items', []) as $item) {
            $presentation = NutritionMedicinePresentation::query()
                ->with('catalog.input')
                ->where('external_code', $item['presentation_code'])
                ->first();
            if (! $presentation) {
                continue;
            }

            $quantity = (float) $item['quantity'];
            SolicitudInput::query()
                ->where('solicitud_id', $request->id)
                ->where('nutrition_medicine_presentation_id', $presentation->id)
                ->update([
                    'valor' => $this->nptInputValue($quantity, $weight, $nptType, $presentation->catalog?->input),
                    'valor_ml' => $quantity,
                ]);
        }
    }

    private function attachDefaultEvaBag(ExternalMixtureRequest $external, Solicitud $request): void
    {
        $hasEvaBag = SolicitudInput::query()
            ->where('solicitud_id', $request->id)
            ->whereHas('input', fn ($query) => $query->where('category_id', 6))
            ->exists();

        if ($hasEvaBag) {
            return;
        }

        $listItem = NutriMedicineListItem::query()
            ->with(['presentation.catalog.input', 'presentation.stocks' => function ($query) use ($external): void {
                $query->where('laboratory_id', $external->hospital->laboratory_id)
                    ->where('is_active', true)
                    ->where('stock_ml_actual', '>', 0)
                    ->orderBy('caducidad')
                    ->orderBy('id');
            }])
            ->where('nutri_medicine_list_id', $external->hospital->nutri_medicine_list_id)
            ->get()
            ->first(fn ($item) => (int) ($item->presentation?->catalog?->input?->category_id) === 6);

        $presentation = $listItem?->presentation;
        $inputId = $presentation?->catalog?->input_id;

        if (! $presentation || ! $inputId) {
            throw new RuntimeException('La lista nutricional del hospital no tiene una Bolsa EVA operativa configurada.');
        }

        $stock = $presentation->stocks->first();
        if (! $stock) {
            throw new RuntimeException('La Bolsa EVA configurada no tiene existencia disponible.');
        }

        SolicitudInput::query()->create([
            'solicitud_id' => $request->id,
            'input_id' => $inputId,
            'nutrition_medicine_presentation_id' => $presentation->id,
            'valor' => 0,
            'valor_ml' => 0,
            'precio_ml' => (float) ($listItem->precio_ml ?? 0),
            'lote' => $stock->lote,
            'caducidad' => $stock->caducidad,
        ]);
    }

    private function attachInfusionSet(ExternalMixtureRequest $external, Solicitud $request): void
    {
        $requested = mb_strtolower((string) data_get($external->payload, 'clinical.format.infusion_set'));
        if (! in_array($requested, ['si', 'sí', 'yes', '1', 'true'], true)) {
            return;
        }

        if (SolicitudInput::query()->where('solicitud_id', $request->id)->where('input_id', 40)->exists()) {
            return;
        }

        $listItem = NutriMedicineListItem::query()
            ->with(['presentation.catalog', 'presentation.stocks' => function ($query) use ($external): void {
                $query->where('laboratory_id', $external->hospital->laboratory_id)
                    ->where('is_active', true)->where('stock_ml_actual', '>', 0)
                    ->orderBy('caducidad')->orderBy('id');
            }])
            ->where('nutri_medicine_list_id', $external->hospital->nutri_medicine_list_id)
            ->whereHas('presentation.catalog', fn ($query) => $query->where('input_id', 40))
            ->first();

        $presentation = $listItem?->presentation;
        $stock = $presentation?->stocks?->first();
        if (! $presentation || ! $stock) {
            throw new RuntimeException('El equipo de infusión solicitado no tiene una presentación con existencia disponible.');
        }

        SolicitudInput::query()->create([
            'solicitud_id' => $request->id, 'input_id' => 40,
            'nutrition_medicine_presentation_id' => $presentation->id,
            'valor' => 1, 'valor_ml' => 1, 'precio_ml' => (float) ($listItem->precio_ml ?? 0),
            'lote' => $stock->lote, 'caducidad' => $stock->caducidad,
        ]);
    }

    private function materializeOncology(ExternalMixtureRequest $external): SolicitudOnco
    {
        $payload = $external->payload;
        $format = data_get($payload, 'clinical.format', []);
        $user = $this->operationalUser($external);
        $birthDate = data_get($format, 'birth_date');
        $request = SolicitudOnco::query()->create([
            'user_id' => $user->id,
            'hospital_id' => $external->hospital_id,
            'servicio' => data_get($payload, 'clinical.service', 'Oncologia'),
            'nombre_paciente' => data_get($payload, 'patient.name'),
            'sexo' => data_get($format, 'sex') === 'Femenino' ? 'F' : 'M',
            'edad' => data_get($format, 'age', $birthDate ? Carbon::parse($birthDate)->age : null),
            'peso' => data_get($format, 'weight'),
            'cama' => data_get($format, 'bed'),
            'piso' => data_get($format, 'floor'),
            'registro_paciente' => data_get($payload, 'patient.external_id'),
            'fecha_nacimiento' => $birthDate,
            'diagnostico' => data_get($payload, 'clinical.diagnosis', 'Sin diagnostico'),
            'alergias' => data_get($format, 'allergies', ''),
            'fecha_entrega' => data_get($payload, 'clinical.required_at'),
            'observaciones' => data_get($payload, 'clinical.notes'),
            'nombre_medico' => data_get($format, 'doctor_name', data_get($payload, 'clinical.doctor', 'Medico remitente')),
            'cedula_medico' => data_get($format, 'professional_license', 'Sin cedula'),
            'estado' => 'pendiente',
        ]);

        foreach ($payload['items'] as $index => $item) {
            $presentation = MedicinePresentation::query()
                ->with('catalog')
                ->where('external_code', $item['presentation_code'])
                ->firstOrFail();
            $catalog = $presentation->catalog;
            $medicine = MedicineOnco::query()->firstOrCreate(['catalog_id' => $catalog->id], ['precio' => 0]);
            $clinicalMedication = data_get($format, "medications.{$index}", []);
            $dose = $item['unit'] === 'mg'
                ? (float) $item['quantity']
                : ((float) $item['quantity']) * (float) $presentation->contenido_valor;
            $pivot = $presentation->lists()->whereKey($external->hospital->onco_medicine_list_id)->first()?->pivot;
            $mixture = Mezcla::query()->create([
                'solicitud_id' => $request->id,
                'volumen_dilucion' => data_get($clinicalMedication, 'dilution_volume', $presentation->volumen_diluyente ?: 1),
                'tiempo_infusion' => (string) data_get($clinicalMedication, 'infusion_minutes', 60),
                'estado' => 'pendiente',
            ]);
            MezclaMedicamento::query()->create([
                'mezcla_id' => $mixture->id,
                'medicamento_id' => $medicine->id,
                'nombre_medicamento' => $catalog->denominacion,
                'denominacion_snapshot' => $catalog->denominacion,
                'marca_snapshot' => $presentation->marca,
                'requires_infusor_snapshot' => $catalog->requires_infusor,
                'conc_min_snapshot' => $catalog->conc_min,
                'conc_max_snapshot' => $catalog->conc_max,
                'dosis' => $dose,
                'charge_by' => ($pivot?->charge_by ?? 'mg') === 'frasco' ? 'frasco' : 'mg',
                'precio_mg_snapshot' => $pivot?->precio_mg_override,
            ]);
        }

        return $request;
    }

    private function operationalUser(ExternalMixtureRequest $external)
    {
        $user = $external->hospital->users()->where('is_active', true)->orderBy('id')->first();
        if (! $user) {
            throw new RuntimeException('El hospital no tiene un usuario operativo activo para registrar la solicitud.');
        }

        return $user;
    }

    private function splitName(?string $fullName): array
    {
        $parts = preg_split('/\s+/', trim((string) $fullName), 2);

        return [$parts[0] ?: 'Paciente', $parts[1] ?? 'Sin apellidos'];
    }

    private function nptSex(?string $sex): ?string
    {
        return in_array($sex, ['Femenino', 'Masculino'], true) ? $sex : null;
    }
}
