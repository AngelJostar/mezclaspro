<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\PriceListAdditionalCharge;
use App\Models\RequestQuotation;
use App\Models\User;
use App\Models\Oncologicos\MedicineList;
use App\Models\Nutricionales\NutriMedicineList;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestQuotationCaptureService
{
    public function __construct(private InstitutionBillingPricingService $pricing) {}

    public function catalog(User $user, string $category, int $hospitalId, bool $noCommercial = false, ?string $billingMode = null): array
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
        $list = match ($category) {
            'oncologicos' => $hospital->oncoMedicineList,
            'antibioticos' => $hospital->antibioticMedicineList,
            default => $hospital->nutriMedicineList,
        };
        if ($noCommercial) {
            $listId = config('request-quotations.base_lists.'.$category);
            $list = $listId ? ($category === 'nutricionales'
                ? NutriMedicineList::find($listId) : MedicineList::find($listId)) : null;
            if (!$list) $this->fail('no_commercial_relationship', 'No hay una lista base/generica configurada para esta categoria.');
            if (!in_array($billingMode, ['unit', 'frasco'], true)) $this->fail('billing_mode', 'Selecciona la modalidad de cobro.');
        } elseif ($billingMode !== null) {
            $this->fail('billing_mode', 'El cobro se determina por la lista asignada al hospital.');
        }
        if (!$list || ($category === 'nutricionales' && !$list->is_active)
            || ($category !== 'nutricionales' && $list->catalog_category !== $category)) {
            $this->fail('hospital_id', 'El hospital no tiene una lista de precios vigente para este tipo de mezcla.');
        }

        $products = [];
        $serviceTotal = 0.0;
        $supplies = [];
        if ($category !== 'nutricionales') {
            $presentations = $list->presentations()->where('medicine_presentations.is_available', true)
                ->wherePivot('is_active', true)
                ->whereHas('catalog', fn ($query) => $query->where('state', true)->where('catalog_category', $category))
                ->with('catalog.diluents')->orderBy('medicine_presentations.id')->get();
            foreach ($presentations as $presentation) {
                $config = $presentation->pivot;
                $contentMg = $presentation->contentInMilligrams();
                $unit = $config->charge_by ?: ($list->charge_by ?: 'mg');
                if ($noCommercial) $unit = $billingMode === 'frasco' ? 'frasco' : 'mg';
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
                    'unit' => $noCommercial ? ($billingMode === 'frasco' ? 'frasco' : 'ml') : ($item->charge_by ?: 'ml'),
                    'unit_price' => $item->precio_ml !== null ? (float) $item->precio_ml : null,
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
            'price_list' => ['id' => $list->id, 'name' => $list->name, 'source' => $noCommercial ? 'generic' : 'hospital'],
            'products' => $products, 'charges' => $charges,
        ];
    }

    public function commercialCatalog(User $user, array $data): array
    {
        $catalog = $this->catalog($user, $data['category'], (int) $data['hospital_id'],
            (bool) ($data['no_commercial_relationship'] ?? false), $data['billing_mode'] ?? null);
        $unit = $data['category'] === 'nutricionales' ? 'ml' : 'mg';
        foreach ($catalog['products'] as &$product) {
            // Legacy nutrition tariffs store price per mL even for whole-bottle billing.
            if ($unit === 'ml' && $product['unit'] === 'frasco') {
                $product['unit_price'] = $product['unit_price'] !== null && $product['container_ml'] > 0
                    ? round($product['unit_price'] * $product['container_ml'], 4) : null;
            } elseif ($unit === 'mg' && $product['unit'] === 'ml') {
                $product['unit_price'] = $product['unit_price'] !== null && $product['container_ml'] > 0 && $product['content_mg'] > 0
                    ? round($product['unit_price'] * $product['container_ml'] / $product['content_mg'], 4) : null;
                $product['unit'] = 'mg';
            }
            $product['content'] = $unit === 'ml' ? $product['container_ml'] : $product['content_mg'];
        }
        unset($product);
        $modes = array_unique(array_column($catalog['products'], 'unit'));
        $catalog['billing_mode'] = count($modes) === 1 ? (reset($modes) === 'frasco' ? 'frasco' : 'unit') : 'mixed';
        $catalog['concentration_unit'] = $unit;
        return $catalog;
    }

    public function capture(User $user, array $data, bool $preview = false): array
    {
        if (($data['flow'] ?? null) === 'commercial') return $this->captureCommercial($user, $data, $preview);
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

    private function captureCommercial(User $user, array $data, bool $preview): array
    {
        $grouped = isset($data['mixture_count']);
        if ($grouped) {
            $data['mixture_count'] = (int) $data['mixture_count'];
            $numbers = []; $seen = [];
            foreach ($data['items'] as $index => &$item) {
                $number = (int) $item['mixture_number'];
                if ($number > $data['mixture_count']) $this->fail("items.$index.mixture_number", 'La mezcla no corresponde a esta cotizacion.');
                $key = $number.':'.$item['presentation_id'];
                if (isset($seen[$key])) $this->fail("items.$index.presentation_id", 'El medicamento ya esta agregado a esta mezcla.');
                $seen[$key] = true; $numbers[$number] = true; $item['mixture_number'] = $number;
            }
            unset($item);
            if (count($numbers) !== $data['mixture_count']) $this->fail('items', 'Agrega al menos un medicamento a cada mezcla.');
            usort($data['items'], fn ($left, $right) => $left['mixture_number'] <=> $right['mixture_number']);
        }
        $catalog = $this->commercialCatalog($user, $data);
        if (!in_array((int) $data['institution_id'], array_column($catalog['institutions'], 'id'), true)) {
            $this->fail('institution_id', 'La institucion seleccionada no corresponde al hospital.');
        }
        $products = collect($catalog['products'])->keyBy('id');
        $lines = [];
        foreach ($data['items'] as $index => &$item) {
            $product = $products->get($item['presentation_id']);
            $this->assertProduct($product, "items.$index.presentation_id");
            if (!in_array($product['unit'], [$catalog['concentration_unit'], 'frasco'], true)) {
                $this->fail("items.$index.presentation_id", 'La unidad de cobro no corresponde a la categoria.');
            }
            $bottle = $product['unit'] === 'frasco';
            $quantityField = $bottle ? 'bottle_count' : 'concentration';
            $otherField = $bottle ? 'concentration' : 'bottle_count';
            if (!isset($item[$quantityField]) || array_key_exists($otherField, $item)) {
                $this->fail("items.$index.$quantityField", $bottle
                    ? 'Captura la cantidad de frascos, no la concentracion, para este medicamento.'
                    : 'Captura la concentracion solicitada para este medicamento.');
            }
            $quantity = $bottle ? (int) $item['bottle_count'] : (float) $item['concentration'];
            $item[$quantityField] = $quantity;
            $item['product_name'] = $product['name'];
            $item['presentation_name'] = trim($product['brand'].' '.$product['presentation']);
            $item['unit'] = $product['unit'];
            $listPrice = $product['unit_price'];
            if (array_key_exists('unit_price_override', $item)) {
                $item['unit_price_override'] = round((float) $item['unit_price_override'], 4);
                $product['unit_price'] = $item['unit_price_override'];
            }
            // Only the quotation snapshot changes; the hospital tariff remains untouched.
            $lines[] = $this->line($product, $quantity, 1) + [
                'presentation_content' => $product['content'],
                'concentration_unit' => $catalog['concentration_unit'],
                'list_unit_price' => $listPrice,
                'price_adjusted' => $product['unit_price'] != $listPrice,
                'price_adjusted_by' => $product['unit_price'] != $listPrice ? $user->id : null,
            ] + ($grouped ? ['mixture_number' => $item['mixture_number']] : []) + ($bottle ? []
                : ['concentration' => $quantity, 'concentration_unit' => $catalog['concentration_unit']]);
        }
        unset($item);
        // Older captures did not group medication rows explicitly.
        $mixtures = $grouped ? $data['mixture_count'] : ($data['category'] === 'nutricionales' ? 1 : count($data['items']));
        $requirements = []; $requestedMedicines = [];
        foreach ($data['requirements'] ?? [] as $index => $requirement) {
            if ((int) $requirement['mixture_number'] > $mixtures) {
                $this->fail("requirements.$index.mixture_number", 'El requerimiento no corresponde a una mezcla de esta cotizacion.');
            }
            $medicine = trim($requirement['medicine']);
            if ($medicine === '') $this->fail("requirements.$index.medicine", 'Captura el medicamento del requerimiento.');
            $key = (int) $requirement['mixture_number'].':'.mb_strtolower(Str::ascii($medicine));
            if (isset($requestedMedicines[$key])) $this->fail("requirements.$index.medicine", 'El medicamento ya esta capturado en la receta de esta mezcla.');
            $requestedMedicines[$key] = true;
            $requirements[] = ['mixture_number' => (int) $requirement['mixture_number'], 'medicine' => $medicine,
                'concentration' => round((float) $requirement['concentration'], 4), 'unit' => $catalog['concentration_unit']];
        }
        usort($requirements, fn ($left, $right) => $left['mixture_number'] <=> $right['mixture_number']);
        if (array_key_exists('requirements', $data)) $data['requirements'] = $requirements;
        foreach ($grouped ? range(1, $mixtures) : [null] as $number) {
            foreach ($catalog['charges'] as $charge) {
                if ($charge['total'] < 0) $this->fail('hospital_id', 'Un cargo de la lista tiene un importe invalido.');
                $count = $grouped ? 1 : $mixtures;
                $amount = round($charge['total'] * $count, 2);
                $tax = $charge['vat_included'] ? $this->pricing->splitIncludedVat($amount) : ['base' => $amount, 'vat' => 0];
                $lines[] = ['description' => $charge['name'], 'presentation' => '', 'quantity' => $count,
                    'unit' => 'servicio', 'unit_price' => round($tax['base'] / $count, 4), 'mixtures' => 1,
                    'subtotal' => $tax['base'], 'vat' => $tax['vat'], 'total' => $amount] + ($grouped ? ['mixture_number' => $number] : []);
            }
        }
        $total = round(array_sum(array_column($lines, 'total')), 2);
        if (!is_finite($total) || $total > 999999999.99) $this->fail('items', 'El importe de la cotizacion excede el limite permitido.');
        $snapshot = ['currency' => 'MXN', 'lines' => $lines, 'mixtures' => $mixtures, 'total' => $total,
            'category' => $data['category'], 'concentration_unit' => $catalog['concentration_unit'],
            'price_list' => $catalog['price_list'], 'billing_mode' => $catalog['billing_mode']];
        if ($requirements !== []) $snapshot['requirements'] = $requirements;
        $token = hash('sha256', json_encode($snapshot, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR));
        if (!$preview && !hash_equals($token, $data['pricing_token'] ?? '')) {
            $this->fail('pricing_token', 'Revisa nuevamente la cotizacion: los precios o medicamentos cambiaron.');
        }
        unset($data['action'], $data['submission_key'], $data['seller_id'], $data['pricing_token']);
        $patientNames = [];
        foreach (['patient_name', 'patient_paternal_surname', 'patient_maternal_surname'] as $field) {
            $data[$field] = trim($data[$field] ?? '');
            if ($data[$field] !== '') $patientNames[] = $data[$field];
        }
        $patientName = implode(' ', $patientNames);
        if (mb_strlen($patientName) > 255) $this->fail('patient_name', 'El nombre completo del paciente no debe exceder 255 caracteres.');
        $data['patient_platform_id'] = trim($data['patient_platform_id'] ?? '');
        return ['hospital_id' => $data['hospital_id'], 'institution_id' => $data['institution_id'], 'category' => $data['category'],
            'patient_name' => $patientName, 'price_list_id' => $catalog['price_list']['id'],
            'price_list_name' => $catalog['price_list']['name'], 'total' => $total, 'clinical_data' => $data,
            'pricing_snapshot' => $snapshot] + ($preview ? ['pricing_token' => $token] : []);
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
