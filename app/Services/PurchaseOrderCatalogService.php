<?php

namespace App\Services;

use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

class PurchaseOrderCatalogService
{
    public function products(Warehouse $warehouse, string $destination): Collection
    {
        $scope = fn ($query) => $query
            ->where('laboratory_id', $warehouse->laboratory_id)
            ->where('warehouse_id', $warehouse->id);

        // Membership comes from the warehouse's registered presentations, including exhausted lots.
        $products = match ($destination) {
            'oncologicos', 'antibioticos' => MedicinePresentation::query()
                ->with('catalog:id,denominacion')
                ->whereHas('catalog', fn ($query) => $query->where(function ($category) use ($destination) {
                    $category->where('catalog_category', $destination);
                    if ($destination === 'oncologicos') {
                        $category->orWhereNull('catalog_category');
                    }
                }))
                ->whereHas('batches', $scope)
                ->get()
                ->map(fn ($product) => $this->option($destination, $product->id, [
                    $product->catalog->denominacion, $product->presentacion,
                    $product->marca, $product->fabricante,
                ])),
            'nutricionales' => NutritionMedicinePresentation::query()
                ->with('catalog:id,denominacion_generica')
                ->whereHas('catalog')
                ->whereHas('stocks', $scope)
                ->get()
                ->map(fn ($product) => $this->option($destination, $product->id, [
                    $product->catalog->denominacion_generica, $product->presentacion,
                    $product->denominacion_comercial, $product->fabricante,
                ])),
            'insumos' => DiluentPresentation::query()
                ->with('diluent:id,denominacion_generica')
                ->whereHas('diluent')
                ->where('laboratory_id', $warehouse->laboratory_id)
                ->where('warehouse_id', $warehouse->id)
                ->orderBy('id')
                ->get()
                ->map(fn ($product) => $this->option($destination, $product->id, [
                    $product->diluent->denominacion_generica, $product->presentacion,
                    $product->denominacion_comercial, $product->fabricante,
                ]))
                ->unique('description'),
            default => collect(),
        };

        return $products->sortBy('description', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function option(string $destination, int $id, array $parts): array
    {
        return [
            'product_key' => $destination.':'.$id,
            'description' => collect($parts)->map(fn ($part) => trim((string) $part))
                ->filter(fn ($part) => $part !== '')->unique()->implode(' - '),
        ];
    }
}
