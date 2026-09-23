<?php

namespace App\Services;

use App\Models\ConsumableCatalogPresentation;
use App\Models\MinimumStockSetting;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\DiluentCatalogPresentation;
use App\Models\Oncologicos\MedicinePresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MinimumStockCatalogService
{
    public function query(string $type): Builder
    {
        if ($type === 'diluent') abort_unless(Schema::hasTable('diluent_catalog_presentations'), 404);
        return match ($type) {
            'medicine' => MedicinePresentation::query()->with('catalog')->whereHas('catalog'),
            'nutrition' => NutritionMedicinePresentation::query()->with('catalog')->whereHas('catalog'),
            'diluent' => DiluentCatalogPresentation::query()->with('diluent')->whereHas('diluent'),
            'consumable' => ConsumableCatalogPresentation::query()->with('item')->whereHas('item'),
            default => abort(404),
        };
    }

    public function rows(int $laboratoryId, string $category = 'todas'): Collection
    {
        $settings = MinimumStockSetting::query()->with('supplier')
            ->where('laboratory_id', $laboratoryId)->get()
            ->keyBy(fn ($setting) => $setting->product_type.':'.$setting->presentation_id);

        // Older installations still keep diluents only as lots, not catalog presentations.
        $types = ['medicine', 'nutrition', 'consumable'];
        if (Schema::hasTable('diluent_catalog_presentations')) $types[] = 'diluent';
        $types = match ($category) {
            'nutricionales' => ['nutrition'],
            'oncologicos', 'antibioticos' => ['medicine'],
            default => $types,
        };

        return collect($types)
            ->flatMap(function ($type) use ($settings, $laboratoryId, $category) {
                $stock = $this->currentStock($type, $laboratoryId);
                $products = $this->query($type);
                if ($type === 'medicine' && in_array($category, ['oncologicos', 'antibioticos'], true)) {
                    $products->whereHas('catalog', fn ($query) => $query->forCategory($category));
                }
                return $products->get()->map(function ($product) use ($type, $settings, $stock) {
                    $setting = $settings->get($type.':'.$product->id);
                    $dose = match ($type) {
                        'medicine' => $product->contentInMilligrams(),
                        'nutrition' => $product->presentacion_ml,
                        'diluent' => $product->volume_ml,
                        default => null,
                    };
                    return (object) [
                        'type' => $type,
                        'id' => $product->id,
                        'key' => $type.':'.$product->id,
                        'category' => match ($type) {
                            'medicine' => $product->catalog->catalog_category ?: 'oncologicos',
                            'nutrition' => 'nutricionales',
                            default => 'insumos',
                        },
                        'product' => match ($type) {
                            'medicine' => $product->catalog->denominacion,
                            'nutrition' => $product->catalog->denominacion_generica,
                            'diluent' => $product->diluent->denominacion_generica,
                            'consumable' => $product->item->name,
                        },
                        'active' => (bool) (in_array($type, ['medicine', 'nutrition'], true)
                            ? $product->is_available : ($product->is_active && ($type !== 'consumable' || $product->item->is_active))),
                        'dose' => $dose === null ? '-' : rtrim(rtrim(number_format((float) $dose, 4, '.', ''), '0'), '.')
                            .($type === 'medicine' ? ' mg' : ' ml'),
                        'presentation' => $product->presentacion ?? $product->presentation ?? '-',
                        'commercial_name' => $product->marca ?? $product->denominacion_comercial ?? $product->commercial_name ?? '-',
                        'minimum_stock' => $setting?->minimum_stock,
                        'maximum_stock' => $setting?->maximum_stock,
                        'current_stock' => (float) $stock->get($product->id, 0),
                        'supplier' => $setting?->supplier,
                    ];
                });
            })->sortBy(fn ($row) => mb_strtolower($row->product.' '.$row->presentation.' '.$row->commercial_name))
            ->values();
    }

    private function currentStock(string $type, int $laboratoryId): Collection
    {
        [$table, $presentation, $quantity] = match ($type) {
            'medicine' => ['medicine_batches', 'medicine_presentation_id', 'stock_actual'],
            'nutrition' => ['medicine_laboratory_stocks', 'nutrition_medicine_presentation_id', 'frascos_actuales'],
            'diluent' => ['diluent_presentations', 'catalog_presentation_id', 'stock_actual'],
            'consumable' => ['consumable_lots', 'catalog_presentation_id', 'stock_actual'],
        };

        $query = DB::table($table)->where('is_active', true);
        if ($type === 'consumable') {
            $query->whereIn('warehouse_id', DB::table('warehouses')->select('id')->where('laboratory_id', $laboratoryId));
        } else {
            $query->where('laboratory_id', $laboratoryId);
        }

        // Physical pieces across all active lots, not available doses or unreserved stock.
        return $query->select($presentation)->selectRaw("COALESCE(SUM({$quantity}), 0) as current_stock")
            ->groupBy($presentation)->pluck('current_stock', $presentation);
    }
}
