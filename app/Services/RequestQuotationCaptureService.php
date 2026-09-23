<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\PriceListAdditionalCharge;
use App\Models\RequestQuotation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestQuotationCaptureService
{
    public function __construct(private InstitutionBillingPricingService $pricing) {}

    public function catalog(User $user, string $category, int $hospitalId): array
    {
        abort_unless(RequestQuotation::canCreate($user, $category), 403);
        if ($user->hasAnyRole(['Cliente', 'Institucion'])) {
            abort_unless($user->hospital_id && (int) $user->hospital_id === $hospitalId, 403);
        }
        $hospital = Hospital::with('instituciones')->findOrFail($hospitalId);
        if (!$hospital->is_active || !$hospital->access_is_active) {
            $this->fail('hospital_id', 'El hospital no esta activo.');
        }
        $institutions = $hospital->instituciones->where('is_active', true)->values();
        if ($institutions->isEmpty()) {
            $this->fail('institution_id', 'El hospital no tiene una institucion activa asignada.');
        }
        $list = $category === 'oncologicos' ? $hospital->oncoMedicineList : $hospital->nutriMedicineList;
        if (!$list || ($category === 'nutricionales' && !$list->is_active)
            || ($category === 'oncologicos' && $list->catalog_category !== 'oncologicos')) {
            $this->fail('hospital_id', 'El hospital no tiene una lista de precios vigente para este tipo de mezcla.');
        }

        $products = [];
        $serviceTotal = 0.0;
        $supplies = [];
        if ($category === 'oncologicos') {
            $presentations = $list->presentations()->where('medicine_presentations.is_available', true)
                ->wherePivot('is_active', true)
                ->whereHas('catalog', fn ($query) => $query->where('state', true)->where('catalog_category', 'oncologicos'))
                ->with('catalog.diluents')->orderBy('medicine_presentations.id')->get();
            foreach ($presentations as $presentation) {
                $config = $presentation->pivot;
                $contentMg = $presentation->contentInMilligrams();
                $unit = $config->charge_by ?: ($list->charge_by ?: 'mg');
                $price = match ($unit) {
                    'mg' => $config->precio_mg_override > 0 ? (float) $config->precio_mg_override
                        : ($contentMg > 0 && $config->precio !== null ? round((float) $config->precio / $contentMg, 4) : null),
                    'ml' => $config->precio_ml_override !== null ? (float) $config->precio_ml_override
                        : ($config->precio !== null ? (float) $config->precio : null),
                    'frasco' => $config->precio !== null ? (float) $config->precio : null,
                    default => null,
                };
                $products[] = [
                    'id' => $presentation->id, 'name' => $presentation->catalog->denominacion,
                    'brand' => $presentation->marca, 'presentation' => $presentation->presentacion,
                    'unit' => $unit, 'unit_price' => $price, 'vat' => (bool) $config->iva_desglosado,
                    'content_mg' => $contentMg, 'container_ml' => (float) $presentation->volumen_diluyente,
                    'diluents' => $presentation->catalog->diluents->map(fn ($diluent) => [
                        'id' => $diluent->id, 'name' => $diluent->denominacion_generica,
                    ])->values()->all(),
                ];
            }
            if ($list->has_mixing_service && ($list->mixing_service_price === null || $list->mixing_service_price < 0)) {
                $this->fail('hospital_id', 'El servicio de mezclado no tiene un precio valido en la lista.');
            }
            $serviceTotal = $list->has_mixing_service ? (float) $list->mixing_service_price : 0.0;
        } else {
            $items = $list->items()->where('is_active', true)->whereHas('presentation', fn ($query) => $query->where('is_available', true)
                ->whereHas('catalog', fn ($catalog) => $catalog->where('is_active', true)))
                ->with(['presentation.catalog.category', 'presentation.catalog.input'])->orderBy('id')->get();
            foreach ($items as $item) {
                $presentation = $item->presentation;
                $catalog = $presentation->catalog;
                $name = $catalog->denominacion_generica;
                $description = mb_strtolower($name.' '.$catalog->input?->description);
                if ((str_contains($description, 'servicio de mezclado') || str_contains($description, 'preparaci') && str_contains($description, 'npt')
                    || str_contains($description, 'bolsa eva') || str_contains($description, 'set de infusi'))
                    && ($item->precio_ml === null || $item->precio_ml < 0)) {
                    $this->fail('hospital_id', 'Un servicio o insumo de la lista no tiene un precio valido.');
                }
                if (str_contains($description, 'servicio de mezclado') || str_contains($description, 'preparaci') && str_contains($description, 'npt')) {
                    $serviceTotal += (float) $item->precio_ml;
                    continue;
                }
                if (str_contains($description, 'bolsa eva') || str_contains($description, 'set de infusi')) {
                    $supplies[] = ['name' => $name, 'total' => (float) $item->precio_ml,
                        'bag' => str_contains($description, 'bolsa eva')];
                    continue;
                }
                $products[] = [
                    'id' => $presentation->id, 'name' => $name, 'brand' => $presentation->denominacion_comercial,
                    'presentation' => $presentation->presentacion, 'group' => $catalog->category?->name ?: 'Otros componentes',
                    'unit' => $item->charge_by ?: 'ml', 'unit_price' => $item->precio_ml !== null ? (float) $item->precio_ml : null,
                    'container_ml' => (float) $presentation->presentacion_ml, 'vat' => false,
                ];
            }
            $groupOrder = ['aminoacidos' => 1, 'lipidos' => 2, 'carbohidratos' => 3, 'electrolitos' => 4, 'aditivos' => 5];
            $products = collect($products)->sortBy(fn ($product) => [
                $groupOrder[Str::lower(Str::ascii($product['group']))] ?? 6, $product['group'], $product['name'], $product['brand'],
            ])->values()->all();
        }
        $charges = PriceListAdditionalCharge::where('price_list_type', $category)->where('price_list_id', $list->id)
            ->where('is_active', true)->get()->map(fn ($charge) => [
                'name' => $charge->name, 'total' => (float) $charge->amount, 'vat_included' => (bool) $charge->iva_included,
            ])->all();
        $hasAdditionalCharges = count($charges) > 0;
        foreach ($supplies as $supply) {
            if (!$supply['bag'] || !$hasAdditionalCharges) $charges[] = ['name' => $supply['name'], 'total' => $supply['total'], 'vat_included' => true];
        }
        if ($serviceTotal > 0) {
            array_unshift($charges, ['name' => 'Servicio de mezclado', 'total' => $serviceTotal, 'vat_included' => true]);
        }

        return [
            'hospital_id' => $hospital->id, 'institutions' => $institutions->map->only(['id', 'nombre'])->all(),
            'price_list' => ['id' => $list->id, 'name' => $list->name], 'products' => $products, 'charges' => $charges,
        ];
    }

    public function capture(User $user, array $data): array
    {
        $catalog = $this->catalog($user, $data['category'], (int) $data['hospital_id']);
        if (!in_array((int) $data['institution_id'], array_column($catalog['institutions'], 'id'), true)) {
            $this->fail('institution_id', 'La institucion seleccionada no corresponde al hospital.');
        }
        $products = collect($catalog['products'])->keyBy('id');
        $lines = [];
        $mixtures = 0;
        if ($data['category'] === 'oncologicos') {
            foreach ($data['rows'] as $index => &$row) {
                $product = $products->get($row['presentation_id']);
                $this->assertProduct($product, "rows.$index.presentation_id");
                $row['deliveries'] = array_values($row['deliveries']);
                if (count(array_unique(array_map(fn ($date) => substr($date, 0, 10), $row['deliveries']))) !== count($row['deliveries'])) {
                    $this->fail("rows.$index.deliveries", 'Usa una fecha distinta en cada entrega del medicamento.');
                }
                if (!empty($row['diluent_id']) && !in_array((int) $row['diluent_id'], array_column($product['diluents'], 'id'), true)) {
                    $this->fail("rows.$index.diluent_id", 'El diluyente no esta configurado para este medicamento.');
                }
                $count = (int) $row['boluses_per_day'] * count($row['deliveries']);
                $dose = (float) $row['dose_mg'];
                $quantity = match ($product['unit']) {
                    'mg' => $dose,
                    'ml' => $product['content_mg'] > 0 && $product['container_ml'] > 0
                        ? $dose * $product['container_ml'] / $product['content_mg'] : null,
                    'frasco' => $product['content_mg'] > 0 ? ceil($dose / $product['content_mg']) : null,
                    default => null,
                };
                if ($quantity === null) {
                    $this->fail("rows.$index.presentation_id", 'La presentacion no tiene contenido suficiente para calcular su precio.');
                }
                $mixtures += $count;
                $row['product_name'] = $product['name'];
                $row['presentation_name'] = trim($product['brand'].' '.$product['presentation']);
                $row['diluent_name'] = collect($product['diluents'])->firstWhere('id', $row['diluent_id'] ?? null)['name'] ?? null;
                $lines[] = $this->line($product, $quantity, $count);
            }
            unset($row);
        } else {
            $mixtures = 1;
            $volume = array_sum(array_column($data['components'], 'volume_ml'));
            $factor = 1 + (float) ($data['overfill_ml'] ?? 0) / $volume;
            $data['volume_total_ml'] = round($volume, 4);
            foreach ($data['components'] as $index => &$component) {
                $product = $products->get($component['presentation_id']);
                $this->assertProduct($product, "components.$index.presentation_id");
                if (!in_array($product['unit'], ['ml', 'frasco'], true)) {
                    $this->fail("components.$index.presentation_id", 'La unidad de cobro de este componente no es valida.');
                }
                $usedMl = (float) $component['volume_ml'] * $factor;
                if ($product['unit'] === 'frasco') {
                    if ($product['container_ml'] <= 0) {
                        $this->fail("components.$index.presentation_id", 'La presentacion no tiene volumen por frasco registrado.');
                    }
                    $quantity = ceil($usedMl / $product['container_ml']);
                    // Nutritional list prices are per mL, also when charging whole bottles.
                    $product['unit_price'] = round($product['unit_price'] * $product['container_ml'], 4);
                } else {
                    $quantity = $usedMl;
                }
                $component['product_name'] = $product['name'];
                $component['presentation_name'] = trim($product['brand'].' '.$product['presentation']);
                $component['quoted_volume_ml'] = round($usedMl, 4);
                $lines[] = $this->line($product, $quantity, 1);
            }
            unset($component);
        }
        foreach ($catalog['charges'] as $charge) {
            if ($charge['total'] < 0) $this->fail('hospital_id', 'Un cargo de la lista tiene un importe invalido.');
            $chargeTotal = round($charge['total'] * $mixtures, 2);
            $tax = $charge['vat_included'] ? $this->pricing->splitIncludedVat($chargeTotal) : ['base' => $chargeTotal, 'vat' => 0];
            $lines[] = ['description' => $charge['name'], 'presentation' => '', 'quantity' => $mixtures,
                'unit' => 'servicio', 'unit_price' => round($tax['base'] / $mixtures, 4), 'mixtures' => 1,
                'subtotal' => $tax['base'], 'vat' => $tax['vat'], 'total' => $chargeTotal];
        }
        $total = round(array_sum(array_column($lines, 'total')), 2);
        if (!is_finite($total) || $total > 999999999.99) {
            $this->fail('rows', 'El importe de la cotizacion excede el limite permitido.');
        }
        unset($data['attachment'], $data['action'], $data['submission_key'], $data['seller_id']);

        return ['hospital_id' => $data['hospital_id'], 'institution_id' => $data['institution_id'],
            'category' => $data['category'], 'patient_name' => $data['patient_name'],
            'price_list_id' => $catalog['price_list']['id'], 'price_list_name' => $catalog['price_list']['name'],
            'total' => $total, 'clinical_data' => $data,
            'pricing_snapshot' => ['currency' => 'MXN', 'lines' => $lines, 'mixtures' => $mixtures, 'total' => $total]];
    }

    private function assertProduct(?array $product, string $field): void
    {
        if (!$product) {
            $this->fail($field, 'El producto no esta activo en la lista de precios del hospital.');
        }
        if ($product['unit_price'] === null || $product['unit_price'] < 0) {
            $this->fail($field, 'El producto no tiene un precio valido registrado.');
        }
    }

    private function line(array $product, float $quantity, int $count): array
    {
        $base = round($quantity * $product['unit_price'], 2);
        $vat = $this->pricing->calculateVatFromBase($base, $product['vat']);
        return ['description' => $product['name'], 'presentation' => trim($product['brand'].' '.$product['presentation']),
            'quantity' => round($quantity, 4), 'unit' => $product['unit'], 'unit_price' => $product['unit_price'],
            'mixtures' => $count, 'subtotal' => round($base * $count, 2), 'vat' => round($vat * $count, 2),
            'total' => round(($base + $vat) * $count, 2)];
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
