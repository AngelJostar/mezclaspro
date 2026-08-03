<?php

namespace App\Exports\Oncologicos;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class OncologicosInventoryExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected int $laboratoryId;
    protected string $q;
    protected string $stock;

    public function __construct(int $laboratoryId, string $q = '', string $stock = '')
    {
        $this->laboratoryId = $laboratoryId;
        $this->q = trim($q);
        $this->stock = $stock;
    }

    public function array(): array
    {
        $rows = [];

        $items = DB::table('medicines_catalog as mc')
            ->join('medicine_presentations as mp', 'mp.catalog_id', '=', 'mc.id')
            ->leftJoin('medicine_batches as mb', function ($join) {
                $join->on('mb.medicine_presentation_id', '=', 'mp.id')
                    ->where('mb.laboratory_id', '=', $this->laboratoryId);
            })
            ->leftJoin('laboratories as l', 'l.id', '=', DB::raw($this->laboratoryId))
            ->when($this->q !== '', function ($query) {
                $query->where(function ($w) {
                    $w->where('mc.denominacion', 'like', "%{$this->q}%")
                        ->orWhere('mp.presentacion', 'like', "%{$this->q}%")
                        ->orWhere('mp.marca', 'like', "%{$this->q}%")
                        ->orWhere('mb.lote', 'like', "%{$this->q}%");
                });
            })
            ->when($this->stock !== '', function ($query) {
                if ($this->stock === '1') {
                    $query->whereNotNull('mb.id')
                        ->where('mb.stock_actual', '>', 0);
                } elseif ($this->stock === '0') {
                    $query->where(function ($w) {
                        $w->whereNull('mb.id')
                            ->orWhere('mb.stock_actual', '<=', 0);
                    });
                }
            })
            ->select([
                'l.nombre as laboratorio',
                'l.estado as laboratorio_estado',

                'mc.id as catalog_id',
                'mc.denominacion',
                'mc.state',
                'mc.requires_infusor',
                'mc.conc_min',
                'mc.conc_max',

                'mp.id as presentation_id',
                'mp.presentacion',
                'mp.contenido_valor',
                'mp.contenido_unidad',
                'mp.marca',
                'mp.fabricante',
                'mp.volumen_diluyente',
                'mp.precio_frasco',
                'mp.legend',
                'mp.temp_min_c',
                'mp.temp_max_c',
                'mp.stability_hours',
                'mp.is_available',

                'mb.id as batch_id',
                'mb.lote',
                'mb.caducidad',
                'mb.fecha_ingreso',
                'mb.stock_inicial',
                'mb.stock_actual',
                'mb.stock_reservado',
                'mb.is_active as batch_active',
            ])
            ->orderBy('mc.denominacion')
            ->orderBy('mp.presentacion')
            ->orderByRaw('CASE WHEN mb.caducidad IS NULL THEN 1 ELSE 0 END')
            ->orderBy('mb.caducidad')
            ->orderBy('mb.id')
            ->get();

        foreach ($items as $item) {
            $rows[] = [
                $item->laboratorio,
                $item->laboratorio_estado,

                $item->catalog_id,
                $item->denominacion,
                $item->state ? 'Activo' : 'Inactivo',
                $item->requires_infusor ? 'Sí' : 'No',
                $item->conc_min,
                $item->conc_max,

                $item->presentation_id,
                $item->marca,
                $item->fabricante,
                $item->presentacion,
                $item->contenido_valor,
                $item->contenido_unidad,
                $item->volumen_diluyente,
                $item->precio_frasco,
                $item->legend,
                $item->temp_min_c,
                $item->temp_max_c,
                $item->stability_hours,
                $item->is_available ? 'Disponible' : 'No disponible',

                $item->batch_id,
                $item->lote,
                $item->caducidad,
                $item->fecha_ingreso,
                $item->stock_inicial ?? 0,
                $item->stock_actual ?? 0,
                $item->stock_reservado ?? 0,
                ($item->batch_id && $item->batch_active) ? 'Activo' : 'Inactivo / Sin lote',
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Laboratorio',
            'Estado laboratorio',

            'ID catálogo',
            'Denominación',
            'Estado medicamento',
            'Requiere infusor',
            'Concentración mínima',
            'Concentración máxima',

            'ID presentación',
            'Marca',
            'Fabricante',
            'Presentación',
            'Contenido valor',
            'Contenido unidad',
            'Volumen diluyente',
            'Precio frasco',
            'Leyenda',
            'Temp. mínima',
            'Temp. máxima',
            'Estabilidad horas',
            'Estado presentación',

            'ID lote',
            'Lote',
            'Caducidad',
            'Fecha ingreso',
            'Stock inicial',
            'Stock actual',
            'Stock reservado',
            'Estado lote',
        ];
    }
}
