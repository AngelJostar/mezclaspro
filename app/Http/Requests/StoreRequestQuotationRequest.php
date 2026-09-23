<?php

namespace App\Http\Requests;

use App\Models\RequestQuotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequestQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && (RequestQuotation::canCreate($this->user(), 'oncologicos')
            || RequestQuotation::canCreate($this->user(), 'nutricionales'));
    }

    public function rules(): array
    {
        $oncology = $this->input('category') === 'oncologicos';
        return [
            'category' => ['required', Rule::in(['oncologicos', 'nutricionales'])],
            'hospital_id' => ['required', 'integer', 'min:1'],
            'institution_id' => ['required', 'integer', 'min:1'],
            'seller_id' => ['nullable', 'integer', 'min:1'],
            'submission_key' => ['required', 'uuid'],
            'action' => ['required', Rule::in(['save', 'send'])],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'service' => ['required', 'string', 'max:100'],
            'floor' => ['nullable', 'string', 'max:50'],
            'bed' => ['nullable', 'string', 'max:50'],
            'record_number' => ['nullable', 'string', 'max:100'],
            'patient_name' => ['required', 'string', 'max:255'],
            'sex' => [$oncology ? 'required' : 'nullable', Rule::in(['F', 'M'])],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:'.now()->subYears(130)->toDateString()],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:500'],
            'height_cm' => [$oncology ? 'required' : 'exclude', 'numeric', 'gt:0', 'max:300'],
            'body_surface_m2' => [$oncology ? 'nullable' : 'exclude', 'numeric', 'gt:0', 'max:10'],
            'diagnosis' => [$oncology ? 'required' : 'nullable', 'string', 'max:255'],
            'observations' => ['nullable', 'string', 'max:500'],
            'doctor_name' => ['required', 'string', 'max:255'],
            'doctor_license' => ['required', 'string', 'max:100'],
            'delivery_method' => [$oncology ? 'nullable' : 'exclude', 'string', 'max:100'],
            'attachment' => [$oncology ? 'nullable' : 'prohibited', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'rows' => [$oncology ? 'required' : 'exclude', 'array', 'min:1', 'max:50'],
            'rows.*' => ['array:presentation_id,dose_mg,diluent_id,dilution_ml,boluses_per_day,infusion_minutes,deliveries'],
            'rows.*.presentation_id' => ['required', 'integer', 'min:1'],
            'rows.*.dose_mg' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'rows.*.diluent_id' => ['nullable', 'integer', 'min:1'],
            'rows.*.dilution_ml' => ['required', 'numeric', 'gt:0', 'max:100000'],
            'rows.*.boluses_per_day' => ['required', 'integer', 'min:1', 'max:99'],
            'rows.*.infusion_minutes' => ['required', 'numeric', 'gt:0', 'max:10080'],
            'rows.*.deliveries' => ['required', 'array', 'min:1', 'max:3'],
            'rows.*.deliveries.*' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:scheduled_date'],
            'administration_route' => [$oncology ? 'exclude' : 'required', Rule::in(['Central', 'Periferica'])],
            'infusion_hours' => [$oncology ? 'exclude' : 'required', 'numeric', 'gt:0', 'max:168'],
            'infusion_rate' => [$oncology ? 'exclude' : 'nullable', 'numeric', 'gt:0', 'max:100000'],
            'overfill_ml' => [$oncology ? 'exclude' : 'nullable', 'numeric', 'min:0', 'max:10000'],
            'npt' => [$oncology ? 'exclude' : 'required', Rule::in(['ADULT', 'INF'])],
            'delivery_at' => [$oncology ? 'exclude' : 'required', 'date_format:Y-m-d\TH:i', 'after_or_equal:scheduled_date'],
            'components' => [$oncology ? 'exclude' : 'required', 'array', 'min:1', 'max:200'],
            'components.*' => ['array:presentation_id,volume_ml'],
            'components.*.presentation_id' => ['required', 'integer', 'min:1', 'distinct'],
            'components.*.volume_ml' => ['required', 'numeric', 'gt:0', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'tipo de mezcla', 'hospital_id' => 'hospital', 'institution_id' => 'institucion',
            'scheduled_date' => 'fecha de programacion', 'patient_name' => 'nombre del paciente',
            'birth_date' => 'fecha de nacimiento', 'weight_kg' => 'peso', 'height_cm' => 'talla',
            'service' => 'servicio', 'diagnosis' => 'diagnostico', 'doctor_name' => 'nombre del medico',
            'doctor_license' => 'cedula profesional', 'attachment' => 'firma y cedula',
            'rows' => 'medicamentos', 'components' => 'componentes', 'delivery_at' => 'fecha de entrega',
        ];
    }
}
