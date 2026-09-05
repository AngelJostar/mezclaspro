<?php

namespace App\Livewire\Oncologicos;

use App\Models\Oncologicos\Mezcla;
use App\Support\SolicitudStatusFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitudesTable extends Component
{
    use WithPagination;

    public $sortField = 'id';

    public $sortDirection = 'desc';

    public $requestType = 'oncologicos';

    public string $statusFilter = SolicitudStatusFilter::ALL;

    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'mezcla-inspeccionada' => '$refresh',
    ];

    public function mount(
        string $requestType = 'oncologicos',
        string $statusFilter = SolicitudStatusFilter::ALL
    ): void {
        $this->requestType = $requestType === 'antibioticos' ? 'antibioticos' : 'oncologicos';
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
        $priceListRelation = $this->requestType === 'antibioticos'
            ? 'antibioticMedicineList'
            : 'oncoMedicineList';

        $query = Mezcla::query()
            ->join('solicitud_oncos as oncology_requests', 'oncology_requests.id', '=', 'mezclas.solicitud_id')
            ->select('mezclas.*')
            ->with([
                "solicitud.hospital.{$priceListRelation}.distributor",
                'solicitud.hospital.instituciones',
                'solicitud.user',
            ])
            ->where('oncology_requests.tipo_solicitud', $this->requestType);

        // ✅ Igual que Nutricionales: si es Cliente, limita lo que ve
        // Ajusta este filtro si en tu sistema el Cliente se relaciona distinto.
        if (in_array($role, ['Cliente', 'Institucion'], true)) {
            $query->where('oncology_requests.hospital_id', $user->hospital_id);
        }

        $this->applyStatusFilter($query);

        // ✅ Ordenamientos
        if ($this->sortField === 'hospital_name') {
            // ordenar por hospital->name
            $query->leftJoin('hospitals as h', 'oncology_requests.hospital_id', '=', 'h.id')
                ->orderBy('h.name', $this->sortDirection);
        } elseif ($this->sortField === 'estado') {
            $query->orderByRaw($this->effectiveStatusExpression().' '.$this->sortDirection);
        } elseif ($this->sortField === 'fecha_entrega') {
            $query->orderByRaw(
                'COALESCE(mezclas.fecha_entrega, oncology_requests.fecha_entrega) '.$this->sortDirection
            );
        } else {
            $sortColumns = [
                'id' => 'mezclas.id',
                'request_id' => 'oncology_requests.id',
                'nombre_paciente' => 'oncology_requests.nombre_paciente',
                'created_at' => 'oncology_requests.created_at',
                'remision' => 'mezclas.remision',
                'lote' => 'mezclas.lote',
            ];
            $sortColumn = $sortColumns[$this->sortField] ?? 'mezclas.id';

            $query->orderBy($sortColumn, $this->sortDirection);
        }

        $mezclas = $query->paginate(50);

        $mezclas->getCollection()->each(function (Mezcla $mezcla) use ($priceListRelation): void {
            $solicitud = $mezcla->solicitud;
            $priceList = $solicitud->hospital?->getRelationValue($priceListRelation);

            $solicitud->setAttribute(
                'has_subdistributor_remission',
                (bool) $priceList?->distributor
            );
        });

        return view('livewire.oncologicos.solicitudes-table', compact('mezclas'));
    }

    private function applyStatusFilter(Builder $query): void
    {
        if ($this->statusFilter === SolicitudStatusFilter::PENDING) {
            $query->whereRaw('('.$this->effectiveStatusExpression().') = ?', ['pendiente']);

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::PREPARATION) {
            $query->whereIn(DB::raw($this->effectiveStatusExpression()), SolicitudStatusFilter::PREPARATION_STATES);
            $this->joinDeliverySchedules($query);
            $query->whereNull('request_delivery_schedules.id');

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::IN_ROUTE) {
            $query->whereIn(DB::raw($this->effectiveStatusExpression()), SolicitudStatusFilter::PREPARATION_STATES);
            $this->joinDeliverySchedules($query);
            $query->whereNotNull('request_delivery_schedules.id');

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::DELIVERED) {
            $query->whereIn(DB::raw($this->effectiveStatusExpression()), SolicitudStatusFilter::DELIVERED_STATES);

            return;
        }

        if ($this->statusFilter === SolicitudStatusFilter::HISTORY) {
            $query->whereIn(DB::raw($this->effectiveStatusExpression()), SolicitudStatusFilter::HISTORY_STATES);
        }
    }

    private function joinDeliverySchedules(Builder $query): void
    {
        $query
            ->leftJoin('distribution_delivery_schedules as request_delivery_schedules', function (JoinClause $join) {
                $join->on('request_delivery_schedules.hospital_id', '=', 'oncology_requests.hospital_id')
                    ->where('request_delivery_schedules.status', 'sent')
                    ->whereRaw(
                        'DATE(request_delivery_schedules.scheduled_date) = DATE(COALESCE(mezclas.fecha_entrega, oncology_requests.fecha_entrega))'
                    );
            })
            ->distinct();
    }

    private function effectiveStatusExpression(): string
    {
        return "CASE
            WHEN oncology_requests.estado IN ('cancelada', 'no_aprobada', 'no-aprobada')
                THEN oncology_requests.estado
            ELSE COALESCE(NULLIF(mezclas.estado, ''), 'pendiente')
        END";
    }
}
