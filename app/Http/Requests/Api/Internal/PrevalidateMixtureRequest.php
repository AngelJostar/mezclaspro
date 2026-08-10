<?php

namespace App\Http\Requests\Api\Internal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrevalidateMixtureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('requests:prevalidate') === true;
    }

    public function rules(): array
    {
        return [
            'medical_unit_code' => ['required', 'string', 'max:100'],
            'catalog_type' => ['required', Rule::in(['npt', 'oncology'])],
            'catalog_version' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_code' => ['required', 'string', 'max:100'],
            'items.*.presentation_code' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['required', Rule::in(['ml', 'mg', 'unit'])],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'El token no tiene permiso para prevalidar solicitudes.');
    }
}
