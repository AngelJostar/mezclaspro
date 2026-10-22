<?php

namespace App\Services;

use App\Models\HospitalConciliationSubmission;

class ClientConciliationReview
{
    public static function version(HospitalConciliationSubmission $submission): string
    {
        return HospitalConciliationSummary::signature([
            $submission->id, $submission->hospital_id, $submission->filters,
            $submission->period_from?->toDateString(), $submission->period_to?->toDateString(), $submission->snapshot,
        ]);
    }

    public static function response(HospitalConciliationSubmission $submission): ?HospitalConciliationSubmission
    {
        return HospitalConciliationSubmission::where('hospital_id', $submission->hospital_id)->where('direction', 'received')
            ->where('filters->source_submission_id', $submission->id)->latest('id')->first();
    }

    public function currentGroup(HospitalConciliationSubmission $submission): ?array
    {
        if (!$submission->period_from || !$submission->period_to || empty($submission->filters['period_content_hash'])
            || empty($submission->filters['institucion_id'])) return null;
        return app(ConciliationPeriodData::class)->groups([
            'institucion_id' => $submission->filters['institucion_id'], 'hospital_id' => $submission->hospital_id,
            'desde' => $submission->period_from->toDateString(), 'hasta' => $submission->period_to->toDateString(),
        ])->first(fn ($group) => ($group['period_id'] ?? null) === ($submission->filters['period_id'] ?? null)
            && hash_equals($group['version'], $submission->filters['period_content_hash']));
    }

    public function presentation(HospitalConciliationSubmission $submission): array
    {
        $response = $submission->direction === 'sent' ? self::response($submission) : $submission;
        $responses = collect($response?->snapshot ?? [])->keyBy(fn ($row) => $row['kind'].'-'.$row['id']);
        $accepted = ($response?->filters['response_type'] ?? null) === 'accepted';
        $canRespond = $submission->direction === 'sent' && !$response && count($submission->snapshot) > 0 && $this->currentGroup($submission) !== null;
        $rows = collect($submission->snapshot)->map(function ($row) use ($responses) {
            $key = $row['kind'].'-'.$row['id'];
            $reply = $responses->get($key);
            $originalYes = $row['conciliable'] ?? (($row['cells']['conciliable'] ?? 'Sí') !== 'No');
            $yes = $reply['conciliable'] ?? $originalYes;
            $changed = $reply ? (bool) ($reply['change_requested'] ?? (
                $yes !== $originalYes || is_bool($reply['requested_conciliable'] ?? null) || trim($reply['reason'] ?? '') !== ''
            )) : false;
            return [
                'key' => $key, 'remision' => $row['remision'] ?? $row['cells']['id'] ?? $row['id'],
                'patient' => ($row['cells']['patient'] ?? '') ?: 'Sin paciente',
                'date' => $row['cells']['delivery_date'] ?? $row['cells']['date'] ?? null,
                'lines' => collect($row['lines'] ?? [])->map(fn ($line) => $line + ['description' => 'Mezcla', 'quantity' => 1, 'unit_cents' => null])->all()
                    ?: [['description' => $row['cells']['type'] ?? 'Mezcla', 'quantity' => 1, 'unit_cents' => $row['amount_cents'] ?? null]],
                'amount_cents' => $row['amount_cents'] ?? null, 'conciliable' => $yes,
                'reason' => $reply['reason'] ?? $row['reason'] ?? '',
                'changed' => $changed, 'status' => $changed ? 'Con Cambios' : 'Sin Cambios',
            ];
        })->all();
        $group = [
            'hospital' => $submission->hospital_name, 'institution' => data_get($submission->snapshot, '0.cells.institution', 'Sin institución'),
            'from' => $submission->period_from?->toDateString(), 'to' => $submission->period_to?->toDateString(),
            'rows' => $rows,
        ] + ConciliationPeriodData::totals($rows);
        $canRespond = $canRespond && !$group['missing_prices'];
        return compact('group', 'canRespond') + [
            'version' => self::version($submission),
            'responseStatus' => $response ? ($accepted ? 'Aceptada' : 'Con cambios') : null,
            'responseBy' => $response?->sender_name, 'responseAt' => $response?->created_at?->format('d/m/Y H:i'),
        ];
    }
}
