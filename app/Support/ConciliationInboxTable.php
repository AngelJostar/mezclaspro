<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\HospitalConciliationSubmission;

final class ConciliationInboxTable
{
    public const TABS = ['todas' => 'Todas', 'recibidas' => 'Recibidas', 'enviadas' => 'Enviadas', 'pendientes' => 'Pendientes'];

    public const COLUMNS = [
        'type' => 'Tipo',
        'patient' => 'Paciente',
        'date' => 'Fecha y hora de solicitud',
        'view' => 'Ver',
        'remision' => 'No. de remisión',
        'price' => 'Precio de venta total editable',
        'conciliable' => 'Conciliable',
        'conciliation_status' => 'Estatus de conciliación',
    ];

    public static function prepare(Collection $requests, array $filters): array
    {
        $rows = $requests->map(function ($row) {
                $mixture = $row['mixture'];
                $cells = array_map(fn ($value) => Str::squish($value) === '' ? '—' : Str::squish($value), [
                    'type' => match ($mixture['kind'] ?? null) {
                        'nutricionales' => 'Nutricional', 'oncologicos' => 'Oncologica',
                        'antibioticos' => 'Antibiotico', default => '—',
                    },
                    'patient' => $mixture['cells']['patient'] ?? '—',
                    'date' => $mixture['cells']['date'] ?? '—',
                    'view' => !empty($mixture['url']) ? 'Ver' : '—',
                    'remision' => $mixture['remision'] ?? '—',
                    'price' => $mixture['current_price'] ?? '—',
                    'conciliable' => !empty($mixture['url']) ? ($mixture['current_conciliable'] ? 'Sí' : 'No') : '—',
                    'conciliation_status' => $mixture['conciliation_status'],
                ]);

                return $row + ['cells' => $cells];
        });

        // Options include every matching report, even when its rows are on another page.
        $options = [];
        foreach (array_keys(self::COLUMNS) as $field) {
            $options[$field] = $rows->pluck('cells.'.$field)->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        }
        $selected = array_map(fn ($values) => array_map(fn ($value) => (string) $value, $values), $filters['columnas'] ?? []);
        $rows = $rows->filter(function ($row) use ($selected) {
            foreach ($selected as $field => $values) {
                if (!in_array($row['cells'][$field], $values, true)) return false;
            }
            return true;
        });
        $sort = $filters['orden'] ?? 'date';
        $direction = $filters['direccion'] ?? 'desc';
        $rows = $rows->sort(function ($left, $right) use ($sort, $direction) {
            $a = $left['cells'][$sort];
            $b = $right['cells'][$sort];
            if ($sort === 'price') {
                $a = str_replace(['$', ',', ' '], '', $a);
                $b = str_replace(['$', ',', ' '], '', $b);
                if (is_numeric($a) !== is_numeric($b)) return is_numeric($a) ? -1 : 1;
            }
            $comparison = $sort === 'price' && is_numeric($a) && is_numeric($b)
                ? (float) $a <=> (float) $b
                : strnatcasecmp(Str::ascii($a), Str::ascii($b));
            return $direction === 'desc' ? -$comparison : $comparison;
        })->values();

        return compact('rows', 'options', 'selected', 'sort', 'direction');
    }

    public static function status(?string $recordedValue, ?HospitalConciliationSubmission $submission = null): string
    {
        // Eligibility (Sí/No) does not confirm a completed reconciliation.
        if (Str::lower(Str::ascii(trim($recordedValue ?? ''))) === 'conciliado') return 'Conciliado';
        return $submission ? ($submission->direction === 'sent' ? 'Enviada' : 'Recibida') : 'Pendiente';
    }
}
