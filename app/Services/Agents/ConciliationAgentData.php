<?php

namespace App\Services\Agents;

class ConciliationAgentData
{
    public static function project(array $record): array
    {
        $rows = json_decode($record['snapshot'], true, flags: JSON_THROW_ON_ERROR);
        unset($record['snapshot']);
        $record += ['mixtures' => count($rows), 'yes' => 0, 'no' => 0, 'missing_amounts' => 0, 'missing_reasons' => 0, 'missing_fields' => 0];
        foreach ($rows as $row) {
            $yes = $row['conciliable'] ?? (($row['cells']['conciliable'] ?? '') !== 'No');
            $record[$yes ? 'yes' : 'no']++;
            if (!isset($row['amount_cents'])) $record['missing_amounts']++;
            if (!$yes && trim($row['reason'] ?? '') === '') $record['missing_reasons']++;
            foreach (['id', 'request_id', 'institution', 'hospital', 'patient', 'date', 'delivery_date', 'approval', 'status'] as $field) {
                if (in_array(trim((string) ($row['cells'][$field] ?? '')), ['', '—', 'Sin registrar'], true)) { $record['missing_fields']++; break; }
            }
        }
        return $record;
    }

    public static function draft(array $rows): string
    {
        $records = collect($rows);
        return sprintf('Se revisaron %d conciliaciones recibidas, con %d registros de mezcla: %d conciliables y %d no conciliables. Hay %d registros sin importe, %d sin datos de identificación o programación y %d no conciliables sin motivo. No se modificaron las solicitudes.',
            $records->count(), $records->sum('mixtures'), $records->sum('yes'), $records->sum('no'), $records->sum('missing_amounts'), $records->sum('missing_fields'), $records->sum('missing_reasons'));
    }
}
