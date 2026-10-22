<?php

namespace App\Services;

use App\Http\Controllers\Admin\InstitucionBillingController;
use App\Models\ConciliationPeriod;
use App\Models\ConciliationPeriodDecision;
use App\Models\ConciliationPeriodItem;
use App\Models\HospitalConciliationSubmission;
use App\Models\InstitutionBillingMovement;
use App\Support\ConciliationInboxTable;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConciliationPeriodData
{
    public function __construct(private InstitutionBillingPricingService $pricing) {}

    public function rows(array $filters = []): Collection
    {
        $requests = app(InstitucionBillingController::class)->conciliationRequests($filters['institucion_id'] ?? null, $filters['hospital_id'] ?? null);
        $hospitalIds = $requests['nutrition']->pluck('hospital_id')->merge($requests['onco']->pluck('solicitud.hospital_id'))->unique();
        $submissions = HospitalConciliationSubmission::whereIn('hospital_id', $hospitalIds)->latest('id')->get();
        $latest = ConciliationInboxData::indexSubmissions($submissions);
        $received = ConciliationInboxData::indexSubmissions($submissions->where('direction', 'received'));
        $billingIds = $requests['nutrition']->pluck('billing.id')->merge($requests['onco']->pluck('billing.id'))->filter()->unique();
        $movements = InstitutionBillingMovement::whereIn('institution_billing_id', $billingIds)->latest('id')->get()->groupBy('institution_billing_id');
        $rows = collect();
        foreach (['nutrition', 'onco'] as $type) {
            foreach ($requests[$type] as $record) {
                if (blank($record->remision)) continue;
                $nutrition = $type === 'nutrition';
                $kind = $nutrition ? 'nutricionales' : $record->solicitud?->tipo_solicitud;
                if (!in_array($kind, ['nutricionales', 'oncologicos', 'antibioticos'], true)) continue;
                $hospital = $nutrition ? $record->hospital : $record->solicitud?->hospital;
                $date = $nutrition ? $record->solicitud_detail?->fecha_hora_entrega : ($record->fecha_entrega ?? $record->solicitud?->fecha_entrega);
                if (!$hospital || !$date) continue;
                $date = Carbon::parse($date);
                $day = $date->toDateString();
                if ((!empty($filters['desde']) && $day < $filters['desde']) || (!empty($filters['hasta']) && $day > $filters['hasta'])) continue;
                $billing = $record->billing;
                if ($billing && (int) $billing->hospital_id !== (int) $hospital->id) continue;
                // Never count the same remittance against multiple institutions.
                $institution = $billing?->institucion_id ? $hospital->instituciones->firstWhere('id', $billing->institucion_id)
                    : ($hospital->instituciones->count() === 1 ? $hospital->instituciones->first() : null);
                if (!$institution || (!empty($filters['institucion_id']) && (int) $filters['institucion_id'] !== (int) $institution->id)) continue;
                $price = $nutrition ? $this->pricing->priceNutritionRequest($record) : $this->pricing->priceOncoMix($record);
                $captured = trim((string) $billing?->precio_total);
                $computed = $price['total_iva_included'] ?? null;
                $amount = $captured !== '' ? self::cents($captured)
                    : (is_numeric($computed) && $computed > 0 ? self::cents(number_format((float) $computed, 2, '.', '')) : null);
                $lines = $nutrition ? [] : collect($price['lines'] ?? [])->map(fn ($line) => [
                    'description' => $line['description'], 'quantity' => (float) $line['quantity'],
                    'unit_cents' => self::cents(number_format((float) ($line['quantity'] > 0 ? $line['total_with_vat'] / $line['quantity'] : $line['unit_price']), 2, '.', '')),
                ])->all();
                if (!$lines) $lines = [['description' => $nutrition ? 'Medicamento de nutrición parenteral' : ($price['description'] ?? 'Mezcla '.$kind), 'quantity' => 1, 'unit_cents' => $amount]];
                $patient = $nutrition ? trim(($record->solicitud_patient?->nombre_paciente ?? '').' '.($record->solicitud_patient?->apellidos_paciente ?? '')) : $record->solicitud?->nombre_paciente;
                $previous = $billing?->conciliable;
                $yes = blank($previous) || in_array(Str::lower(Str::ascii(trim($previous))), ['si', 'yes', 'true', '1', 'conciliado', 'conciliable'], true);
                $submission = $received[$hospital->id.':'.$kind.':'.$record->id] ?? null;
                $submitted = collect($submission?->snapshot ?? [])->first(fn ($row) => $row['kind'] === $kind && (int) $row['id'] === (int) $record->id);
                $changes = $yes ? [] : ['Remisión marcada como no conciliable.'];
                $clientRequest = null;
                if ($submitted && ($submitted['change_requested'] ?? (($submitted['conciliable'] ?? true) === false || !empty($submitted['reason'])))) {
                    $requested = $submitted['requested_conciliable'] ?? null;
                    $changes[] = is_bool($requested)
                        ? 'Solicitud del cliente: marcar como conciliable '.($requested ? 'Sí.' : 'No.')
                        : 'Solicitud de cambios del cliente para revisión por PROMESA Prodifem.';
                    $clientRequest = ['submission_id' => $submission->id, 'folio' => $submission->folio(),
                        'by' => $submission->sender_name, 'at' => $submission->created_at->format('d/m/Y H:i'),
                        'conciliable' => $requested];
                }
                $priceChange = ($movements->get($billing?->id) ?? collect())->first(fn ($movement) => ($movement->details['field'] ?? null) === 'precio_total');
                $baselineAmount = $submitted['amount_cents'] ?? ($priceChange ? self::cents($priceChange->details['before'] ?? null) : null);
                if ($baselineAmount !== null && $amount !== $baselineAmount) {
                    $changes[] = 'Importe: '.HospitalConciliationSummary::money($baselineAmount).' → '.HospitalConciliationSummary::money($amount).'.';
                }
                if ($yes && $submitted && ($submitted['conciliable'] ?? true) === false && !is_bool($submitted['requested_conciliable'] ?? null)) $changes[] = 'Conciliable: No → Sí.';
                $from = max($filters['desde'] ?? '0000-01-01', $date->copy()->startOfMonth()->toDateString());
                $to = min($filters['hasta'] ?? '9999-12-31', $date->copy()->endOfMonth()->toDateString());
                $rows->push([
                    'key' => $kind.'-'.$record->id, 'kind' => $kind, 'id' => $record->id,
                    'institution_id' => $institution->id, 'institution' => $institution->nombre,
                    'hospital_id' => $hospital->id, 'hospital' => $hospital->name, 'from' => $from, 'to' => $to,
                    'remision' => $record->remision, 'patient' => $patient ?: 'Sin paciente', 'date' => $date->format('Y-m-d H:i'),
                    'lines' => $lines, 'amount_cents' => $amount, 'conciliable' => $yes, 'previous_conciliable' => $previous,
                    'previous_price' => $billing?->precio_total,
                    'status' => ConciliationInboxTable::status($previous, $latest[$hospital->id.':'.$kind.':'.$record->id] ?? null),
                    'request_id' => $nutrition ? $record->id : $record->solicitud_id, 'lot' => $record->lote ?: '—',
                    'observations' => trim((string) $billing?->observaciones),
                    'reason' => trim((string) ($submitted['reason'] ?? '')),
                    'reason_folio' => !empty($submitted['reason']) ? $submission->folio() : null,
                    'client_request' => $clientRequest,
                    'changes' => $changes,
                ]);
            }
        }
        return $rows;
    }

    public function candidates(): Collection
    {
        $assigned = ConciliationPeriodItem::all()->keyBy(fn ($item) => $item->kind.'-'.$item->mixture_id);
        return $this->rows()->sortByDesc('date')->values()->map(fn ($row) => $row + [
            'version' => self::candidateVersion($row),
            'period_id' => $assigned->get($row['key'])?->conciliation_period_id,
        ]);
    }

    public static function candidateVersion(array $row): string
    {
        return HospitalConciliationSummary::signature(array_diff_key($row, array_flip(['status', 'from', 'to', 'observations', 'reason', 'reason_folio', 'changes', 'client_request'])));
    }

    public function groups(array $filters): Collection
    {
        $allRows = $this->rows(array_diff_key($filters, ['desde' => true, 'hasta' => true]))->keyBy('key');
        $assigned = ConciliationPeriodItem::all()->keyBy(fn ($item) => $item->kind.'-'.$item->mixture_id);
        $rows = $allRows->reject(fn ($row) => $assigned->has($row['key']))
            ->filter(fn ($row) => substr($row['date'], 0, 10) >= $filters['desde'] && substr($row['date'], 0, 10) <= $filters['hasta'])
            ->map(function ($row) use ($filters) {
                $row['from'] = max($row['from'], $filters['desde']);
                $row['to'] = min($row['to'], $filters['hasta']);
                return $row;
            });
        $submissions = HospitalConciliationSubmission::whereIn('hospital_id', $allRows->pluck('hospital_id')->unique())->latest('id')->get();
        $latest = ConciliationInboxData::indexSubmissions($submissions);
        $groups = $rows->groupBy(fn ($row) => implode(':', [$row['institution_id'], $row['hospital_id'], $row['from'], $row['to']]))
            ->map(function ($items, $key) {
                $first = $items->first();
                $group = array_intersect_key($first, array_flip(['institution_id', 'institution', 'hospital_id', 'hospital', 'from', 'to']));
                $group['key'] = hash('sha256', $key);
                $group['rows'] = $items->sortBy('key', SORT_NATURAL)->values()->all();
                return $group;
            })->values();
        $saved = ConciliationPeriod::with('items')
            ->when($filters['institucion_id'] ?? null, fn ($q, $id) => $q->where('institution_id', $id))
            ->when($filters['hospital_id'] ?? null, fn ($q, $id) => $q->where('hospital_id', $id))
            ->whereDate('period_from', '<=', $filters['hasta'])->whereDate('period_to', '>=', $filters['desde'])->get();
        foreach ($saved as $period) {
            $items = $period->items->map(function ($item) use ($allRows, $period) {
                $row = $allRows->get($item->kind.'-'.$item->mixture_id);
                if (!$row || $row['institution_id'] != $period->institution_id || $row['hospital_id'] != $period->hospital_id) {
                    $row = array_replace($item->snapshot, ['amount_cents' => null, 'unavailable' => true]);
                }
                $changes = $row['changes'] ?? [];
                if ($row['amount_cents'] !== ($item->snapshot['amount_cents'] ?? null)) {
                    $changes[] = 'Importe al crear el periodo: '.HospitalConciliationSummary::money($item->snapshot['amount_cents'] ?? null)
                        .'; actual: '.HospitalConciliationSummary::money($row['amount_cents']).'.';
                }
                if ($row['conciliable'] && !($item->snapshot['conciliable'] ?? true)) $changes[] = 'Conciliable: No → Sí.';
                $row['changes'] = array_values(array_unique($changes));
                return array_replace($row, ['from' => $period->period_from->toDateString(), 'to' => $period->period_to->toDateString()]);
            })->sortBy('key', SORT_NATURAL)->values();
            $group = array_intersect_key($items->first(), array_flip(['institution_id', 'institution', 'hospital_id', 'hospital', 'from', 'to']));
            $groups->push($group + ['key' => 'period-'.$period->id, 'period_id' => $period->id, 'rows' => $items->all()]);
        }
        $decisions = ConciliationPeriodDecision::whereIn('group_key', $groups->pluck('key'))->latest('id')->get()->groupBy('group_key');
        return $groups->map(function ($group) use ($submissions, $latest, $decisions) {
            $group += self::totals($group['rows']);
            $group['version'] = self::version($group);
            $sent = $submissions->first(fn ($submission) => $submission->direction === 'sent'
                && ($submission->filters['period_content_hash'] ?? null) === $group['version']);
            $group['sent_folio'] = $sent?->folio();
            $directions = collect($group['rows'])->map(fn ($row) => ($latest[$row['hospital_id'].':'.$row['kind'].':'.$row['id']] ?? null)?->direction);
            $group['bucket'] = $directions->contains(null) ? 'pendientes' : ($directions->contains(fn ($direction) => $direction !== 'sent') ? 'recibidas' : 'enviadas');
            $group['submission_key'] = (string) Str::uuid();
            $group['review_version'] = HospitalConciliationSummary::signature([$group['version'], array_map(fn ($row) => [
                'key' => $row['key'], 'observations' => $row['observations'] ?? '', 'reason' => $row['reason'] ?? '',
                'reason_folio' => $row['reason_folio'] ?? null, 'changes' => $row['changes'] ?? [],
            ] + (!empty($row['client_request']) ? ['client_request' => $row['client_request']] : []), $group['rows'])]);
            $currentDecisions = ($decisions->get($group['key']) ?? collect())->where('version', $group['review_version']);
            $authorization = $currentDecisions->firstWhere('action', 'authorize');
            $group['changes_count'] = collect($group['rows'])->filter(fn ($row) => !empty($row['changes']))->count();
            // The counters show the hospital's requested selection while changes await staff authorization.
            $group['mixture_count'] = count($group['rows']);
            $group['conciliable_count'] = collect($group['rows'])->filter(fn ($row) => $row['client_request']['conciliable'] ?? $row['conciliable'])->count();
            $group['non_conciliable_count'] = $group['mixture_count'] - $group['conciliable_count'];
            $group['has_differences'] = $group['non_conciliable_count'] > 0 || $group['changes_count'] > 0;
            $group['authorization'] = $authorization ? ['by' => $authorization->user_name, 'at' => $authorization->created_at->format('d/m/Y H:i')] : null;
            $group['can_authorize'] = $group['changes_count'] > 0 && !$authorization && !$group['missing_prices'];
            $clientAcceptance = $submissions->first(fn ($submission) => $submission->direction === 'received'
                && ($submission->filters['response_type'] ?? null) === 'accepted'
                && ($submission->filters['period_content_hash'] ?? null) === $group['version']
                && collect($group['rows'])->every(fn ($row) => ($latest[$row['hospital_id'].':'.$row['kind'].':'.$row['id']] ?? null)?->id === $submission->id));
            $group['client_acceptance'] = $clientAcceptance ? ['by' => $clientAcceptance->sender_name,
                'at' => $clientAcceptance->created_at->format('d/m/Y H:i'), 'folio' => $clientAcceptance->folio()] : null;
            if ($clientAcceptance && !$group['changes_count']) {
                $group['rows'] = array_map(fn ($row) => array_replace($row, ['status' => 'Conciliado']), $group['rows']);
            }
            $reviewed = $currentDecisions->contains('action', 'review') || collect($group['rows'])->every(fn ($row) => $row['status'] === 'Conciliado');
            $group['conciliation_status'] = $authorization ? 'Aceptada' : ($group['changes_count'] ? 'Con cambios' : (($reviewed || $clientAcceptance) ? 'Aceptada' : 'Pendiente'));
            return $group;
        })->sortBy(fn ($group) => $group['from'].' '.$group['institution'].' '.$group['hospital'])->values();
    }

    public static function cents(?string $value): ?int
    {
        $value = trim($value ?? '');
        if (!preg_match('/^\$?\s*(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d{1,2})?$/D', $value)) return null;
        return HospitalInvoiceLedger::cents(str_replace(['$', ',', ' '], '', $value));
    }

    public static function totals(array $rows): array
    {
        $sum = fn ($items) => $items->contains(fn ($row) => $row['amount_cents'] === null) ? null : $items->sum('amount_cents');
        $items = collect($rows);
        return ['total_cents' => $sum($items), 'new_cents' => $sum($items->where('conciliable', true)),
            'excluded_cents' => $sum($items->where('conciliable', false)), 'missing_prices' => $items->whereNull('amount_cents')->count()];
    }

    public static function version(array $group): string
    {
        return HospitalConciliationSummary::signature([
            $group['institution_id'], $group['hospital_id'], $group['from'], $group['to'],
            // Keep the delivery content hash stable when only review metadata is added.
            array_map(fn ($row) => array_diff_key($row, array_flip(['status', 'observations', 'reason', 'reason_folio', 'changes', 'client_request'])), $group['rows']),
        ]);
    }

    public static function snapshot(array $group): array
    {
        return array_map(fn ($row) => [
            'kind' => $row['kind'], 'id' => $row['id'], 'amount_cents' => $row['amount_cents'], 'conciliable' => $row['conciliable'],
            'remision' => $row['remision'], 'lines' => $row['lines'], 'reason' => $row['reason'] ?? null,
            'cells' => ['type' => match ($row['kind']) { 'nutricionales' => 'Nutricional', 'antibioticos' => 'Antibiótico', default => 'Oncológica' },
                'id' => (string) $row['id'], 'request_id' => (string) $row['request_id'], 'institution' => $row['institution'], 'hospital' => $row['hospital'],
                'patient' => $row['patient'], 'date' => $row['date'], 'delivery_date' => $row['date'], 'lot' => $row['lot'],
                'approval' => '—', 'status' => $row['status'], 'conciliable' => $row['conciliable'] ? 'Sí' : 'No'],
        ], $group['rows']);
    }
}
