<?php

namespace App\Http\Requests\Api\Internal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExternalMixtureDocument extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->tokenCan('requests:documents') === true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['authorization', 'clinical_support'])],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    protected function failedAuthorization(): void
    {
        abort(403, 'El token no tiene permiso para adjuntar documentos.');
    }
}
