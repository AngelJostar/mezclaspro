<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MedicalUnitCatalogController extends Controller
{
    public function npt(Request $request, string $externalCode): JsonResponse
    {
        $this->authorizeCatalogRead($request);

        $hospital = $this->hospital($externalCode);
        $list = $hospital->nutriMedicineList;

        $items = $list
            ? $list->items()
                ->with(['presentation.catalog.category', 'presentation.catalog.input'])
                ->get()
                ->filter(fn ($item) => $item->presentation?->is_available && $item->presentation?->catalog?->is_active)
                ->map(function ($item): array {
                    $presentation = $item->presentation;
                    $catalog = $presentation->catalog;

                    return [
                        'product_code' => $catalog->external_code,
                        'presentation_code' => $presentation->external_code,
                        'generic_name' => $catalog->denominacion_generica,
                        'commercial_name' => $presentation->denominacion_comercial,
                        'manufacturer' => $presentation->fabricante,
                        'presentation' => $presentation->presentacion,
                        'content' => $presentation->presentacion_ml !== null
                            ? ['value' => (float) $presentation->presentacion_ml, 'unit' => 'ml']
                            : null,
                        'category' => $catalog->category?->name,
                        'input' => $catalog->input?->name,
                        'osmolarity' => $catalog->osmolaridad !== null ? (float) $catalog->osmolaridad : null,
                        'price_per_ml' => (float) $item->precio_ml,
                    ];
                })
                ->values()
            : collect();

        return $this->catalogResponse($hospital, 'npt', $list?->updated_at, $items);
    }

    public function oncology(Request $request, string $externalCode): JsonResponse
    {
        $this->authorizeCatalogRead($request);

        $hospital = $this->hospital($externalCode);
        $list = $hospital->oncoMedicineList;

        $items = $list
            ? $list->presentations()
                ->with('catalog')
                ->where('medicine_presentations.is_available', true)
                ->whereHas('catalog', fn ($query) => $query->where('state', true))
                ->get()
                ->map(function ($presentation): array {
                    return [
                        'product_code' => $presentation->catalog->external_code,
                        'presentation_code' => $presentation->external_code,
                        'generic_name' => $presentation->catalog->denominacion,
                        'brand' => $presentation->marca,
                        'manufacturer' => $presentation->fabricante,
                        'presentation' => $presentation->presentacion,
                        'content' => [
                            'value' => (float) $presentation->contenido_valor,
                            'unit' => $presentation->contenido_unidad,
                        ],
                        'requires_infusor' => (bool) $presentation->catalog->requires_infusor,
                        'charge_by' => $presentation->pivot->charge_by ?? $presentation->lists->first()?->charge_by,
                        'price' => $presentation->pivot->precio !== null ? (float) $presentation->pivot->precio : null,
                        'price_per_mg' => $presentation->pivot->precio_mg_override !== null
                            ? (float) $presentation->pivot->precio_mg_override
                            : null,
                    ];
                })
                ->values()
            : collect();

        return $this->catalogResponse($hospital, 'oncology', $list?->updated_at, $items);
    }

    private function authorizeCatalogRead(Request $request): void
    {
        abort_unless($request->user()?->tokenCan('catalogs:read'), 403, 'El token no tiene permiso para consultar catalogos.');
    }

    private function hospital(string $externalCode): Hospital
    {
        return Hospital::query()
            ->with(['nutriMedicineList', 'oncoMedicineList'])
            ->where('external_code', $externalCode)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function catalogResponse(Hospital $hospital, string $type, mixed $updatedAt, Collection $items): JsonResponse
    {
        $versionSource = collect([$hospital->updated_at, $updatedAt])
            ->filter()
            ->sortDesc()
            ->first();

        return response()->json([
            'data' => [
                'medical_unit' => [
                    'external_code' => $hospital->external_code,
                    'name' => $hospital->name,
                ],
                'catalog_type' => $type,
                'catalog_version' => optional($versionSource)->toIso8601String(),
                'items' => $items,
            ],
            'meta' => [
                'count' => $items->count(),
            ],
        ]);
    }
}
