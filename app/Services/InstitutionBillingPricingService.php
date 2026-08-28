<?php

namespace App\Services;

use App\Models\Nutricionales\Medicine as NutricionalMedicine;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\PriceListAdditionalCharge;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstitutionBillingPricingService
{
    public const IVA_RATE = 0.16;

    private array $oncoPresentationConfigs = [];

    private array $nutritionListConfigs = [];

    private ?float $mixingServicePrice = null;

    public function priceOncoMix(Mezcla $mezcla): array
    {
        $mezcla->loadMissing([
            'billing',
            'solicitud.hospital',
            'medicamentos.medicamentoOnco.catalog.presentations',
            'medicamentos.presentacionesUsadas.batch.presentation',
            'infusor',
        ]);

        $isAntibiotic = $mezcla->solicitud?->tipo_solicitud === 'antibioticos';
        $medicineListId = (int) ($isAntibiotic
            ? $mezcla->solicitud?->hospital?->antibiotic_medicine_list_id
            : $mezcla->solicitud?->hospital?->onco_medicine_list_id);
        $medicationLines = $mezcla->medicamentos
            ->map(fn ($medicamento) => $this->resolveOncoMedication($medicamento, $medicineListId))
            ->values();

        $medicationTotal = round((float) $medicationLines->sum('subtotal'), 2);
        $medicationVat = round((float) $medicationLines->sum('vat'), 2);
        $medicationTotalWithVat = round($medicationTotal + $medicationVat, 2);
        $infusorApplies = ((bool) $mezcla->set_infusion || !empty($mezcla->infusor_id)) && $mezcla->infusor;
        $suppliesTotal = $infusorApplies ? round((float) ($mezcla->infusor?->precio ?? 0), 2) : 0.0;
        $storedTotal = $this->parseMoney($mezcla->billing?->precio_total);
        $minimumBaseTotal = round($medicationTotal + $suppliesTotal, 2);

        $additionalCharges = $this->additionalCharges($isAntibiotic ? 'antibioticos' : 'oncologicos', $medicineListId);
        $additionalTotal = (float) $additionalCharges->sum('total');
        if ($storedTotal >= $minimumBaseTotal && $storedTotal > 0) {
            $serviceTotal = round($storedTotal - $minimumBaseTotal, 2);
            $totalIncluded = round($storedTotal + $medicationVat, 2);
        } else {
            $serviceTotal = $additionalCharges->isEmpty() ? $this->mixingServicePrice() : 0.0;
            $totalIncluded = round($medicationTotalWithVat + $suppliesTotal + $serviceTotal + $additionalTotal, 2);
        }

        $serviceVat = $this->splitIncludedVat($serviceTotal);
        $suppliesVat = $this->splitIncludedVat($suppliesTotal);
        $subtotalBeforeVat = round($medicationTotal + $serviceVat['base'] + $suppliesVat['base'], 2);

        $mezcla->setAttribute('infusor_aplica', (bool) $infusorApplies);
        $mezcla->setAttribute(
            'infusor_nombre',
            $infusorApplies
                ? (trim(($mezcla->infusor?->nombre_generico ?? '') . ' ' . ($mezcla->infusor?->nombre_comercial ?? '')) ?: 'Infusor')
                : 'Infusor'
        );
        $mezcla->setAttribute('infusor_precio', $suppliesTotal);
        $mezcla->setAttribute('infusor_subtotal', $suppliesTotal);
        $mezcla->setAttribute('mixing_service_base', $serviceVat['base']);
        $mezcla->setAttribute('mixing_service_vat', $serviceVat['vat']);
        $mezcla->setAttribute('mixing_service_total', $serviceTotal);
        $mezcla->setAttribute('medication_vat', $medicationVat);
        $mezcla->setAttribute('supplies_vat', $suppliesVat['vat']);
        $mezcla->setAttribute('total_iva_included', $totalIncluded);

        return [
            'description' => $medicationLines->pluck('description')->filter()->implode(', ')
                ?: ($isAntibiotic ? 'Medicamento antibiótico' : 'Medicamento oncológico'),
            'lines' => $medicationLines,
            'medication_total' => $medicationTotal,
            'medication_vat' => $medicationVat,
            'medication_total_iva_included' => $medicationTotalWithVat,
            'service_base' => $serviceVat['base'],
            'service_vat' => $serviceVat['vat'],
            'service_total' => $serviceTotal,
            'supplies_base' => $suppliesVat['base'],
            'supplies_vat' => $suppliesVat['vat'],
            'supplies_total' => $suppliesTotal,
            'additional_charge_lines' => $additionalCharges,
            'subtotal_before_vat' => $subtotalBeforeVat,
            'vat_total' => round($medicationVat + $serviceVat['vat'] + $suppliesVat['vat'], 2),
            'total_iva_included' => $totalIncluded,
        ];
    }

    public function priceNutritionRequest(NutricionalSolicitud $solicitud): array
    {
        $solicitud->loadMissing([
            'billing',
            'user.hospital',
            'solicitud_detail',
            'input.input.nutritionMedicineCatalog',
            'input.presentation',
        ]);

        $medicineListId = (int) ($solicitud->user?->hospital?->nutri_medicine_list_id ?? 0);
        $medicationLines = collect();
        $supplyLines = collect();
        $serviceFromInputs = 0.0;
        $additionalCharges = $this->additionalCharges('nutricionales', $medicineListId);

        foreach ($solicitud->input ?? collect() as $item) {
            $description = trim((string) ($item->input?->description ?? ''));
            $listConfig = $this->nutritionListConfig($item, $medicineListId);
            $unitPrice = $this->nutritionUnitPrice($item, $listConfig);
            $remissionDescription = trim((string) ($listConfig?->descripcion_remision ?? ''));

            if ($this->isNutritionService($description)) {
                if ($additionalCharges->isNotEmpty()) continue;
                $serviceFromInputs += $unitPrice;
                continue;
            }

            if ($this->isNutritionTaxableSupply($description)) {
                if ($additionalCharges->isNotEmpty() && stripos($description, 'bolsa eva') !== false) continue;
                $supplyLines->push([
                    'description' => $this->nutritionSupplyDescription($description),
                    'quantity' => 1.0,
                    'unit_label' => 'pieza',
                    'unit_price' => $unitPrice,
                    'subtotal' => round($unitPrice, 2),
                ]);
                continue;
            }

            $quantity = $this->nutritionVolume($solicitud, $item);
            $subtotal = round($quantity * $unitPrice, 2);

            $medicationLines->push([
                'description' => $remissionDescription
                    ?: ($item->input?->nutritionMedicineCatalog?->denominacion_generica
                        ?: ($description ?: 'Ingrediente de nutrición parenteral')),
                'quantity' => $quantity,
                'unit_label' => 'mL',
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);
        }

        $medicationTotal = round((float) $medicationLines->sum('subtotal'), 2);
        $suppliesTotal = round((float) $supplyLines->sum('subtotal'), 2);
        $storedTotal = $this->parseMoney($solicitud->billing?->precio_total);
        $minimumTotal = round($medicationTotal + $suppliesTotal, 2);

        if ($storedTotal >= $minimumTotal && $storedTotal > 0) {
            $serviceTotal = round($storedTotal - $minimumTotal, 2);
            $totalIncluded = round($storedTotal, 2);
        } else {
            $serviceTotal = $additionalCharges->isEmpty() ? round($serviceFromInputs > 0 ? $serviceFromInputs : $this->mixingServicePrice(), 2) : 0.0;
            $totalIncluded = round($minimumTotal + $serviceTotal + (float) $additionalCharges->sum('total'), 2);
        }

        $serviceVat = $this->splitIncludedVat($serviceTotal);
        $suppliesVat = $this->splitIncludedVat($suppliesTotal);

        return [
            'description' => $medicationLines
                ->pluck('description')
                ->filter()
                ->unique()
                ->implode(', ') ?: 'Medicamento de nutrición parenteral',
            'lines' => $medicationLines,
            'supply_lines' => $supplyLines,
            'medication_total' => $medicationTotal,
            'service_base' => $serviceVat['base'],
            'service_vat' => $serviceVat['vat'],
            'service_total' => $serviceTotal,
            'supplies_base' => $suppliesVat['base'],
            'supplies_vat' => $suppliesVat['vat'],
            'supplies_total' => $suppliesTotal,
            'additional_charge_lines' => $additionalCharges,
            'subtotal_before_vat' => round($medicationTotal + $serviceVat['base'] + $suppliesVat['base'], 2),
            'vat_total' => round($serviceVat['vat'] + $suppliesVat['vat'], 2),
            'total_iva_included' => $totalIncluded,
        ];
    }

    public function splitIncludedVat(float $total): array
    {
        if ($total <= 0) {
            return ['base' => 0.0, 'vat' => 0.0, 'total' => 0.0];
        }

        $base = round($total / (1 + self::IVA_RATE), 2);

        return [
            'base' => $base,
            'vat' => round($total - $base, 2),
            'total' => round($total, 2),
        ];
    }

    public function calculateVatFromBase(float $base, bool $applies): float
    {
        if (!$applies || $base <= 0) {
            return 0.0;
        }

        return round($base * self::IVA_RATE, 2);
    }

    public function formatMoney(float $value): string
    {
        return '$' . number_format($value, 2, '.', ',');
    }

    public function parseMoney($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^0-9.\-]/', '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function resolveOncoMedication($medicamento, int $medicineListId): array
    {
        $fallbackDescription = $medicamento->denominacion_snapshot
            ?? $medicamento->medicamentoOnco?->catalog?->denominacion
            ?? $medicamento->nombre_medicamento
            ?? 'Medicamento oncológico';
        $presentationsUsed = $medicamento->presentacionesUsadas ?? collect();
        $catalogId = (int) ($medicamento->medicamentoOnco?->catalog_id ?? 0);
        $configs = $this->oncoConfigs($medicineListId, $catalogId);
        $usedPresentationIds = $presentationsUsed
            ->map(fn ($used) => (int) (
                $used->batch?->medicine_presentation_id
                ?? $used->presentation?->id
                ?? 0
            ))
            ->filter()
            ->values();
        $firstConfig = $configs->first(
            fn ($config) => $usedPresentationIds->contains((int) $config->medicine_presentation_id)
        ) ?? $configs->first();
        $description = trim((string) ($firstConfig?->descripcion_remision ?? ''))
            ?: $fallbackDescription;
        $configuredPricePerMg = $this->oncoPricePerMilligram($firstConfig);
        $chargeBy = $this->resolveOncoChargeMethod($firstConfig, $medicamento);
        $bottleQuantity = $this->resolveBottleQuantity($medicamento, $presentationsUsed, $firstConfig);

        if ($chargeBy === 'mg') {
            $quantity = (float) ($medicamento->dosis ?? 0);
            $snapshotPricePerMg = $medicamento->precio_mg_snapshot;
            $unitPrice = $snapshotPricePerMg !== null
                ? (float) $snapshotPricePerMg
                : ($configuredPricePerMg > 0
                    ? $configuredPricePerMg
                    : (float) ($medicamento->medicamentoOnco?->precio_mg ?? 0));
            $subtotal = round($quantity * $unitPrice, 2);
            $unitLabel = 'mg';
            $vatBreakdown = (bool) ($firstConfig?->iva_desglosado ?? false);
            $vat = $this->calculateVatFromBase($subtotal, $vatBreakdown);
        } elseif ($chargeBy === 'ml') {
            $quantity = (float) $presentationsUsed->sum('volumen_usado_ml');
            $snapshotPricePerMl = $medicamento->precio_ml_snapshot;
            $unitPrice = $snapshotPricePerMl !== null
                ? (float) $snapshotPricePerMl
                : (float) ($firstConfig?->precio_ml_override ?? $firstConfig?->precio ?? 0);
            $subtotal = round($quantity * $unitPrice, 2);
            $unitLabel = 'mL';
            $vatBreakdown = (bool) ($firstConfig?->iva_desglosado ?? false);
            $vat = $this->calculateVatFromBase($subtotal, $vatBreakdown);
        } else {
            [$quantity, $unitPrice, $subtotal, $vat, $vatBreakdown] = $this->resolveBottlePricing(
                $medicamento,
                $presentationsUsed,
                $configs
            );
            $unitLabel = 'frasco';
        }

        $medicamento->setAttribute('denominacion_doc', $description);
        $medicamento->setAttribute('marca_doc', $medicamento->marca_snapshot ?: '—');
        $medicamento->setAttribute('unidad_cobro', $unitLabel);
        $medicamento->setAttribute('cantidad_cobro', $quantity);
        $medicamento->setAttribute('precio_unitario_calculado', round($unitPrice, 4));
        $medicamento->setAttribute('subtotal_calculado', round($subtotal, 2));
        $medicamento->setAttribute('iva_desglosado_doc', $vatBreakdown);
        $medicamento->setAttribute('iva_calculado', $vat);
        $medicamento->setAttribute('subtotal_iva_incluido', round($subtotal + $vat, 2));

        return [
            'description' => $description,
            'quantity' => round($quantity, 2),
            'bottle_quantity' => $bottleQuantity,
            'unit_label' => $unitLabel,
            'unit_price' => round($unitPrice, 4),
            'subtotal' => round($subtotal, 2),
            'vat_breakdown' => $vatBreakdown,
            'vat' => $vat,
            'total_with_vat' => round($subtotal + $vat, 2),
        ];
    }

    private function resolveBottlePricing($medicamento, Collection $presentationsUsed, Collection $configs): array
    {
        $quantity = 0.0;
        $subtotal = 0.0;
        $vat = 0.0;
        $vatBreakdown = false;

        foreach ($presentationsUsed as $used) {
            $units = max((float) ($used->unidades_usadas ?? 0), 1.0);
            $config = $configs->firstWhere('medicine_presentation_id', $used->batch?->medicine_presentation_id)
                ?? $configs->first();
            $lineSubtotal = $used->subtotal;

            if ($lineSubtotal === null) {
                $unitPrice = (float) ($used->precio_frasco_snapshot ?? $config?->precio ?? 0);
                $lineSubtotal = $unitPrice * $units;
            }

            $quantity += $units;
            $subtotal += (float) $lineSubtotal;
            $lineHasVat = (bool) ($config?->iva_desglosado ?? false);
            $vatBreakdown = $vatBreakdown || $lineHasVat;
            $vat += $this->calculateVatFromBase((float) $lineSubtotal, $lineHasVat);
        }

        if ($quantity <= 0) {
            $config = $configs->first();
            $unitPrice = (float) ($config?->precio ?? $medicamento->medicamentoOnco?->precio ?? 0);
            $contentMg = (float) ($config?->cantidad_medicamento ?? $config?->contenido_valor ?? 0);
            $dose = (float) ($medicamento->dosis ?? 0);
            $quantity = $contentMg > 0 && $dose > 0 ? (float) ceil($dose / $contentMg) : 1.0;
            $subtotal = $quantity * $unitPrice;
            $vatBreakdown = (bool) ($config?->iva_desglosado ?? false);
            $vat = $this->calculateVatFromBase($subtotal, $vatBreakdown);

            return [$quantity, $unitPrice, round($subtotal, 2), $vat, $vatBreakdown];
        }

        return [$quantity, $subtotal / $quantity, round($subtotal, 2), round($vat, 2), $vatBreakdown];
    }

    private function resolveBottleQuantity($medicamento, Collection $presentationsUsed, $config): ?float
    {
        $recordedQuantity = (float) $presentationsUsed->sum(function ($used) {
            return max((float) ($used->unidades_usadas ?? 0), 0.0);
        });

        if ($recordedQuantity > 0) {
            return round($recordedQuantity, 2);
        }

        $presentation = $presentationsUsed
            ->map(fn ($used) => $used->batch?->presentation ?? $used->presentation)
            ->filter()
            ->first()
            ?? $medicamento->medicamentoOnco?->catalog?->presentations?->first();
        $contentInMilligrams = MedicinePresentation::contentInMilligramsFrom(
            $config?->contenido_valor ?? $presentation?->contenido_valor,
            $config?->contenido_unidad ?? $presentation?->contenido_unidad,
            $config?->cantidad_medicamento ?? $presentation?->cantidad_medicamento,
            $config?->presentacion ?? $presentation?->presentacion
        );
        $dose = (float) ($medicamento->dosis ?? 0);

        if (!$contentInMilligrams || $dose <= 0) {
            return null;
        }

        return (float) ceil($dose / $contentInMilligrams);
    }

    private function oncoConfigs(int $medicineListId, int $catalogId): Collection
    {
        if ($medicineListId <= 0 || $catalogId <= 0) {
            return collect();
        }

        $cacheKey = $medicineListId . ':' . $catalogId;

        if (!array_key_exists($cacheKey, $this->oncoPresentationConfigs)) {
            $this->oncoPresentationConfigs[$cacheKey] = DB::table('medicine_list_presentation as mlp')
                ->join('medicine_presentations as mp', 'mp.id', '=', 'mlp.medicine_presentation_id')
                ->where('mlp.medicine_list_id', $medicineListId)
                ->where('mp.catalog_id', $catalogId)
                ->where('mp.is_available', true)
                ->orderBy('mp.id')
                ->get([
                    'mlp.medicine_presentation_id',
                    'mlp.charge_by',
                    'mlp.precio',
                    'mlp.precio_mg_override',
                    'mlp.precio_ml_override',
                    'mlp.iva_desglosado',
                    'mlp.descripcion_remision',
                    'mp.presentacion',
                    'mp.contenido_valor',
                    'mp.contenido_unidad',
                    'mp.cantidad_medicamento',
                ]);
        }

        return $this->oncoPresentationConfigs[$cacheKey];
    }

    private function oncoPricePerMilligram($config): float
    {
        if (!$config) {
            return 0.0;
        }

        $override = (float) ($config->precio_mg_override ?? 0);
        if ($override > 0) {
            return $override;
        }

        $bottlePrice = (float) ($config->precio ?? 0);
        $milligrams = MedicinePresentation::contentInMilligramsFrom(
            $config->contenido_valor ?? null,
            $config->contenido_unidad ?? null,
            $config->cantidad_medicamento ?? null,
            $config->presentacion ?? null
        );

        return $bottlePrice > 0 && $milligrams > 0
            ? round($bottlePrice / $milligrams, 4)
            : 0.0;
    }

    private function resolveOncoChargeMethod($config, $medicamento): string
    {
        foreach ([$config?->charge_by, $medicamento->charge_by] as $value) {
            $chargeBy = strtolower(trim((string) $value));

            if (in_array($chargeBy, ['frasco', 'mg'], true)) {
                return $chargeBy;
            }
        }

        return $this->oncoPricePerMilligram($config) > 0 ? 'mg' : 'frasco';
    }

    private function nutritionUnitPrice($item, $listConfig): float
    {
        $snapshot = (float) ($item->precio_ml ?? 0);
        if ($snapshot > 0) {
            return $snapshot;
        }

        return (float) ($listConfig?->precio_ml ?? 0);
    }

    private function nutritionListConfig($item, int $medicineListId): ?object
    {
        $presentationId = (int) ($item->nutrition_medicine_presentation_id ?? 0);

        if ($medicineListId <= 0 || $presentationId <= 0) {
            return null;
        }

        $cacheKey = $medicineListId . ':' . $presentationId;

        if (!array_key_exists($cacheKey, $this->nutritionListConfigs)) {
            $this->nutritionListConfigs[$cacheKey] = DB::table('nutri_medicine_list_items')
                ->where('nutri_medicine_list_id', $medicineListId)
                ->where('nutrition_medicine_presentation_id', $presentationId)
                ->first([
                    'precio_ml',
                    'descripcion_remision',
                ]);
        }

        return $this->nutritionListConfigs[$cacheKey];
    }

    private function nutritionVolume(NutricionalSolicitud $solicitud, $item): float
    {
        $hasOverfill = (float) ($solicitud->solicitud_detail?->sobrellenado_ml ?? 0) > 0;
        $value = $hasOverfill ? $item->valor_sobrellenado : $item->valor_ml;

        return max((float) ($value ?? 0), 0.0);
    }

    private function isNutritionService(string $description): bool
    {
        $normalized = mb_strtolower($description);

        return str_contains($normalized, 'preparación para npt')
            || str_contains($normalized, 'preparacion para npt')
            || str_contains($normalized, 'servicio de mezclado');
    }

    private function isNutritionTaxableSupply(string $description): bool
    {
        $normalized = mb_strtolower($description);

        return str_contains($normalized, 'bolsa eva')
            || str_contains($normalized, 'set de infusión')
            || str_contains($normalized, 'set de infusion');
    }

    private function nutritionSupplyDescription(string $description): string
    {
        if (stripos($description, 'bolsa eva') !== false) {
            return 'Bolsa EVA';
        }

        if (stripos($description, 'set de infusión') !== false || stripos($description, 'set de infusion') !== false) {
            return 'Set de infusión';
        }

        return $description ?: 'Insumo';
    }

    private function additionalCharges(string $type, int $listId): Collection
    {
        if ($listId <= 0) return collect();

        return PriceListAdditionalCharge::query()
            ->where('price_list_type', $type)->where('price_list_id', $listId)->where('is_active', true)
            ->orderBy('name')->get()
            ->map(function ($charge) {
                $total = round((float) $charge->amount, 2);
                $vat = $charge->iva_included ? $this->splitIncludedVat($total) : ['base' => $total, 'vat' => 0.0, 'total' => $total];
                return ['concept_type' => $charge->concept_type, 'description' => $charge->name, 'quantity' => 1.0,
                    'unit_label' => $charge->concept_type === 'Servicio' ? 'servicio' : 'pieza',
                    'unit_price_before_vat' => $vat['base'], 'subtotal_before_vat' => $vat['base'], 'vat' => $vat['vat'], 'total' => $total];
            });
    }

    private function mixingServicePrice(): float
    {
        if ($this->mixingServicePrice === null) {
            $this->mixingServicePrice = round((float) (NutricionalMedicine::query()
                ->where(function ($query) {
                    $query->where('denominacion_generica', 'like', '%SERVICIO DE MEZCLADO%')
                        ->orWhere('denominacion_comercial', 'like', '%SERVICIO DE PREPARACIÓN%');
                })
                ->value('precio_ml') ?? 0), 2);
        }

        return $this->mixingServicePrice;
    }
}
