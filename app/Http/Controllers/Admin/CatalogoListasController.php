<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\PriceListAdditionalCharge;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\MedicineList;
use App\Models\Oncologicos\MedicinePresentation;
use App\Models\Oncologicos\MedicinesCatalog;
use App\Models\Warehouse;
use App\Services\PriceListDocumentConfigurationService;
use App\Services\PriceListWarehouseConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogoListasController extends Controller
{
    private const DEFAULT_CATEGORY = 'oncologicos';

    private const ALL_CATEGORY = 'todos';

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
    ];

    public function index(Request $request)
    {
        $requestedCategory = (string) $request->query('category', self::DEFAULT_CATEGORY);
        $browseCategories = $this->browseCategories();
        $category = array_key_exists($requestedCategory, $browseCategories)
            ? $requestedCategory
            : self::DEFAULT_CATEGORY;
        $mode = null;

        return view('admin.catalogo-listas.index', [
            'category' => $category,
            'mode' => $mode,
            'categories' => $browseCategories,
        ]);
    }

    public function catalog(string $category)
    {
        $category = $this->normalizeBrowseCategory($category);

        return view('admin.catalogo-listas.catalog', [
            'category' => $category,
            'mode' => 'catalogo',
            'categories' => $this->browseCategories(),
            'rows' => $this->browseCatalogRows($category),
        ]);
    }

    public function lists(string $category)
    {
        $category = $this->normalizeBrowseCategory($category);

        return view('admin.catalogo-listas.lists', [
            'category' => $category,
            'mode' => 'listas',
            'categories' => $this->browseCategories(),
            'rows' => $this->browsePriceListRows($category),
        ]);
    }

    public function showList(string $category, int $list)
    {
        $category = $this->normalizeCategory($category);

        $priceList = $this->findPriceList($category, $list);

        return view('admin.catalogo-listas.show-list', [
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'list' => $priceList,
            'items' => $this->priceListItems($category, $list),
            'additionalCharges' => PriceListAdditionalCharge::query()
                ->where('price_list_type', $category)
                ->where('price_list_id', $priceList->id)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeAdditionalCharge(Request $request, string $category, int $list)
    {
        $category = $this->normalizeCategory($category);
        $priceList = $this->findPriceList($category, $list);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'concept_type' => ['required', 'in:Servicio,Insumo'],
            'amount' => ['required', 'numeric', 'min:0'],
            'iva_included' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        PriceListAdditionalCharge::create([
            ...$data,
            'price_list_type' => $category,
            'price_list_id' => $priceList->id,
            'iva_included' => $request->boolean('iva_included', true),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('swal', ['icon' => 'success', 'title' => 'Cargo agregado', 'text' => 'Se aplicará automáticamente.']);
    }

    public function createList(string $category)
    {
        $category = $this->normalizeCategory($category);

        return view('admin.catalogo-listas.unified-editor', array_merge([
            'category' => $category,
            'categories' => self::CATEGORIES,
            'catalogsByCategory' => collect(array_keys(self::CATEGORIES))
                ->mapWithKeys(fn (string $key) => [$key => $this->editorCatalogs($key)]),
        ], $this->editorLocationData()));
    }

    private function syncInitialAdditionalCharges(Request $request, string $category, int $listId): void
    {
        foreach ((array) $request->input("additional_charges.{$category}", []) as $charge) {
            $name = trim((string) ($charge['name'] ?? ''));
            if ($name === '') continue;
            PriceListAdditionalCharge::create([
                'price_list_type' => $category, 'price_list_id' => $listId, 'name' => $name,
                'concept_type' => $charge['concept_type'] ?? 'Servicio', 'amount' => $charge['amount'] ?? 0,
                'iva_included' => true, 'is_active' => true,
            ]);
        }
    }

    public function storeUnifiedList(
        Request $request,
        PriceListDocumentConfigurationService $documentConfiguration,
        PriceListWarehouseConfigurationService $warehouseConfiguration
    ) {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'return_category' => ['nullable', 'in:oncologicos,nutricionales,antibioticos'],
            'active_category' => ['nullable', 'in:oncologicos,nutricionales,antibioticos'],
            'has_subdistributor' => ['nullable', 'boolean'],
            'subdistributor_razon_social' => ['nullable', 'string', 'max:255', 'required_if:has_subdistributor,1'],
            'subdistributor_rfc' => ['nullable', 'string', 'max:20', 'required_if:has_subdistributor,1'],
            'subdistributor_direccion' => ['nullable', 'string', 'max:500', 'required_if:has_subdistributor,1'],
            'subdistributor_contacto' => ['nullable', 'string', 'max:255', 'required_if:has_subdistributor,1'],
            'subdistributor_additional_information' => ['nullable', 'string', 'max:10000'],
            'subdistributor_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'has_contract' => ['nullable', 'boolean'],
            'contract_number' => ['nullable', 'string', 'max:255', 'required_if:has_contract,1'],
            'contract_information' => ['nullable', 'string', 'max:10000'],
            'category_items' => ['required', 'array'],
            'category_items.oncologicos' => ['nullable', 'array'],
            'category_items.nutricionales' => ['nullable', 'array'],
            'category_items.antibioticos' => ['nullable', 'array'],
            'category_items.*.*.presentation_id' => ['required', 'integer'],
            'category_items.*.*.selected' => ['nullable', 'boolean'],
            'category_items.*.*.price_bottle' => ['required', 'numeric', 'min:0'],
            'category_items.*.*.price_unit' => ['nullable', 'numeric', 'min:0'],
            'category_items.*.*.charge_by' => ['nullable', 'in:frasco,mg'],
            'category_items.*.*.vat_breakdown' => ['nullable', 'boolean'],
            'category_items.*.*.remission_description' => ['nullable', 'string', 'max:500'],
            'additional_charges' => ['nullable', 'array'],
            'additional_charges.*.*.name' => ['nullable', 'string', 'max:120'],
            'additional_charges.*.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $selectedItems = $this->selectedUnifiedItems($request);
        $this->validateUnifiedSelections($selectedItems, trim((string) $request->input('name')));
        $warehouseAttributes = $warehouseConfiguration->resolve($request);

        try {
            $createdCategories = DB::transaction(function () use (
                $request,
                $selectedItems,
                $warehouseAttributes,
                $documentConfiguration
            ) {
                $created = collect();

                foreach (['oncologicos', 'antibioticos'] as $category) {
                    $items = $selectedItems->get($category, collect());

                    if ($items->isEmpty()) {
                        continue;
                    }

                    $list = $this->createUnifiedMedicineList(
                        $category,
                        $items,
                        $request,
                        $warehouseAttributes
                    );
                    $documentConfiguration->syncSubdistributor($list, $request, 'subdistributors/logos');
                    $this->syncInitialAdditionalCharges($request, $category, $list->id);
                    $created->push($category);
                }

                $nutritionItems = $selectedItems->get('nutricionales', collect());

                if ($nutritionItems->isNotEmpty()) {
                    $list = $this->createUnifiedNutritionList(
                        $nutritionItems,
                        $request,
                        $warehouseAttributes
                    );
                    $documentConfiguration->syncSubdistributor($list, $request, 'subdistributors/logos');
                    $this->syncInitialAdditionalCharges($request, 'nutricionales', $list->id);
                    $created->push('nutricionales');
                }

                return $created;
            });
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'error' => 'No fue posible guardar la lista de precios. Revisa los datos e intenta nuevamente.',
            ]);
        }

        $createdCategories = collect(array_keys(self::CATEGORIES))
            ->filter(fn (string $key) => $createdCategories->contains($key))
            ->values();

        $returnCategory = (string) $request->input('return_category');

        if (! $createdCategories->contains($returnCategory)) {
            $returnCategory = (string) $createdCategories->first();
        }

        $createdLabels = $createdCategories
            ->map(fn (string $key) => self::CATEGORIES[$key]['label'])
            ->implode(', ');

        session()->flash('swal', [
            'title' => 'Lista creada',
            'text' => 'Se guardaron los precios de: '.$createdLabels.'.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.catalogo-listas.lists', [
            'category' => $returnCategory,
        ]);
    }

    public function createBackupList(Request $request, string $category)
    {
        $category = $this->normalizeCategory($category);
        $laboratoryId = $request->integer('laboratory_id');
        $warehouseId = $request->integer('warehouse_id');
        $primaryWarehouseId = $request->integer('primary_warehouse_id');
        $embedded = $request->boolean('embedded');
        $locationData = $this->editorLocationData(null, [
            'laboratory_id' => $laboratoryId,
            'warehouse_id' => $warehouseId,
            'is_backup_list' => true,
            'primary_warehouse_id' => $primaryWarehouseId,
        ]);

        abort_unless(
            $laboratoryId > 0
                && $warehouseId > 0
                && $primaryWarehouseId > 0
                && $warehouseId !== $primaryWarehouseId
                && $locationData['selectedLaboratoryId'] === $laboratoryId
                && $locationData['selectedWarehouseId'] === $warehouseId
                && $locationData['primaryWarehouseId'] === $primaryWarehouseId,
            404
        );

        return view('admin.catalogo-listas.editor', array_merge([
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'catalogs' => $this->editorCatalogs($category),
            'formAction' => $this->storeRoute($category),
            'formMethod' => 'POST',
            'list' => null,
            'pricesByPresentation' => collect(),
        ], $locationData, [
            'embedded' => $embedded,
            'categoryRouteName' => 'admin.catalogo-listas.backup-lists.create',
            'categoryRouteQuery' => array_filter([
                'laboratory_id' => $laboratoryId,
                'warehouse_id' => $warehouseId,
                'primary_warehouse_id' => $primaryWarehouseId,
                'embedded' => $embedded ? 1 : null,
            ], fn ($value) => $value !== null),
        ]));
    }

    public function editList(string $category, int $list)
    {
        $category = $this->normalizeCategory($category);
        $priceList = $this->findPriceList($category, $list);

        $locationData = $this->editorLocationData($priceList);

        if ($locationData['isBackupList']) {
            $locationData['categoryRouteName'] = 'admin.catalogo-listas.backup-lists.create';
            $locationData['categoryRouteQuery'] = [
                'laboratory_id' => $locationData['selectedLaboratoryId'],
                'warehouse_id' => $locationData['selectedWarehouseId'],
                'primary_warehouse_id' => $locationData['primaryWarehouseId'],
            ];
        }

        return view('admin.catalogo-listas.editor', array_merge([
            'category' => $category,
            'mode' => 'listas',
            'categories' => self::CATEGORIES,
            'catalogs' => $this->editorCatalogs($category),
            'formAction' => $this->updateRoute($category, $priceList),
            'formMethod' => 'PUT',
            'list' => $priceList,
            'pricesByPresentation' => $this->editorPricesByPresentation($category, $priceList),
            'additionalCharges' => PriceListAdditionalCharge::query()->where('price_list_type', $category)
                ->where('price_list_id', $priceList->id)->orderBy('name')->get(),
        ], $locationData));
    }

    private function normalizeCategory(string $category): string
    {
        abort_unless(array_key_exists($category, self::CATEGORIES), 404);

        return $category;
    }

    private function normalizeBrowseCategory(string $category): string
    {
        abort_unless(array_key_exists($category, $this->browseCategories()), 404);

        return $category;
    }

    private function browseCategories(): array
    {
        return [
            self::ALL_CATEGORY => [
                'label' => 'Todos',
                'icon' => 'fa-solid fa-layer-group',
                'theme' => 'blue',
            ],
            ...self::CATEGORIES,
        ];
    }

    private function browseCatalogRows(string $category): Collection
    {
        $categories = $category === self::ALL_CATEGORY
            ? array_keys(self::CATEGORIES)
            : [$category];

        return collect($categories)
            ->flatMap(function (string $key) {
                return $this->catalogRows($key)->map(function ($row) use ($key) {
                    $row->category_key = $key;
                    $row->category_label = self::CATEGORIES[$key]['label'];

                    return $row;
                });
            })
            ->values();
    }

    private function browsePriceListRows(string $category): Collection
    {
        $categories = $category === self::ALL_CATEGORY
            ? array_keys(self::CATEGORIES)
            : [$category];

        return collect($categories)
            ->flatMap(function (string $key) {
                return $this->priceListRows($key)->map(function ($row) use ($key) {
                    $row->category_key = $key;
                    $row->category_label = self::CATEGORIES[$key]['label'];

                    return $row;
                });
            })
            ->values();
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

        return $formattedDose.' '.$unit;
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
                    trim((string) $presentation->catalog?->denominacion.' '.(string) $presentation->presentacion),
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
                    trim((string) $presentation->catalog?->denominacion_generica.' '.(string) $presentation->denominacion_comercial),
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

        return collect();
    }

    private function priceListRows(string $category): Collection
    {
        if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
            $lists = MedicineList::query()
                ->forCategory($category)
                ->with(['laboratory', 'warehouse', 'backupWarehouse', 'primaryWarehouse'])
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
                    'central' => $list->laboratory?->nombre ?? '-',
                    'warehouse' => $list->warehouse?->name ?? '-',
                    'backup_warehouse' => $list->backupWarehouse?->name,
                    'primary_warehouse' => $list->primaryWarehouse?->name,
                    'is_backup' => (bool) $list->is_backup,
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
                ->with([
                    'hospitals.instituciones',
                    'laboratory',
                    'warehouse',
                    'backupWarehouse',
                    'primaryWarehouse',
                ])
                ->latest()
                ->get();

            return $lists->map(function (NutriMedicineList $list) use ($category) {
                return (object) [
                    'id' => $list->id,
                    'name' => $list->name,
                    'central' => $list->laboratory?->nombre ?? '-',
                    'warehouse' => $list->warehouse?->name ?? '-',
                    'backup_warehouse' => $list->backupWarehouse?->name,
                    'primary_warehouse' => $list->primaryWarehouse?->name,
                    'is_backup' => (bool) $list->is_backup,
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
                ->with([
                    'presentations.catalog',
                    'distributor',
                    'laboratory',
                    'warehouse',
                    'backupWarehouse',
                    'primaryWarehouse',
                ])
                ->findOrFail($list);
        }

        if ($category === 'nutricionales') {
            return NutriMedicineList::query()
                ->with([
                    'items.presentation.catalog',
                    'distributor',
                    'laboratory',
                    'warehouse',
                    'backupWarehouse',
                    'primaryWarehouse',
                ])
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
                        'charge_by' => $this->normalizeChargeBy($presentation->pivot->charge_by),
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
                        'presentation' => trim(($item->presentation?->denominacion_comercial ?? '').' '.($item->presentation?->presentacion ?? '')),
                        'price_bottle' => $milliliters > 0 ? $priceMl * $milliliters : null,
                        'unit_price' => $priceMl,
                        'charge_by' => null,
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
                    $priceUnit = $presentation->pivot->precio_mg_override;

                    if ($priceUnit === null) {
                        $priceUnit = $milligrams > 0 ? ($priceBottle / $milligrams) : 0;
                    }

                    return [
                        $presentation->id => [
                            'price_bottle' => $priceBottle,
                            'price_unit' => (float) $priceUnit,
                            'charge_by' => $this->normalizeChargeBy($presentation->pivot->charge_by),
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

    private function selectedUnifiedItems(Request $request): Collection
    {
        return collect(array_keys(self::CATEGORIES))
            ->mapWithKeys(function (string $category) use ($request) {
                $items = collect($request->input('category_items.'.$category, []))
                    ->filter(fn ($item) => filter_var(
                        $item['selected'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    ))
                    ->values();

                return [$category => $items];
            });
    }

    private function validateUnifiedSelections(Collection $selectedItems, string $name): void
    {
        if ($selectedItems->every(fn (Collection $items) => $items->isEmpty())) {
            throw ValidationException::withMessages([
                'category_items' => 'Selecciona al menos un producto en alguna categoría.',
            ]);
        }

        foreach ($selectedItems as $category => $items) {
            $presentationIds = $items
                ->pluck('presentation_id')
                ->map(fn ($id) => (int) $id);

            if ($presentationIds->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'category_items.'.$category => 'No puedes repetir una presentación dentro de la misma categoría.',
                ]);
            }

            if ($items->isEmpty()) {
                continue;
            }

            if (in_array($category, ['oncologicos', 'antibioticos'], true)) {
                $validCount = MedicinePresentation::query()
                    ->whereIn('id', $presentationIds)
                    ->where('is_available', true)
                    ->whereHas('catalog', fn ($query) => $query->forCategory($category))
                    ->count();
            } else {
                $validCount = NutritionMedicinePresentation::query()
                    ->whereIn('id', $presentationIds)
                    ->where('is_available', true)
                    ->whereHas('catalog', fn ($query) => $query->where('is_active', true))
                    ->count();
            }

            if ($validCount !== $presentationIds->unique()->count()) {
                throw ValidationException::withMessages([
                    'category_items.'.$category => 'La selección contiene productos que ya no están disponibles.',
                ]);
            }
        }

        if (
            $selectedItems->get('nutricionales', collect())->isNotEmpty()
            && NutriMedicineList::query()->where('name', $name)->exists()
        ) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe una lista nutricional con este nombre.',
            ]);
        }
    }

    private function createUnifiedMedicineList(
        string $category,
        Collection $items,
        Request $request,
        array $warehouseAttributes
    ): MedicineList {
        $presentations = MedicinePresentation::query()
            ->with('catalog')
            ->whereIn('id', $items->pluck('presentation_id'))
            ->get()
            ->keyBy('id');
        $hasContract = $request->boolean('has_contract');
        $defaultChargeBy = $this->normalizeChargeBy($items->first()['charge_by'] ?? null);

        $list = MedicineList::create(array_merge([
            'name' => trim((string) $request->input('name')),
            'description' => $this->nullableTrimmed($request->input('description')),
            'catalog_category' => $category,
            'active_brands' => false,
            'charge_by' => $defaultChargeBy,
            'show_label_lot_expiry' => false,
            'has_contract' => $hasContract,
            'contract_number' => $hasContract
                ? $this->nullableTrimmed($request->input('contract_number'))
                : null,
            'contract_information' => $hasContract
                ? $this->nullableTrimmed($request->input('contract_information'))
                : null,
            'has_mixing_service' => false,
            'mixing_service_price' => 0,
        ], $warehouseAttributes));

        $pivotData = [];

        foreach ($items as $item) {
            $presentationId = (int) $item['presentation_id'];
            $presentation = $presentations->get($presentationId);
            $priceBottle = (float) $item['price_bottle'];
            $chargeBy = $this->normalizeChargeBy($item['charge_by'] ?? null, $defaultChargeBy);
            $unitAmount = (float) ($presentation?->contentInMilligrams() ?: 0);
            $priceUnit = $unitAmount > 0
                ? $priceBottle / $unitAmount
                : (float) ($item['price_unit'] ?? 0);
            $description = $this->nullableTrimmed($item['remission_description'] ?? null)
                ?? trim(implode(' ', array_filter([
                    trim((string) $presentation?->catalog?->denominacion),
                    trim((string) $presentation?->presentacion),
                ])));

            $pivotData[$presentationId] = [
                'charge_by' => $chargeBy,
                'precio' => $priceBottle,
                'precio_mg_override' => $priceUnit,
                'iva_desglosado' => filter_var(
                    $item['vat_breakdown'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                ),
                'descripcion_remision' => $description,
            ];
        }

        $list->presentations()->sync($pivotData);

        return $list;
    }

    private function createUnifiedNutritionList(
        Collection $items,
        Request $request,
        array $warehouseAttributes
    ): NutriMedicineList {
        $presentations = NutritionMedicinePresentation::query()
            ->with('catalog')
            ->whereIn('id', $items->pluck('presentation_id'))
            ->get()
            ->keyBy('id');
        $hasContract = $request->boolean('has_contract');

        $list = NutriMedicineList::create(array_merge([
            'name' => trim((string) $request->input('name')),
            'description' => $this->nullableTrimmed($request->input('description')),
            'is_active' => true,
            'active_brands' => false,
            'has_contract' => $hasContract,
            'contract_number' => $hasContract
                ? $this->nullableTrimmed($request->input('contract_number'))
                : null,
            'contract_information' => $hasContract
                ? $this->nullableTrimmed($request->input('contract_information'))
                : null,
        ], $warehouseAttributes));

        foreach ($items as $item) {
            $presentationId = (int) $item['presentation_id'];
            $presentation = $presentations->get($presentationId);
            $unitAmount = (float) ($presentation?->presentacion_ml ?: 0);
            $priceBottle = (float) $item['price_bottle'];
            $priceUnit = $unitAmount > 0
                ? $priceBottle / $unitAmount
                : (float) ($item['price_unit'] ?? 0);
            $description = $this->nullableTrimmed($item['remission_description'] ?? null)
                ?? trim(implode(' ', array_filter([
                    trim((string) $presentation?->catalog?->denominacion_generica),
                    trim((string) $presentation?->presentacion),
                ])));

            NutriMedicineListItem::create([
                'nutri_medicine_list_id' => $list->id,
                'nutrition_medicine_presentation_id' => $presentationId,
                'precio_ml' => $priceUnit,
                'descripcion_remision' => $description,
            ]);
        }

        return $list;
    }

    private function nullableTrimmed($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeChargeBy($value, string $fallback = 'frasco'): string
    {
        $chargeBy = strtolower(trim((string) $value));

        return in_array($chargeBy, ['frasco', 'mg'], true)
            ? $chargeBy
            : $fallback;
    }

    private function editorLocationData($list = null, array $overrides = []): array
    {
        $laboratories = Laboratory::query()
            ->where('activo', true)
            ->with(['warehouses' => function ($query) {
                $query->where('is_active', true)
                    ->orderBy('name');
            }])
            ->orderBy('nombre')
            ->get();
        $defaultLaboratory = $laboratories->first(function (Laboratory $laboratory) {
            $label = Str::lower(Str::ascii($laboratory->nombre.' '.$laboratory->estado));

            return str_contains($label, 'cdmx') || str_contains($label, 'ciudad de mexico');
        }) ?? $laboratories->first();
        $requestedLaboratoryId = (int) ($overrides['laboratory_id'] ?? $list?->laboratory_id ?? 0);
        $selectedLaboratory = $laboratories->firstWhere('id', $requestedLaboratoryId)
            ?? $defaultLaboratory;
        $warehouses = $selectedLaboratory?->warehouses ?? collect();
        $defaultWarehouse = $this->defaultWarehouse($warehouses);
        $requestedWarehouseId = (int) ($overrides['warehouse_id'] ?? $list?->warehouse_id ?? 0);
        $selectedWarehouse = $warehouses->firstWhere('id', $requestedWarehouseId)
            ?? $defaultWarehouse;
        $isBackupList = (bool) ($overrides['is_backup_list'] ?? $list?->is_backup ?? false);
        $backupEnabled = ! $isBackupList && (bool) ($list?->backup_enabled ?? false);
        $requestedBackupWarehouseId = (int) ($list?->backup_warehouse_id ?? 0);
        $selectedBackupWarehouse = $warehouses
            ->where('id', '!=', $selectedWarehouse?->id)
            ->firstWhere('id', $requestedBackupWarehouseId);
        $requestedPrimaryWarehouseId = (int) (
            $overrides['primary_warehouse_id']
            ?? $list?->primary_warehouse_id
            ?? 0
        );
        $primaryWarehouse = $warehouses
            ->where('id', '!=', $selectedWarehouse?->id)
            ->firstWhere('id', $requestedPrimaryWarehouseId);

        return [
            'laboratories' => $laboratories,
            'selectedLaboratoryId' => (int) ($selectedLaboratory?->id ?? 0),
            'selectedWarehouseId' => (int) ($selectedWarehouse?->id ?? 0),
            'selectedBackupWarehouseId' => (int) ($selectedBackupWarehouse?->id ?? 0),
            'backupEnabled' => $backupEnabled,
            'isBackupList' => $isBackupList,
            'primaryWarehouseId' => (int) ($primaryWarehouse?->id ?? 0),
            'categoryRouteName' => null,
            'categoryRouteQuery' => [],
        ];
    }

    private function defaultWarehouse(Collection $warehouses): ?Warehouse
    {
        return $warehouses->first(function (Warehouse $warehouse) {
            $name = Str::lower(Str::ascii($warehouse->name));

            return str_contains($name, 'central')
                || str_contains($name, 'principal')
                || str_contains($name, 'prodifem');
        }) ?? $warehouses->first();
    }
}
