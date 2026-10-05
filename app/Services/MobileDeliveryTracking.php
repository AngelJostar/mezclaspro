<?php

namespace App\Services;

use App\Models\DistributionDeliverySchedule;
use App\Models\DistributionRouteRun;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;

class MobileDeliveryTracking
{
    public function data(DistributionDeliverySchedule $schedule, bool $detail = false): array
    {
        $schedule->loadMissing(['hospital', 'route', 'confirmation.messenger.personnelProfile', 'warehouse']);
        $date = $schedule->scheduled_date->toDateString();
        // A run from a different date must never be presented as this delivery's live location.
        $run = $schedule->confirmation?->run ?? DistributionRouteRun::where('distribution_route_id', $schedule->distribution_route_id)
            ->whereDate('started_at', $date)->latest('started_at')->first();
        $run?->loadMissing(['messenger.personnelProfile']);
        $location = $run?->locations()->latest('recorded_at')->latest('id')->first();
        $state = $schedule->confirmation ? 'delivered' : ($run && !$run->ended_at ? 'in_route' : 'pending');
        $courier = $schedule->confirmation?->messenger ?? $run?->messenger;
        $data = ['id' => $schedule->id, 'folio' => 'ENT-'.str_pad((string) $schedule->id, 6, '0', STR_PAD_LEFT),
            'hospital' => $schedule->hospital?->name, 'hospital_id' => $schedule->hospital_id,
            'city' => $schedule->hospital?->municipality, 'date' => $date,
            'route' => $schedule->route?->name, 'route_code' => $schedule->route?->code,
            'state' => $state, 'status' => match ($state) { 'delivered' => 'Entregada', 'in_route' => 'En ruta', default => 'Programada' },
            'delivered_at' => $schedule->confirmation?->delivered_at?->toIso8601String(),
            'courier' => $courier ? ['name' => trim($courier->name.' '.$courier->lastname), 'phone' => $courier->personnelProfile?->phone] : null];
        if (!$detail) return $data;
        $data['destination'] = $schedule->hospital?->latitude !== null && $schedule->hospital?->longitude !== null
            ? ['latitude' => $schedule->hospital->latitude, 'longitude' => $schedule->hospital->longitude] : null;
        $data['location'] = $location ? ['latitude' => $location->latitude, 'longitude' => $location->longitude,
            'recorded_at' => $location->recorded_at->toIso8601String(),
            'stale' => $location->recorded_at->lt(now()->subMinutes(5)) || (bool) $run?->ended_at || $state === 'delivered'] : null;
        $data['path'] = $run ? $run->locations()->latest('recorded_at')->limit(100)->get()->reverse()->values()
            ->map(fn ($p) => ['latitude' => $p->latitude, 'longitude' => $p->longitude])->all() : [];
        $data['notes'] = $schedule->confirmation?->notes;
        $data['estimated_arrival'] = null;
        $data['timeline'] = [
            ['label' => 'Entrega programada', 'at' => $schedule->created_at?->toIso8601String(), 'complete' => true],
            ['label' => 'Enviada a distribución', 'at' => $schedule->sent_at?->toIso8601String(), 'complete' => (bool) $schedule->sent_at],
            ['label' => 'En ruta', 'at' => $run?->started_at?->toIso8601String(), 'complete' => (bool) $run],
            ['label' => 'En hospital', 'at' => $schedule->confirmation?->delivered_at?->toIso8601String(), 'complete' => (bool) $schedule->confirmation],
            ['label' => 'Entregada', 'at' => $schedule->confirmation?->delivered_at?->toIso8601String(), 'complete' => (bool) $schedule->confirmation],
        ];
        $data['mixtures'] = $this->mixtures($schedule);
        $data['mixtures_count'] = count($data['mixtures']);
        return $data;
    }

    public function mixtures(DistributionDeliverySchedule $schedule): array
    {
        $date = $schedule->scheduled_date->toDateString();
        $hospitalId = $schedule->hospital_id;
        $states = ['aprobada', 'dispensada', 'preparada', 'revisada', 'entregada'];
        $nutrition = Solicitud::whereIn('estado', $states)
            ->where(fn ($q) => $q->where('hospital_id', $hospitalId)->orWhere(fn ($q) => $q->whereNull('hospital_id')->whereHas('user', fn ($u) => $u->where('hospital_id', $hospitalId))))
            ->whereHas('solicitud_detail', fn ($q) => $q->whereDate('fecha_hora_entrega', $date))
            ->with('hospital')->get()->filter(fn ($s) => !$schedule->warehouse || (int) $s->hospital?->laboratory_id === (int) $schedule->warehouse->laboratory_id)
            ->map(fn ($s) => ['id' => 'npt-'.$s->id, 'request' => $s->request_folio, 'name' => 'Nutrición parenteral', 'status' => $s->estado]);
        $onco = Mezcla::whereIn('estado', $states)->whereHas('solicitud', fn ($q) => $q->where('hospital_id', $hospitalId)
            ->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos']))
            ->where(fn ($q) => $q->whereDate('fecha_entrega', $date)->orWhere(fn ($q) => $q->whereNull('fecha_entrega')->whereHas('solicitud', fn ($s) => $s->whereDate('fecha_entrega', $date))))
            ->with(['solicitud.hospital', 'medicamentos'])->get()->filter(fn ($m) => !$schedule->warehouse || (int) $m->solicitud?->hospital?->laboratory_id === (int) $schedule->warehouse->laboratory_id)
            ->map(fn ($m) => ['id' => 'mz-'.$m->id, 'request' => $m->solicitud?->request_folio,
                'name' => $m->medicamentos->pluck('nombre_medicamento')->filter()->join(', ') ?: 'Mezcla', 'status' => $m->estado,
                'medicines' => $m->medicamentos->map(fn ($d) => ['name' => $d->nombre_medicamento, 'dose' => $d->dosis])->values()->all()]);
        return $nutrition->concat($onco)->values()->all();
    }
}
