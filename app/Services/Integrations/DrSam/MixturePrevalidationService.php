<?php

namespace App\Services\Integrations\DrSam;

use App\Models\Hospital;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Oncologicos\MedicinePresentation;
use Illuminate\Support\Collection;

class MixturePrevalidationService
{
    public function validate(Hospital $hospital, array $payload): array
    {
        $type = $payload['catalog_type'];
        $version = $this->catalogVersion($hospital, $type);
        $errors = collect();

        if (! empty($payload['catalog_version']) && $payload['catalog_version'] !== $version) {
            $errors->push($this->error('stale_catalog', 'catalog_version', 'La version del catalogo ya no es vigente.'));
        }

        $items = collect($payload['items'])->map(function (array $item, int $index) use ($hospital, $type, $errors): array {
            return $type === 'npt'
                ? $this->validateNptItem($hospital, $item, $index, $errors)
                : $this->validateOncologyItem($hospital, $item, $index, $errors);
        })->values();

        if ($type === 'npt') {
            $this->validateNptAuxiliarySupplies($hospital, $errors);
        }

        return [
            'valid' => $errors->isEmpty(),
            'medical_unit' => [
                'external_code' => $hospital->external_code,
                'name' => $hospital->name,
            ],
            'catalog_type' => $type,
            'catalog_version' => $version,
            'items' => $items,
            'errors' => $errors->values(),
        ];
    }

    private function validateNptAuxiliarySupplies(Hospital $hospital, Collection $errors): void
    {
        $bag = NutriMedicineListItem::query()
            ->with(['presentation.catalog.input', 'presentation.stocks' => function ($query) use ($hospital): void {
                $query->where('laboratory_id', $hospital->laboratory_id)
                    ->where('is_active', true)
                    ->where('frascos_actuales', '>', 0)
                    ->whereDate('caducidad', '>=', today());
            }])
            ->where('nutri_medicine_list_id', $hospital->nutri_medicine_list_id)
            ->get()
            ->first(fn ($item) => (int) ($item->presentation?->catalog?->input?->category_id) === 6);

        if (! $bag?->presentation) {
            $errors->push($this->error('auxiliary_supply_not_configured', 'npt.eva_bag', 'La lista nutricional de la unidad no tiene una Bolsa EVA operativa configurada.'));

            return;
        }

        if ($bag->presentation->stocks->sum('frascos_actuales') < 1) {
            $errors->push($this->error('insufficient_auxiliary_stock', 'npt.eva_bag', 'No hay existencia disponible de Bolsa EVA para preparar la solicitud.'));
        }
    }

    private function validateNptItem(Hospital $hospital, array $item, int $index, Collection $errors): array
    {
        $path = "items.{$index}";
        $presentation = NutritionMedicinePresentation::query()
            ->with(['catalog', 'listItems'])
            ->where('external_code', $item['presentation_code'])
            ->whereHas('catalog', fn ($query) => $query->where('external_code', $item['product_code']))
            ->first();
        $inList = $presentation?->listItems->contains('nutri_medicine_list_id', $hospital->nutri_medicine_list_id) ?? false;

        if (! $presentation || ! $inList || ! $presentation->is_available || ! $presentation->catalog?->is_active) {
            $errors->push($this->error('catalog_item_not_available', $path, 'El producto o presentacion NPT no pertenece al catalogo vigente de la unidad.'));

            return $this->itemResult($item, false, null, null);
        }

        if ($item['unit'] !== 'ml') {
            $errors->push($this->error('invalid_unit', "{$path}.unit", 'Los insumos NPT deben solicitarse en ml.'));
        }

        $available = (float) $presentation->stocks()
            ->where('laboratory_id', $hospital->laboratory_id)
            ->where('is_active', true)
            ->whereDate('caducidad', '>=', today())
            ->sum('stock_ml_actual');
        $sufficient = $available >= (float) $item['quantity'];

        if (! $sufficient) {
            $errors->push($this->error('insufficient_stock', $path, 'El inventario NPT disponible es insuficiente.'));
        }

        return $this->itemResult($item, $sufficient && $item['unit'] === 'ml', $available, 'ml');
    }

    private function validateOncologyItem(Hospital $hospital, array $item, int $index, Collection $errors): array
    {
        $path = "items.{$index}";
        $presentation = MedicinePresentation::query()
            ->with('catalog')
            ->where('external_code', $item['presentation_code'])
            ->whereHas('catalog', fn ($query) => $query->where('external_code', $item['product_code']))
            ->whereHas('lists', fn ($query) => $query->whereKey($hospital->onco_medicine_list_id))
            ->first();

        if (! $presentation || ! $presentation->is_available || ! $presentation->catalog?->state) {
            $errors->push($this->error('catalog_item_not_available', $path, 'El producto o presentacion oncologica no pertenece al catalogo vigente de la unidad.'));

            return $this->itemResult($item, false, null, null);
        }

        if (! in_array($item['unit'], ['mg', 'unit'], true)) {
            $errors->push($this->error('invalid_unit', "{$path}.unit", 'Los medicamentos oncologicos deben solicitarse en mg o unidades.'));
        }

        $requiredUnits = $item['unit'] === 'mg'
            ? (int) ceil((float) $item['quantity'] / max((float) $presentation->contenido_valor, 0.0001))
            : (int) ceil((float) $item['quantity']);
        $available = (int) $presentation->batches()
            ->where('laboratory_id', $hospital->laboratory_id)
            ->where('is_active', true)
            ->whereDate('caducidad', '>=', today())
            ->get()
            ->sum(fn ($batch) => $batch->stock_disponible);
        $sufficient = $available >= $requiredUnits;

        if (! $sufficient) {
            $errors->push($this->error('insufficient_stock', $path, 'El inventario oncologico disponible es insuficiente.'));
        }

        return $this->itemResult($item, $sufficient && in_array($item['unit'], ['mg', 'unit'], true), $available, 'unit', $requiredUnits);
    }

    private function catalogVersion(Hospital $hospital, string $type): ?string
    {
        $list = $type === 'npt' ? $hospital->nutriMedicineList : $hospital->oncoMedicineList;
        $source = collect([$hospital->updated_at, $list?->updated_at])->filter()->sortDesc()->first();

        return optional($source)->toIso8601String();
    }

    private function itemResult(array $item, bool $valid, int|float|null $available, ?string $availabilityUnit, ?int $requiredUnits = null): array
    {
        return array_filter([
            'product_code' => $item['product_code'],
            'presentation_code' => $item['presentation_code'],
            'quantity' => (float) $item['quantity'],
            'unit' => $item['unit'],
            'valid' => $valid,
            'available_quantity' => $available,
            'availability_unit' => $availabilityUnit,
            'required_units' => $requiredUnits,
        ], fn ($value) => $value !== null);
    }

    private function error(string $code, string $path, string $message): array
    {
        return compact('code', 'path', 'message');
    }
}
