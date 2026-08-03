<?php

namespace App\Exports\Oncologicos;

use App\Models\Oncologicos\MedicineList;
use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MedicineListExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private int $medicineListId) {}

    private ?MedicineList $lista = null;

    public function collection(): Collection
    {
        $this->lista = MedicineList::with([
            'hospital:id,name',      // ✅
            'distributor',
            'presentations' => function ($q) {
                $q->with('catalog:id,denominacion');
            },
        ])->findOrFail($this->medicineListId);

        return $this->lista->presentations;
    }

    public function headings(): array
    {
        return [
            'Lista ID',
            'Lista nombre',
            'Tipo cobro default',
            'Marcas activas',
            'Hospitales asignados (por usuarios)',

            'Distribuidor nombre',
            'Distribuidor dirección',

            'Medicamento (genérico)',
            'Medicamento (comercial)',
            'Presentación',
            'Marca — Presentación',
            'Cobro (presentación/lista)',
            'Precio frasco',
            'Precio mg (override)',
        ];
    }

    public function map($presentation): array
    {
        $lista = $this->lista;

        // ✅ Hospital directo (ya no por usuarios)
        $hospitales = $lista->hospital?->name ?? '—';

        $dist = $lista->distributor;
        $distNombre    = $dist->nombre ?? ($dist->name ?? '');
        $distDireccion = $dist->direccion ?? ($dist->address ?? '');

        $medGenerico = $presentation->catalog->denominacion ?? '—';
        $marca = trim((string) ($presentation->marca ?? ''));
        $presTxt = trim((string) ($presentation->presentacion ?? $presentation->name ?? ''));

        $marcaPres = ($marca !== '' && $presTxt !== '')
            ? "{$marca} — {$presTxt}"
            : ($presTxt !== '' ? $presTxt : '—');

        $cobro = $presentation->pivot->charge_by ?? $lista->charge_by ?? '—';
        $precioFrasco = (float) ($presentation->pivot->precio ?? 0);
        $precioMg     = (float) ($presentation->pivot->precio_mg_override ?? 0);

        return [
            $lista->id,
            $lista->name,
            $lista->charge_by,
            $lista->active_brands ? 'Sí' : 'No',
            $hospitales, // ✅

            $distNombre ?: '—',
            $distDireccion ?: '—',

            $medGenerico,
            $marca !== '' ? $marca : '—',
            $presTxt !== '' ? $presTxt : '—',
            $marcaPres,

            $cobro ?: '—',
            $precioFrasco,
            $precioMg,
        ];
    }
}
