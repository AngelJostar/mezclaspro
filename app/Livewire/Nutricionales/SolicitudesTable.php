<?php

namespace App\Livewire\Nutricionales;

use App\Models\Nutricionales\Solicitud as NutricionalesSolicitud;
use App\Support\SolicitudStatusFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitudesTable extends Component
{
    use WithPagination;

    public string $statusFilter = SolicitudStatusFilter::ALL;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'nutricional-inspeccionada' => '$refresh',
    ];

    public function mount(string $statusFilter = SolicitudStatusFilter::ALL): void
    {
        $this->statusFilter = SolicitudStatusFilter::normalize($statusFilter);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $role = $user->roles[0]->name ?? null;

        $query = NutricionalesSolicitud::query()
            ->with([
                'user.hospital.instituciones',
                'solicitud_detail',
                'solicitud_patient',
            ]);

        if (in_array($role, ['Cliente', 'Institucion'], true)) {
            $query->where('solicituds.user_id', $user->id);
        }

        $this->applyStatusFilter($query);

        if ($this->sortField === 'request_id') {
            $query->orderBy('solicituds.id', $this->sortDirection);
        } elseif ($this->sortField === 'solicitud_details.fecha_hora_entrega') {
            $query = $query
                ->leftJoin('solicitud_details as sd', 'solicituds.solicitud_detail_id', '=', 'sd.id')
                ->select('solicituds.*')
                ->orderByRaw('CASE WHEN sd.fecha_hora_entrega IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy('sd.fecha_hora_entrega', $this->sortDirection);
        } elseif ($this->sortField === 'lote') {
            $dir = $this->sortDirection;

            $query->orderByRaw('CASE WHEN solicituds.lote IS NULL THEN 1 ELSE 0 END ASC')
                ->orderByRaw("STR_TO_DATE(SUBSTRING(solicituds.lote, 2, 6), '%d%m%y') {$dir}")
                ->orderByRaw("CAST(SUBSTRING(solicituds.lote, 8, 3) AS UNSIGNED) {$dir}");
        } elseif ($this->sortField === 'estado') {
            $query->orderBy('solicituds.estado', $this->sortDirection)
                ->orderBy('solicituds.created_at', 'desc');
        } else {
            $allowedSorts = [
                'id',
                'user_id',
                'created_at',
                'estado',
                'lote',
                'remision',
                'fecha_hora_preparacion',
                'fecha_hora_limite_uso',
            ];

            if (in_array($this->sortField, $allowedSorts, true)) {
                $query->orderBy("solicituds.{$this->sortField}", $this->sortDirection);
            } else {
                $query->orderBy('solicituds.id', 'desc');
            }
        }

        $solicitudes = $query->paginate(50);

        return view('livewire.nutricionales.solicitudes-table', compact('solicitudes'));
    }

    private function applyStatusFilter(Builder $query): void
    {
        if ($this->statusFilter === SolicitudStatusFilter::PENDING) {
            $query->where(function (Builder $query) {
                $query->whereNull('solicituds.estado')
                    ->orWhere('solicituds.estado', 'pendiente');
            });

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::PREPARATION) {
            $query->whereIn('solicituds.estado', SolicitudStatusFilter::PREPARATION_STATES);
            $this->joinDeliverySchedules($query);
            $query->whereNull('request_delivery_schedules.id');

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::IN_ROUTE) {
            $query->whereIn('solicituds.estado', SolicitudStatusFilter::PREPARATION_STATES);
            $this->joinDeliverySchedules($query);
            $query->whereNotNull('request_delivery_schedules.id');

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::DELIVERED) {
            $query->whereIn('solicituds.estado', SolicitudStatusFilter::DELIVERED_STATES);

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::HISTORY) {
            $query->whereIn('solicituds.estado', SolicitudStatusFilter::HISTORY_STATES);
        }
    }

    private function joinDeliverySchedules(Builder $query): void
    {
        $query
            ->leftJoin('users as request_delivery_users', 'request_delivery_users.id', '=', 'solicituds.user_id')
            ->leftJoin(
                'solicitud_details as request_delivery_details',
                'request_delivery_details.id',
                '=',
                'solicituds.solicitud_detail_id'
            )
            ->leftJoin('distribution_delivery_schedules as request_delivery_schedules', function (JoinClause $join) {
                $join->on('request_delivery_schedules.hospital_id', '=', 'request_delivery_users.hospital_id')
                    ->where('request_delivery_schedules.status', 'sent')
                    ->whereRaw(
                        'DATE(request_delivery_schedules.scheduled_date) = DATE(request_delivery_details.fecha_hora_entrega)'
                    );
            })
            ->select('solicituds.*')
            ->distinct();
    }
}
