<?php

namespace App\Livewire\Nutricionales;

use App\Models\Nutricionales\Solicitud as NutricionalesSolicitud;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class SolicitudesTable extends Component
{
    use WithPagination;

    public $buscar = '';
    public $search = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'nutricional-inspeccionada' => '$refresh',
    ];

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

    public function aplicarBusqueda()
    {
        $this->search = trim($this->buscar);
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $role = $user->roles[0]->name ?? null;

        $query = NutricionalesSolicitud::query()
            ->with([
                'user.hospital',
                'solicitud_detail',
                'solicitud_patient',
            ]);

        if (in_array($role, ['Cliente', 'Institucion'], true)) {
            $query->where('solicituds.user_id', $user->id);
        }

        if ($this->search !== '') {
            $query->where(function ($query) {
                $query->where('solicituds.id', 'like', "%{$this->search}%")
                    ->orWhere('solicituds.estado', 'like', "%{$this->search}%")
                    ->orWhere('solicituds.lote', 'like', "%{$this->search}%")
                    ->orWhere('solicituds.remision', 'like', "%{$this->search}%")
                    ->orWhereDate('solicituds.created_at', $this->search)
                    ->orWhereDate('solicituds.fecha_hora_preparacion', $this->search)
                    ->orWhereDate('solicituds.fecha_hora_limite_uso', $this->search)
                    ->orWhereHas('solicitud_detail', function ($q) {
                        $q->whereDate('fecha_hora_entrega', $this->search);
                    })
                    ->orWhereHas('user.hospital', function ($q) {
                        $q->where('name', 'like', "%{$this->search}%");
                    })
                    ->orWhereHas('solicitud_patient', function ($q) {
                        $q->where('nombre_paciente', 'like', "%{$this->search}%")
                            ->orWhere('apellidos_paciente', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->sortField === 'solicitud_details.fecha_hora_entrega') {
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
}

