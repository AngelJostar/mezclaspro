<?php

namespace App\Livewire\Oncologicos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Oncologicos\MedicineList; // Ajusta namespace si es distinto

class MedicineListsTable extends Component
{
    use WithPagination;

    public $buscar = '';
    public $search = '';

    public $sortField = 'id';
    public $sortDirection = 'desc';

    protected $paginationTheme = 'tailwind';

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
        $query = MedicineList::query()
            ->forCategory('oncologicos')
            ->with(['hospital', 'presentations']); // presentations para count()

        if ($this->search !== '') {
            $s = $this->search;
            $isNumeric = ctype_digit($s);

            $query->where(function ($q) use ($s, $isNumeric) {
                if ($isNumeric) {
                    $q->orWhere('medicine_lists.id', $s);
                }

                $q->orWhere('medicine_lists.name', 'like', "%{$s}%")
                  ->orWhere('medicine_lists.description', 'like', "%{$s}%")
                  ->orWhere('medicine_lists.charge_by', 'like', "%{$s}%")
                  ->orWhereHas('hospital', function ($h) use ($s) {
                      $h->where('name', 'like', "%{$s}%");
                  });
            });
        }

        // ✅ Sort especial por hospital
        if ($this->sortField === 'hospital_name') {
            $query->leftJoin('hospitals as h', 'h.onco_medicine_list_id', '=', 'medicine_lists.id')
                ->select('medicine_lists.*')
                ->orderBy('h.name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $listas = $query->paginate(25);

        return view('livewire.oncologicos.medicine-lists-table', compact('listas'));
    }
}
