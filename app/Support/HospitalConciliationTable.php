<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class HospitalConciliationTable
{
    public const COLUMNS = [
        'type' => ['Tipo'], 'id' => ['ID mezcla'], 'request_id' => ['No. solicitud'],
        'institution' => ['Institución'], 'hospital' => ['Hospital'], 'patient' => ['Paciente'],
        'date' => ['Fecha y hora', 'de solicitud'], 'delivery_date' => ['Fecha y hora', 'programada de entrega'],
        'lot' => ['Lote'], 'view' => ['Ver'], 'approval' => ['Aprobación'],
        'status' => ['Estado de', 'proceso'], 'conciliable' => ['Conciliable'],
    ];

    public static function fields(): array { return array_values(array_diff(array_keys(self::COLUMNS), ['view'])); }

    public static function values(array $row): array
    {
        return array_map(fn ($value) => Str::squish($value), [
            'type' => ['nutricionales' => 'Nutricional', 'oncologicos' => 'Oncologica', 'antibioticos' => 'Antibiotico'][$row['kind']],
            'id' => (string) $row['id'], 'request_id' => (string) $row['request_id'],
            'institution' => $row['institution'], 'hospital' => $row['hospital'], 'patient' => $row['patient'] ?: 'Sin paciente',
            'date' => $row['date']?->format('Y-m-d H:i') ?? '—',
            'delivery_date' => $row['delivery_date'] ? Carbon::parse($row['delivery_date'])->format('Y-m-d H:i') : '—',
            'lot' => $row['lot'] ?: '—', 'approval' => $row['approval'],
            'status' => match ($row['status']) {
                'en_ajuste' => 'En ajuste', 'aprobada' => 'Aprobada', 'dispensada' => 'Dispensada',
                'preparada', 'enproceso' => 'Preparada', 'revisada', 'inspeccionada' => 'Inspeccionada',
                'entregada', 'finalizada' => 'Entregada', 'cancelada' => 'Cancelada', 'no_aprobada' => 'No aprobada',
                default => 'Pendiente',
            },
            'conciliable' => $row['conciliable'] ? 'Sí' : 'No',
        ]);
    }

    public static function prepare(Collection $rows, array $filters, ?array $fields = null, string $defaultSort = 'date'): array
    {
        $rows = $rows->map(fn ($row) => $row + ['cells' => self::values($row)]);
        $options = [];
        foreach ($fields ?? self::fields() as $field) $options[$field] = $rows->pluck('cells.'.$field)->unique()->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
        $selected = array_map(fn ($values) => array_map(fn ($value) => (string) $value, $values), $filters['columnas'] ?? []);
        $rows = $rows->filter(function ($row) use ($selected) {
            foreach ($selected as $field => $values) if (! in_array($row['cells'][$field], $values, true)) return false;
            return true;
        });
        $sort = $filters['orden'] ?? $defaultSort;
        $direction = $filters['direccion'] ?? 'desc';
        $rows = $rows->sort(function ($a, $b) use ($sort, $direction) {
            $left = $a['cells'][$sort]; $right = $b['cells'][$sort];
            $missing = fn ($value) => in_array($value, ['—', 'Sin registrar', 'Sin institución', 'Sin paciente'], true);
            if ($missing($left) !== $missing($right)) return $missing($left) ? 1 : -1;
            $comparison = strnatcasecmp(Str::ascii($left), Str::ascii($right));
            return $direction === 'desc' ? -$comparison : $comparison;
        })->values();
        return compact('rows', 'options', 'selected', 'sort', 'direction');
    }
}
