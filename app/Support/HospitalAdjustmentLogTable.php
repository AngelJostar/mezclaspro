<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class HospitalAdjustmentLogTable
{
    public const COLUMNS = [
        'type' => ['Tipo'], 'id' => ['ID mezcla'], 'request_id' => ['No. solicitud'],
        'institution' => ['Institución'], 'hospital' => ['Hospital'], 'patient' => ['Paciente'],
        'date' => ['Fecha y hora', 'de solicitud'], 'delivery_date' => ['Fecha y hora', 'programada de entrega'],
        'lot' => ['Lote'], 'view' => ['Ver'], 'messages' => ['Mensajería'], 'approval' => ['Aprobación'],
        'status' => ['Estado de', 'proceso'], 'adjustment_date' => ['Fecha del ajuste'],
        'adjustment_reason' => ['Motivo del ajuste'], 'adjustment_status' => ['Estado del ajuste'], 'history' => ['Historial'],
    ];

    public static function fields(): array
    {
        return array_values(array_diff(array_keys(self::COLUMNS), ['view', 'messages', 'history']));
    }

    public static function values(array $row): array
    {
        return HospitalConciliationTable::values($row) + [
            'adjustment_date' => $row['version']->created_at?->format('Y-m-d H:i') ?? '—',
            'adjustment_reason' => Str::squish($row['version']->description ?? ''),
            'adjustment_status' => $row['version']->log_label,
        ];
    }

    public static function prepare(Collection $rows, array $filters): array
    {
        $rows = $rows->map(fn ($row) => $row + ['cells' => self::values($row)]);
        return HospitalConciliationTable::prepare($rows, $filters, self::fields(), 'adjustment_date');
    }
}
