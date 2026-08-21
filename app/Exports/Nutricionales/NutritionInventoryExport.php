<?php

namespace App\Exports\Nutricionales;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class NutritionInventoryExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected int $laboratoryId;
    protected int $warehouseId;
    protected string $q;
    protected string $stock;

    public function __construct(int $laboratoryId, int $warehouseId, string $q = '', string $stock = '')
    {
        $this->laboratoryId = $laboratoryId;
        $this->warehouseId = $warehouseId;
        $this->q = trim($q);
        $this->stock = $stock;
    }

    public function array(): array
    {
        $rows = [];

        $items = DB::table('nutrition_medicines_catalog as nmc')
            ->join('nutrition_medicine_presentations as nmp', 'nmp.nutrition_medicine_catalog_id', '=', 'nmc.id')
            ->leftJoin('inputs as i', 'i.id', '=', 'nmc.input_id')
            ->leftJoin('categories as c', 'c.id', '=', 'nmc.category_id')
            ->leftJoin('medicine_laboratory_stocks as mls', function ($join) {
                $join->on('mls.nutrition_medicine_presentation_id', '=', 'nmp.id')
                    ->where('mls.laboratory_id', '=', $this->laboratoryId)
                    ->where('mls.warehouse_id', '=', $this->warehouseId);
            })
            ->leftJoin('laboratories as l', 'l.id', '=', DB::raw($this->laboratoryId))
            ->leftJoin('warehouses as w', 'w.id', '=', DB::raw($this->warehouseId))
            ->when($this->q !== '', function ($query) {
                $query->where(function ($w) {
                    $w->where('nmc.denominacion_generica', 'like', "%{$this->q}%")
                        ->orWhere('nmp.denominacion_comercial', 'like', "%{$this->q}%")
                        ->orWhere('nmp.presentacion', 'like', "%{$this->q}%")
                        ->orWhere('nmp.fabricante', 'like', "%{$this->q}%")
                        ->orWhere('i.description', 'like', "%{$this->q}%")
                        ->orWhere('mls.lote', 'like', "%{$this->q}%");
                });
            })
            ->when($this->stock !== '', function ($query) {
                if ($this->stock === '1') {
                    $query->whereNotNull('mls.id')
                        ->where('mls.stock_ml_actual', '>', 0);
                } elseif ($this->stock === '0') {
                    $query->where(function ($w) {
                        $w->whereNull('mls.id')
                            ->orWhere('mls.stock_ml_actual', '<=', 0);
                    });
                }
            })
            ->select([
                'l.nombre as laboratorio',
                'l.estado as laboratorio_estado',
                'w.name as almacen',

                'nmc.id as catalog_id',
                'nmc.denominacion_generica',
                'nmc.osmolaridad',
                'nmc.is_active as catalog_active',
                'i.description as input',
                'c.name as categoria',

                'nmp.id as presentation_id',
                'nmp.denominacion_comercial',
                'nmp.fabricante',
                'nmp.presentacion',
                'nmp.presentacion_ml',
                'nmp.is_available',

                'mls.id as stock_id',
                'mls.lote',
                'mls.caducidad',
                'mls.fecha_ingreso',
                'mls.numero_factura',
                'mls.frascos_iniciales',
                'mls.frascos_actuales',
                'mls.stock_ml_inicial',
                'mls.stock_ml_actual',
                'mls.is_active as stock_active',
            ])
            ->where('nmc.is_active', 1)
            ->where('nmp.is_available', 1)
            ->orderBy('nmc.denominacion_generica')
            ->orderBy('nmp.denominacion_comercial')
            ->orderByRaw('CASE WHEN mls.caducidad IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mls.caducidad')
            ->orderBy('mls.lote')
            ->get();

        foreach ($items as $item) {
            $rows[] = [
                $item->laboratorio,
                $item->laboratorio_estado,
                $item->almacen,

                $item->catalog_id,
                $item->denominacion_generica,
                $item->input,
                $item->categoria,
                $item->osmolaridad,
                $item->catalog_active ? 'Activo' : 'Inactivo',

                $item->presentation_id,
                $item->denominacion_comercial,
                $item->fabricante,
                $item->presentacion,
                $item->presentacion_ml,
                $item->is_available ? 'Disponible' : 'No disponible',

                $item->stock_id,
                $item->lote,
                $item->caducidad,
                $item->fecha_ingreso,
                $item->numero_factura,
                $item->frascos_iniciales ?? 0,
                $item->frascos_actuales ?? 0,
                $item->stock_ml_inicial ?? 0,
                $item->stock_ml_actual ?? 0,
                ($item->stock_id && $item->stock_active) ? 'Activo' : 'Inactivo / Sin lote',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Laboratorio',
            'Estado laboratorio',
            'Almacén',

            'ID catalogo',
            'Denominacion generica',
            'Input',
            'Categoria',
            'Osmolaridad',
            'Estado medicamento',

            'ID presentacion',
            'Denominacion comercial',
            'Fabricante',
            'Presentacion',
            'ML por presentacion',
            'Estado presentacion',

            'ID lote',
            'Lote',
            'Caducidad',
            'Fecha ingreso',
            'Numero factura',
            'Frascos iniciales',
            'Frascos actuales',
            'Stock ml inicial',
            'Stock ml actual',
            'Estado lote',
        ];
    }
}
