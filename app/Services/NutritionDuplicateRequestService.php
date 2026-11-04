<?php

namespace App\Services;

use App\Models\Nutricionales\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Str;

class NutritionDuplicateRequestService
{
    public function findForCreation(
        int $hospitalId,
        array $patient,
        string $deliveryAt,
        bool $confirmed = false
    ): ?Solicitud
    {
        if ($confirmed) {
            return null;
        }

        return $this->find($hospitalId, $patient, $deliveryAt);
    }

    public function findApprovedForApproval(
        int $hospitalId,
        array $patient,
        string $deliveryAt,
        int $excludedSolicitudId,
        bool $confirmed = false
    ): ?Solicitud {
        if ($confirmed) {
            return null;
        }

        return $this->find($hospitalId, $patient, $deliveryAt, $excludedSolicitudId, true);
    }

    public function confirmationData(Solicitud $solicitud): array
    {
        $solicitud->loadMissing(['solicitud_patient', 'solicitud_detail']);
        $patient = $solicitud->solicitud_patient;
        $detail = $solicitud->solicitud_detail;
        $status = $this->statusLabel((string) $solicitud->estado);
        $approved = in_array($this->normalize((string) $solicitud->estado), [
            'aprobada', 'preparada', 'revisada', 'entregada',
        ], true);

        return [
            'folio' => (string) $solicitud->id,
            'patient' => trim(implode(' ', array_filter([
                $patient?->nombre_paciente,
                $patient?->apellidos_paciente,
            ]))),
            'delivery_at' => $detail?->fecha_hora_entrega
                ? Carbon::parse($detail->fecha_hora_entrega)->format('d/m/Y H:i')
                : 'Sin fecha',
            'status' => $status,
            'lot' => $approved && filled($solicitud->lote) ? (string) $solicitud->lote : null,
        ];
    }

    private function find(
        int $hospitalId,
        array $patient,
        string $deliveryAt,
        ?int $excludedSolicitudId = null,
        bool $approvedOnly = false
    ): ?Solicitud {
        $deliveryDate = Carbon::parse($deliveryAt)->toDateString();

        $query = Solicitud::query()
            ->with(['solicitud_patient', 'solicitud_detail'])
            ->where(function ($hospitalQuery) use ($hospitalId) {
                $hospitalQuery->where('hospital_id', $hospitalId)
                    ->orWhere(function ($legacyQuery) use ($hospitalId) {
                        $legacyQuery->whereNull('hospital_id')
                            ->whereHas('user', fn ($userQuery) => $userQuery->where('hospital_id', $hospitalId));
                    });
            })
            ->whereHas('solicitud_detail', fn ($detailQuery) =>
                $detailQuery->whereDate('fecha_hora_entrega', $deliveryDate)
            );

        if ($excludedSolicitudId !== null) {
            $query->where($query->getModel()->getQualifiedKeyName(), '!=', $excludedSolicitudId);
        }

        if ($approvedOnly) {
            $query->whereIn('estado', ['aprobada', 'preparada', 'revisada', 'entregada']);
        }

        return $query->orderBy('id')->get()->first(fn (Solicitud $candidate) =>
            $this->samePatient($patient, $candidate->solicitud_patient)
        );
    }

    private function samePatient(array $incoming, mixed $candidate): bool
    {
        if (!$candidate) {
            return false;
        }

        $register = $this->normalize((string) ($incoming['registro'] ?? ''));

        if ($register !== '') {
            return $register === $this->normalize((string) $candidate->registro);
        }

        return $this->normalize((string) ($incoming['nombre_paciente'] ?? '')) === $this->normalize((string) $candidate->nombre_paciente)
            && $this->normalize((string) ($incoming['apellidos_paciente'] ?? '')) === $this->normalize((string) $candidate->apellidos_paciente)
            && $this->date((string) ($incoming['fecha_nacimiento'] ?? '')) === $this->date((string) $candidate->fecha_nacimiento);
    }

    private function normalize(string $value): string
    {
        return (string) Str::of($value)->squish()->lower()->ascii();
    }

    private function date(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return $value;
        }
    }

    private function statusLabel(string $status): string
    {
        return match ($this->normalize($status)) {
            'no_aprobada', 'no-aprobada' => 'No aprobada',
            'aprobada' => 'Aprobada',
            'preparada' => 'Preparada',
            'revisada' => 'Revisada',
            'entregada' => 'Entregada',
            'cancelada' => 'Cancelada',
            default => 'Pendiente',
        };
    }
}
