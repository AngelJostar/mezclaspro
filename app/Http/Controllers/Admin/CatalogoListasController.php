<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\DiluentPresentation;
use App\Models\Oncologicos\MedicineList;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\MedicinesCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogoListasController extends Controller
{
    private const DEFAULT_CATEGORY = 'oncologicos';

    public const CATEGORIES = [
        'oncologicos' => [
            'label' => 'Oncologicos',
            'icon' => 'fa-solid fa-dna',
            'theme' => 'cyan',
        ],
        'nutricionales' => [
            'label' => 'Nutricionales',
            'icon' => 'fa-solid fa-seedling',
            'theme' => 'emerald',
        ],
        'antibioticos' => [
            'label' => 'Antibioticos',
            'icon' => 'fa-solid fa-capsules',
            'theme' => 'rose',
        ],
        'insumos' => [
            'label' => 'Insumos',
            'icon' => 'fa-solid fa-boxes-stacked',
            'theme' => 'amber',
            'supports_lists' => false,
        ],
    ];

    public function index(Request $request)
    {
        $requestedCategory = (string) $request->query('category', self::DEFAULT_CATEGORY);
        $category = array_key_exists($requestedCategory, self::CATEGORIES)
            ? $requestedCategory
            : self::DEFAULT_CATEGORY;
        return view('admin.catalogo-listas.catalog', [
            'category' => $category,
            'mode' => 'catalogo',
            'categories' => self::CATEGORIES,
            'rows' => $this->catalogRows($category),
        ]);
    }

    public function catalog(string $category)
    {
        $category = $this->normalizeCategory($category);

        return view('admin.catalogo-listas.catalog', [
            'category' => $category,
            'mode' => 'catalogo',
            'categories' => self::CATEGORIES,
            'rows' => $this->catalogRows($category),
        ]);
    }

    public function lists(string $category)
    {
        $category = $this->normalizeCategory($category);

        if (! $this->categorySupportsLists($category)) {
            return redirect()->route('admin.catalogo-listas.catalog', ['category' => $category]);
        }

        return view('admin.catalogo-listas.lists', [
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'rows' => $this->priceListRows($category),
        ]);
    }

    public function showList(string $category, int $list)
    {
        $category = $this->normalizeCategory($category);
        abort_unless($this->categorySupportsLists($category), 404);

        return view('admin.catalogo-listas.show-list', [
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'list' => $this->findPriceList($category, $list),
            'items' => $this->priceListItems($category, $list),
        ]);
    }

    public function createList(string $category)
    {
        $category = $this->normalizeCategory($category);
        abort_unless($this->categorySupportsLists($category), 404);

        return view('admin.catalogo-listas.editor', [
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'catalogs' => $this->editorCatalogs($category),
            'formAction' => $this->storeRoute($category),
            'formMethod' => 'POST',
            'list' => null,
            'pricesByPresentation' => collect(),
        ]);
    }

    public function editList(string $category, int $list)
    {
        $category = $this->normalizeCategory($category);
        abort_unless($this->categorySupportsLists($category), 404);
        $priceList = $this->findPriceList($category, $list);

        return view('admin.catalogo-listas.editor', [
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'catalogs' => $this->editorCatalogs($category),
            'formAction' => $this->updateRoute($category, $priceList),
            'formMethod' => 'PUT',
            'list' => $priceList,
            'pricesByPresentation' => $this->editorPricesByPresentation($category, $priceList),
        ]);
    }

    private function normalizeCategory(string $category): string
    {
        abort_unless(array_key_exists($category, self::CATEGORIES), 404);

        return $category;
    }

    private function categorySupportsLists(string $category): bool
    {
        return (bool) (self::CATEGORIES[$category]['supports_lists'] ?? true);
    }

    private function oncologyDoseMg(MedicinePresentation $presentation): ?float
    {
        return $presentation->contentInMilligrams();
    }

    private function doseLabel(?float $dose, string $unit): string
    {
        if ($dose === null) {
            return '-';
        }

        $formattedDose = rtrim(rtrim(number_format($dose, 4, '.', ','), '0'), '.');

        return $formattedDose . ' ' . $unit;
    }

    private function catalogRows(string $category): Collection
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            return MedicinePresentation::query()
                ->with(['catalog', 'batches'])
                ->where('is_available', true)
                ->whereHas('catalog', fn ($query) => $query->forCategory($category))
                ->get()
                ->sortBy(fn ($presentation) => mb_strtolower(
                    trim((string) $presentation->catalog?->denominacion . ' ' . (string) $presentation->presentacion),
                    'UTF-8'
                ))
                ->values()
                ->map(function (MedicinePresentation $presentation) {
                    $pricedBatches = $presentation->batches
                        ->filter(fn ($batch) => $batch->costo_unitario !== null);

                    $lowest = $pricedBatches
                        ->sortBy(fn ($batch) => (float) $batch->costo_unitario)
                        ->first();

                    $latest = $pricedBatches
                        ->sortByDesc(fn ($batch) => optional($batch->fecha_ingreso)->timestamp ?? optional($batch->created_at)->timestamp ?? 0)
                        ->first();

                    return (object) [
                        'product' => $presentation->catalog?->denominacion ?? '-',
                        'dose' => $this->doseLabel($this->oncologyDoseMg($presentation), 'mg'),
                        'presentation' => $presentation->presentacion ?? '-',
                        'commercial_name' => $presentation->marca ?: '-',
                        'lowest_price' => $lowest?->costo_unitario,
                        'lowest_date' => $lowest?->fecha_ingreso,
                        'last_price' => $latest?->costo_unitario,
                        'last_date' => $latest?->fecha_ingreso,
                        'edit_url' => $presentation->catalog
                            ? route('admin.oncologicos.medicines.catalog.presentations.edit', [
                                'catalog' => $presentation->catalog,
                                'presentation' => $presentation,
                            ])
                            : '#',
                    ];
                });
        }

        if ($category === 'nutricionales') {
            return NutritionMedicinePresentation::query()
                ->with(['catalog.input', 'stocks'])
                ->where('is_available', true)
                ->get()
                ->sortBy(fn ($presentation) => mb_strtolower(
                    trim((string) $presentation->catalog?->denominacion_generica . ' ' . (string) $presentation->denominacion_comercial),
                    'UTF-8'
                ))
                ->values()
                ->map(function (NutritionMedicinePresentation $presentation) {
                    $latest = $presentation->stocks
                        ->sortByDesc(fn ($stock) => optional($stock->fecha_ingreso)->timestamp ?? optional($stock->created_at)->timestamp ?? 0)
                        ->first();

                    return (object) [
                        'product' => $presentation->catalog?->denominacion_generica ?? '-',
                        'dose' => $this->doseLabel(
                            $presentation->presentacion_ml !== null
                                ? (float) $presentation->presentacion_ml
                                : null,
                            'ml'
                        ),
                        'presentation' => $presentation->presentacion ?: '-',
                        'commercial_name' => $presentation->denominacion_comercial ?: '-',
                        'lowest_price' => null,
                        'lowest_date' => null,
                        'last_price' => null,
                        'last_date' => $latest?->fecha_ingreso,
                        'edit_url' => $presentation->catalog
                            ? route('admin.nutricionales.medicines.edit', ['medicine' => $presentation->catalog])
                            : '#',
                    ];
                });
        }

        if ($category === 'insumos') {
            return DiluentPresentation::query()
                ->with('diluent:id,denominacion_generica')
                ->where('is_active', true)
                ->get()
                ->groupBy(function (DiluentPresentation $presentation) {
                    return mb_strtolower(implode('|', [
                        $presentation->diluent_id,
                        trim((string) $presentation->presentacion),
                        trim((string) $presentation->denominacion_comercial),
                        trim((string) $presentation->fabricante),
                        (string) $presentation->volume_ml,
                    ]), 'UTF-8');
                })
                ->map(function (Collection $presentations) {
                    $presentation = $presentations
                        ->sortByDesc(fn (DiluentPresentation $item) => $item->updated_at?->timestamp ?? 0)
                        ->first();

                    return (object) [
                        'product' => $presentation->diluent?->denominacion_generica ?? '-',
                        'dose' => $this->doseLabel($presentation->volume_ml, 'ml'),
                        'presentation' => $presentation->presentacion ?: '-',
                        'commercial_name' => $presentation->denominacion_comercial ?: '-',
                        'manufacturer' => $presentation->fabricante ?: '-',
                        'lowest_price' => null,
                        'lowest_date' => null,
                        'last_price' => null,
                        'last_date' => null,
                        'edit_url' => route('admin.oncologicos.diluent_presentations.edit', [
                            'diluent' => $presentation->diluent_id,
                            'presentation' => $presentation,
                        ]),
                    ];
                })
                ->sortBy(fn ($row) => mb_strtolower($row->product . ' ' . $row->presentation, 'UTF-8'))
                ->values();
        }

        return collect();
    }

    private function priceListRows(string $category): Collection
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            $lists = MedicineList::query()
                ->forCategory($category)
                ->latest()
                ->get();

            $hospitalListColumn = $category === 'antibioticos'
                ? 'antibiotic_medicine_list_id'
                : 'onco_medicine_list_id';

            $hospitalsByList = Hospital::query()
                ->with('instituciones')
                ->whereIn($hospitalListColumn, $lists->pluck('id'))
                ->get()
                ->groupBy($hospitalListColumn);

            return $lists->map(function (MedicineList $list) use ($hospitalsByList, $category) {
                $hospitals = $hospitalsByList->get($list->id, collect());

                return (object) [
                    'id' => $list->id,
                    'name' => $list->name,
                    'institutions' => $hospitals->flatMap->instituciones->pluck('nombre')->unique()->values(),
                    'hospitals' => $hospitals->pluck('name')->unique()->values(),
                    'created_at' => $list->created_at,
                    'view_url' => route('admin.catalogo-listas.lists.show', ['category' => $category, 'list' => $list->id]),
                    'edit_url' => route('admin.catalogo-listas.lists.edit', ['category' => $category, 'list' => $list->id]),
                ];
            });
        }

        if ($category === 'nutricionales') {
            $lists = NutriMedicineList::query()
                ->with('hospitals.instituciones')
                ->latest()
                ->get();

            return $lists->map(function (NutriMedicineList $list) use ($category) {
                return (object) [
                    'id' => $list->id,
                    'name' => $list->name,
                    'institutions' => $list->hospitals->flatMap->instituciones->pluck('nombre')->unique()->values(),
                    'hospitals' => $list->hospitals->pluck('name')->unique()->values(),
                    'created_at' => $list->created_at,
                    'view_url' => route('admin.catalogo-listas.lists.show', ['category' => $category, 'list' => $list->id]),
                    'edit_url' => route('admin.catalogo-listas.lists.edit', ['category' => $category, 'list' => $list->id]),
                ];
            });
        }

        return collect();
    }

    private function findPriceList(string $category, int $list)
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            return MedicineList::query()
                ->forCategory($category)
                ->with(['presentations.catalog', 'distributor'])
                ->findOrFail($list);
        }

        if ($category === 'nutricionales') {
            return NutriMedicineList::query()
                ->with(['items.presentation.catalog', 'distributor'])
                ->findOrFail($list);
        }

        abort(404);
    }

    private function priceListItems(string $category, int $list): Collection
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            $medicineList = MedicineList::query()
                ->forCategory($category)
                ->with('presentations.catalog')
                ->findOrFail($list);

            return $medicineList->presentations
                ->sortBy(fn ($presentation) => (string) $presentation->catalog?->denominacion)
                ->values()
                ->map(function (MedicinePresentation $presentation) {
                    $milligrams = (float) ($presentation->contentInMilligrams() ?: 0);
                    $priceByBottle = $presentation->pivot->precio;

                    if ($priceByBottle === null && $presentation->pivot->precio_mg_override !== null && $milligrams > 0) {
                        $priceByBottle = (float) $presentation->pivot->precio_mg_override * $milligrams;
                    }

                    return (object) [
                        'product' => $presentation->catalog?->denominacion ?? '-',
                        'presentation' => $presentation->presentacion ?? '-',
                        'price_bottle' => $priceByBottle,
                        'unit_price' => $priceByBottle !== null && $milligrams > 0
                            ? ((float) $priceByBottle / $milligrams)
                            : null,
                        'vat_breakdown' => (bool) $presentation->pivot->iva_desglosado,
                    ];
                });
        }

        if ($category === 'nutricionales') {
            $medicineList = NutriMedicineList::query()
                ->with('items.presentation.catalog')
                ->findOrFail($list);

            return $medicineList->items
                ->sortBy(fn ($item) => (string) $item->presentation?->catalog?->denominacion_generica)
                ->values()
                ->map(function ($item) {
                    $milliliters = (float) ($item->presentation?->presentacion_ml ?: 0);
                    $priceMl = (float) $item->precio_ml;

                    return (object) [
                        'product' => $item->presentation?->catalog?->denominacion_generica ?? '-',
                        'presentation' => trim(($item->presentation?->denominacion_comercial ?? '') . ' ' . ($item->presentation?->presentacion ?? '')),
                        'price_bottle' => $milliliters > 0 ? $priceMl * $milliliters : null,
                        'unit_price' => $priceMl,
                    ];
                });
        }

        return collect();
    }

    private function editorCatalogs(string $category): Collection
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            return MedicinesCatalog::query()
                ->forCategory($category)
                ->with(['presentations' => function ($query) {
                    $query->where('is_available', true)
                        ->orderBy('presentacion');
                }])
                ->whereHas('presentations', fn ($query) => $query->where('is_available', true))
                ->orderBy('denominacion')
                ->get();
        }

        if ($category === 'nutricionales') {
            return NutritionMedicineCatalog::query()
                ->with(['input', 'presentations' => function ($query) {
                    $query->where('is_available', true)
                        ->orderBy('denominacion_comercial');
                }])
                ->where('is_active', true)
                ->whereHas('presentations', fn ($query) => $query->where('is_available', true))
                ->orderBy('denominacion_generica')
                ->get();
        }

        return collect();
    }

    private function storeRoute(string $category): ?string
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            return route('admin.oncologicos.medicines.store');
        }

        if ($category === 'nutricionales') {
            return route('admin.nutricionales.nutri-medicine-lists.store');
        }

        return null;
    }

    private function updateRoute(string $category, $list): ?string
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            return route('admin.oncologicos.medicines.update', $list->id);
        }

        if ($category === 'nutricionales') {
            return route('admin.nutricionales.nutri-medicine-lists.update', $list);
        }

        return null;
    }

    private function editorPricesByPresentation(string $category, $list): Collection
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            $list->loadMissing('presentations');

            return $list->presentations
                ->mapWithKeys(function (MedicinePresentation $presentation) {
                    $milligrams = (float) ($presentation->contentInMilligrams() ?: 0);
                    $priceBottle = $presentation->pivot->precio;

                    if ($priceBottle === null && $presentation->pivot->precio_mg_override !== null && $milligrams > 0) {
                        $priceBottle = (float) $presentation->pivot->precio_mg_override * $milligrams;
                    }

                    $priceBottle = (float) ($priceBottle ?? 0);

                    return [
                        $presentation->id => [
                            'price_bottle' => $priceBottle,
                            'price_unit' => $milligrams > 0 ? ($priceBottle / $milligrams) : 0,
                            'vat_breakdown' => (bool) $presentation->pivot->iva_desglosado,
                            'remission_description' => $presentation->pivot->descripcion_remision,
                        ],
                    ];
                });
        }

        if ($category === 'nutricionales') {
            $list->loadMissing('items.presentation');

            return $list->items
                ->mapWithKeys(function ($item) {
                    $milliliters = (float) ($item->presentation?->presentacion_ml ?: 0);
                    $priceMl = (float) $item->precio_ml;

                    return [
                        $item->nutrition_medicine_presentation_id => [
                            'price_bottle' => $milliliters > 0 ? $priceMl * $milliliters : 0,
                            'price_unit' => $priceMl,
                            'remission_description' => $item->descripcion_remision,
                        ],
                    ];
                });
        }

        return collect();
    }
}
