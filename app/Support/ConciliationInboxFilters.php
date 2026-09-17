<?php

namespace App\Support;

use App\Models\Hospital;
use Illuminate\Support\Facades\Validator;

class ConciliationInboxFilters
{
    public static function validate(array $input): array
    {
        return Validator::make($input, [
            'search' => ['nullable', 'string', 'max:150'],
            'institucion_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'hospital_id' => ['nullable', 'integer', 'exists:hospitals,id'],
        ])->validate();
    }

    public static function apply($query, array $filters, string $prefix = '')
    {
        $search = trim($filters['search'] ?? '');
        return $query->when($filters['institucion_id'] ?? null, fn ($q, $id) => $q->whereIn($prefix.'hospital_id', Hospital::query()
            ->whereHas('instituciones', fn ($h) => $h->where('clientes.id', $id))->select('hospitals.id')))
            ->when($filters['hospital_id'] ?? null, fn ($q, $id) => $q->where($prefix.'hospital_id', $id))
            ->when($search !== '', fn ($q) => $q->where(fn ($nested) => $nested
                ->where($prefix.'hospital_name', 'like', '%'.$search.'%')->orWhere($prefix.'sender_name', 'like', '%'.$search.'%')));
    }
}
