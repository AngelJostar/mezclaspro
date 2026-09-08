<?php

namespace App\Livewire\Oncologicos;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Oncologicos\Diluent; // Ajusta namespace si aplica

class DiluentsTable extends Component
{
    use WithPagination;

    public $buscar = '';
    public $search = '';

    public $sortField = 'denominacion_generica';
    public $sortDirection = 'asc';

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
        $query = Diluent::query();

        if ($this->search !== '') {
            $s = $this->search;

            $query->where(function ($q) use ($s) {
                $q->where('denominacion_generica', 'like', "%{$s}%");
            });
        }

        $diluents = $query
            ->with(['catalogPresentations' => fn ($presentations) => $presentations->orderBy('presentation')])
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(15);

        return view('livewire.oncologicos.diluents-table', compact('diluents'));
    }
}
