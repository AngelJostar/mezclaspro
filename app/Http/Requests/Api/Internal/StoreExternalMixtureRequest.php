<?php

namespace App\Http\Requests\Api\Internal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExternalMixtureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('requests:create') === true;
    }

    public function rules(): array
    {
        return [
            'local_external_id' => ['required', 'uuid'],
            'medical_unit_code' => ['required', 'string', 'max:100'],
            'catalog_type' => ['required', Rule::in(['npt', 'oncology'])],
            'catalog_version' => ['nullable', 'string', 'max:100'],
            'patient' => ['required', 'array'],
            'patient.external_id' => ['required', 'string', 'max:100'],
            'patient.name' => ['required', 'string', 'max:255'],
            'clinical' => ['required', 'array'],
            'documents' => ['sometimes', 'array', 'max:10'],
            'documents.*.type' => ['required', 'string', 'max:50'],
            'documents.*.name' => ['required', 'string', 'max:255'],
            'documents.*.mime_type' => ['nullable', 'string', 'max:100'],
            'documents.*.size' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_code' => ['required', 'string', 'max:100'],
            'items.*.presentation_code' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', Rule::in(['ml', 'mg', 'unit'])],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'El token no tiene permiso para crear solicitudes.');
    }
}
