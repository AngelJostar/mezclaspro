<?php

namespace App\Livewire\Oncologicos;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use App\Models\Oncologicos\SolicitudOnco;

class SolicitudesTable extends Component
{
    use WithPagination;

    public $buscar = '';   // input
    public $search = '';   // aplicado

    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $requestType = 'oncologicos';

    protected $paginationTheme = 'tailwind';

    public function mount(string $requestType = 'oncologicos'): void
    {
        $this->requestType = $requestType === 'antibioticos' ? 'antibioticos' : 'oncologicos';
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

    public function aplicarBusqueda()
    {
        $this->search = trim($this->buscar);
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();
        $role = $user->roles[0]->name ?? null;
        $priceListRelation = $this->requestType === 'antibioticos'
            ? 'antibioticMedicineList'
            : 'oncoMedicineList';

        $query = SolicitudOnco::query()
            ->with([
                "hospital.{$priceListRelation}.distributor",
                'user',
                'mezclas:id,solicitud_id,lote',
            ])
            ->where('tipo_solicitud', $this->requestType);

        // ✅ Igual que Nutricionales: si es Cliente, limita lo que ve
        // Ajusta este filtro si en tu sistema el Cliente se relaciona distinto.
        if (in_array($role, ['Cliente', 'Institucion'], true)) {
            $query->where('hospital_id', $user->hospital_id);
        }

        // ✅ Búsqueda global
        if ($this->search !== '') {
            $s = $this->search;

            $query->where(function ($q) use ($s) {
                $q->where('solicitud_oncos.id', 'like', "%{$s}%")
                    ->orWhere('solicitud_oncos.nombre_paciente', 'like', "%{$s}%")
                    ->orWhere('solicitud_oncos.estado', 'like', "%{$s}%")
                    ->orWhere('solicitud_oncos.remision', 'like', "%{$s}%")
                    ->orWhereDate('solicitud_oncos.created_at', $s)
                    ->orWhereDate('solicitud_oncos.created_at', $s)
                    ->orWhereHas('hospital', function ($h) use ($s) {
                        $h->where('name', 'like', "%{$s}%");
                    })
                    ->orWhereHas('mezclas', function ($m) use ($s) {
                        $m->where('lote', 'like', "%{$s}%");
                    });
            });
        }

        // ✅ Ordenamientos
        if ($this->sortField === 'hospital_name') {
            // ordenar por hospital->name
            $query->leftJoin('hospitals as h', 'solicitud_oncos.hospital_id', '=', 'h.id')
                ->select('solicitud_oncos.*')
                ->orderBy('h.name', $this->sortDirection);
        } elseif ($this->sortField === 'entrega_dt') {
            // ordenar por "fecha_solicitud + horario_entrega"
            // (si horario_entrega es NULL, lo manda al final)
            $dir = $this->sortDirection;

            $query->orderByRaw("CASE WHEN solicitud_oncos.horario_entrega IS NULL THEN 1 ELSE 0 END ASC")
                ->orderByRaw("STR_TO_DATE(CONCAT(solicitud_oncos.fecha_solicitud,' ',solicitud_oncos.horario_entrega), '%Y-%m-%d %H:%i') {$dir}");
        } else {
            // campos directos
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $solicitudes = $query->paginate(50);

        $solicitudes->getCollection()->each(function (SolicitudOnco $solicitud) use ($priceListRelation): void {
            $priceList = $solicitud->hospital?->getRelationValue($priceListRelation);

            $solicitud->setAttribute(
                'has_subdistributor_remission',
                (bool) $priceList?->distributor
            );
        });

        return view('livewire.oncologicos.solicitudes-table', compact('solicitudes'));
    }
}
