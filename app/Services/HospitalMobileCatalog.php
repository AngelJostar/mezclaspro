<?php

namespace App\Services;

use App\Models\Hospital;

class HospitalMobileCatalog
{
    public function products(Hospital $hospital, string $category): array
    {
        $list = match ($category) {
            'oncologicos' => $hospital->oncoMedicineList,
            'antibioticos' => $hospital->antibioticMedicineList,
            default => $hospital->nutriMedicineList,
        };
        if (!$list) return [];
        if ($category === 'nutricionales') {
            if (!$list->is_active) return [];
            return $list->items()->where('is_active', true)
                ->whereHas('presentation', fn ($q) => $q->where('is_available', true)->whereHas('catalog', fn ($c) => $c->where('is_active', true)))
                ->with('presentation.catalog')->get()->map(fn ($item) => [
                    'id' => $item->presentation->id, 'name' => $item->presentation->catalog->denominacion_generica,
                    'presentation' => $item->presentation->presentacion, 'unit' => 'ml',
                ])->values()->all();
        }
        if ($list->catalog_category !== $category) return [];
        return $list->presentations()->wherePivot('is_active', true)->where('is_available', true)
            ->whereHas('catalog', fn ($q) => $q->where('state', true)->where('catalog_category', $category))
            ->with('catalog')->get()->map(fn ($p) => ['id' => $p->id, 'name' => $p->catalog->denominacion,
                'presentation' => trim($p->marca.' '.$p->presentacion), 'unit' => 'mg'])->values()->all();
    }
}
