<?php

namespace App\Livewire\Oncologicos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Oncologicos\MedicinesCatalog;

class MedicinesCatalogTable extends Component
{
    use WithPagination;

    public $buscar = '';
    public $search = '';

    public $sortField = 'denominacion';
    public $sortDirection = 'asc';

    protected $paginationTheme = 'tailwind';
    protected $allowedSorts = ['id', 'denominacion'];

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = in_array($field, $this->allowedSorts, true) ? $field : 'denominacion';
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
        $query = MedicinesCatalog::query()
            ->forCategory('oncologicos')
            ->with([
                'presentations' => function ($q) {
                    $q->orderBy('marca')
                        ->orderBy('presentacion');
                }
            ])
            ->where('state', true);

        if ($this->search !== '') {
            $s = $this->search;
            $isNumeric = ctype_digit($s);

            $query->where(function ($q) use ($s, $isNumeric) {
                if ($isNumeric) {
                    $q->orWhere('id', $s);
                }

                $q->orWhere('denominacion', 'like', "%{$s}%")
                    ->orWhereHas('presentations', function ($p) use ($s) {
                        $p->where('marca', 'like', "%{$s}%")
                            ->orWhere('presentacion', 'like', "%{$s}%");
                    });
            });
        }

        $medicamentos = $query
            ->orderBy(
                in_array($this->sortField, $this->allowedSorts, true) ? $this->sortField : 'denominacion',
                $this->sortDirection === 'desc' ? 'desc' : 'asc'
            )
            ->paginate(50);

        return view('livewire.oncologicos.medicines-catalog-table', compact('medicamentos'));
    }
}
