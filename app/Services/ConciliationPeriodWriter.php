<?php

namespace App\Services;

use App\Models\{Hospital, ConciliationPeriod, ConciliationPeriodItem, HospitalConciliationSubmission, Institucion, InstitutionBilling, User};
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConciliationPeriodWriter
{
    public function __construct(private ConciliationPeriodData $periods) {}

    /** Receives validated scope and remittance versions; rechecks ownership under locks. */
    public function create(User $actor, array $data): array
    {
        $data['hospital_id'] = !empty($data['hospital_id']) ? (int) $data['hospital_id'] : null;
        $data['institucion_id'] = (int) $data['institucion_id'];
        $data['creation_action'] = $data['creation_action'] ?? 'draft';
        ksort($data['selection']);
        $hashInput = [$actor->id, $data['hospital_id'], $data['institucion_id'], $data['selection'], $data['desde'], $data['hasta']];
        if ($data['creation_action'] === 'send') $hashInput[] = 'send';
        $hash = HospitalConciliationSummary::signature($hashInput);
        return DB::transaction(function () use ($actor, $data, $hash) {
            // Serialize creation for this institution, including retries of a multi-hospital batch.
            Institucion::whereKey($data['institucion_id'])->lockForUpdate()->firstOrFail();
            $hospitalIds = Hospital::whereHas('instituciones', fn ($query) => $query->where('clientes.id', $data['institucion_id']))
                ->when($data['hospital_id'], fn ($query, $id) => $query->whereKey($id))
                ->orderBy('id')->lockForUpdate()->pluck('id');
            foreach ($hospitalIds as $hospitalId) $this->lockGroup(['hospital_id' => $hospitalId]);
            $existing = ConciliationPeriod::where('creation_key', $data['creation_key'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->request_hash, $hash), 409, 'Esta operación corresponde a otra selección. Vuelve a abrir Crear periodo.');
                $batch = $existing->creation_batch_key
                    ? ConciliationPeriod::where('creation_batch_key', $existing->creation_batch_key)->orderBy('id')->get() : collect([$existing]);
                return ['periods' => $batch, 'status' => 200];
            }
            // Re-read and validate every selected remittance under the locks before creating any period.
            $rows = $this->periods->rows($data)->keyBy('key');
            $selected = collect();
            foreach ($data['selection'] as $key => $version) {
                $row = $rows->get($key);
                abort_unless($row && hash_equals(ConciliationPeriodData::candidateVersion($row), $version), 409,
                    'Una remisión cambió o no corresponde a la institución, hospitales y fechas indicados. Actualiza las remisiones y revisa la selección.');
                abort_unless($hospitalIds->contains($row['hospital_id']), 409, 'El hospital de una remisión cambió. Actualiza las remisiones.');
                abort_if(ConciliationPeriodItem::where('kind', $row['kind'])->where('mixture_id', $row['id'])->exists(), 409,
                    'Una remisión seleccionada ya está incluida en otro periodo. Actualiza las remisiones.');
                $selected->push($row);
            }
            abort_if($data['creation_action'] === 'send' && $selected->contains(fn ($row) => $row['amount_cents'] === null), 422,
                'Registra el precio de todas las remisiones antes de enviarlas a los hospitales. Puedes crear el periodo en borrador.');
            $periods = collect();
            $from = $data['desde'];
            $to = $data['hasta'];
            foreach ($selected->groupBy('hospital_id')->sortKeys() as $hospitalId => $items) {
                $period = ConciliationPeriod::create([
                    'creation_key' => $periods->isEmpty() ? $data['creation_key'] : (string) Str::uuid(),
                    'creation_batch_key' => $data['creation_key'], 'request_hash' => $hash,
                    'institution_id' => $data['institucion_id'], 'hospital_id' => $hospitalId,
                    'period_from' => $from, 'period_to' => $to, 'created_by' => $actor->id,
                ]);
                $period->items()->createMany($items->map(fn ($row) => ['kind' => $row['kind'], 'mixture_id' => $row['id'], 'snapshot' => $row])->all());
                $periods->push($period);
            }
            if ($data['creation_action'] === 'send') {
                $groups = $this->periods->groups($data)->whereIn('period_id', $periods->pluck('id'))->keyBy('period_id');
                foreach ($periods as $period) {
                    $group = $groups->get($period->id);
                    abort_unless($group && count($group['rows']) === $period->items()->count()
                        && collect($group['rows'])->every(fn ($row) => empty($row['unavailable'])
                            && $row['hospital_id'] == $period->hospital_id && $row['institution_id'] == $period->institution_id),
                        409, 'Una remisión cambió de hospital o dejó de estar disponible. Actualiza la selección.');
                    $this->submit($actor, $group, (string) Str::uuid(), HospitalConciliationSummary::signature([
                        'create_and_send', $actor->id, $data['creation_key'], $period->id, $group['version'],
                    ]));
                }
            }
            return ['periods' => $periods, 'status' => 201];
        }, 3);
    }

    // Use the same source-row locks as individual billing edits, then read a fresh version.
    public function lockGroup(array $filters): void
    {
        Hospital::whereKey($filters['hospital_id'])->lockForUpdate()->firstOrFail();
        Solicitud::where('hospital_id', $filters['hospital_id'])->orderBy('id')->lockForUpdate()->get(['id']);
        Mezcla::whereHas('solicitud', fn ($q) => $q->where('hospital_id', $filters['hospital_id']))->orderBy('id')->lockForUpdate()->get(['id']);
        InstitutionBilling::where('hospital_id', $filters['hospital_id'])->orderBy('id')->lockForUpdate()->get(['id']);
    }

    public function submit(User $actor, array $group, string $key, string $hash): HospitalConciliationSubmission
    {
        abort_if($group['missing_prices'], 422, 'Registra el precio de todas las remisiones antes de enviar el periodo.');
        return HospitalConciliationSubmission::create([
            'submission_key' => $key, 'request_hash' => $hash, 'direction' => 'sent',
            'hospital_id' => $group['hospital_id'], 'hospital_name' => $group['hospital'],
            'submitted_by' => $actor->id, 'sender_name' => $actor->name,
            'period_from' => $group['from'], 'period_to' => $group['to'],
            'filters' => ['institucion_id' => $group['institution_id'], 'period_content_hash' => $group['version'], 'modalidad' => 'periodo']
                + (isset($group['period_id']) ? ['period_id' => $group['period_id']] : []),
            'mixture_count' => count($group['rows']), 'conciliable_count' => collect($group['rows'])->where('conciliable', true)->count(),
            'snapshot' => ConciliationPeriodData::snapshot($group),
        ]);
    }

}
