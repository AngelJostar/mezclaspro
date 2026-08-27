<?php

namespace App\Http\Controllers\Admin\Nutricionales;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\NutriDistributor;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use App\Services\PriceListDocumentConfigurationService;
use App\Services\PriceListWarehouseConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NutriMedicineListController extends Controller
{
    public function index()
    {
        $lists = NutriMedicineList::withCount('items')
            ->latest()
            ->paginate(15);

        return view('admin.nutricionales.nutri-medicine-lists.index', compact('lists'));
    }

    public function create()
    {
        $catalogs = NutritionMedicineCatalog::with([
            'presentations' => function ($query) {
                $query->where('is_available', 1)
                    ->orderBy('denominacion_comercial');
            },
        ])
            ->where('is_active', 1)
            ->orderBy('denominacion_generica')
            ->get();

        return view('admin.nutricionales.nutri-medicine-lists.create', compact('catalogs'));
    }

    public function store(
        Request $request,
        PriceListDocumentConfigurationService $documentConfiguration,
        PriceListWarehouseConfigurationService $warehouseConfiguration
    ) {
        if ($request->filled('from_catalogo_listas') && $request->boolean('is_backup_list')) {
            $request->merge([
                'has_subdistributor' => false,
                'has_contract' => false,
                'contract_number' => null,
                'contract_information' => null,
            ]);
        }

        $totalPresentations = DB::table('nutrition_medicine_presentations')
            ->where('is_available', 1)
            ->count();

        $request->validate([
            'name' => 'required|string|max:255|unique:nutri_medicine_lists,name',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'active_brands' => 'nullable|boolean',
            'distributor_name' => 'nullable|string|max:255|required_with:distributor_address,distributor_logo',
            'distributor_address' => 'nullable|string|max:500|required_with:distributor_name,distributor_logo',
            'distributor_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'has_subdistributor' => 'nullable|boolean',
            'subdistributor_razon_social' => 'nullable|string|max:255|required_if:has_subdistributor,1',
            'subdistributor_rfc' => 'nullable|string|max:20|required_if:has_subdistributor,1',
            'subdistributor_direccion' => 'nullable|string|max:500|required_if:has_subdistributor,1',
            'subdistributor_contacto' => 'nullable|string|max:255|required_if:has_subdistributor,1',
            'subdistributor_additional_information' => 'nullable|string|max:10000',
            'subdistributor_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'has_contract' => 'nullable|boolean',
            'contract_number' => 'nullable|string|max:255|required_if:has_contract,1',
            'contract_information' => 'nullable|string|max:10000',
            'items' => 'required|array|size:'.$totalPresentations,
            'items.*.nutrition_medicine_presentation_id' => 'required|exists:nutrition_medicine_presentations,id',
            'items.*.precio_ml' => 'required|numeric|min:0',
            'items.*.selected' => 'nullable|boolean',
            'items.*.descripcion_remision' => 'nullable|string|max:500',
        ], [
            'distributor_name.required_with' => 'Indica el nombre del distribuidor.',
            'distributor_address.required_with' => 'Indica la dirección del distribuidor.',
        ]);

        $warehouseAttributes = $request->filled('from_catalogo_listas')
            ? $warehouseConfiguration->resolve($request)
            : [];

        $items = collect($request->input('items', []))
            ->when(
                $request->filled('from_catalogo_listas'),
                fn ($items) => $items->filter(
                    fn ($item) => filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOLEAN)
                )
            )
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Selecciona al menos un producto para conformar la lista de precios.',
            ]);
        }

        $defaultDescriptions = $this->defaultRemissionDescriptions(
            $items->pluck('nutrition_medicine_presentation_id')
        );

        DB::beginTransaction();

        try {
            $list = NutriMedicineList::create(array_merge([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'active_brands' => $request->boolean('active_brands', false),
                'has_contract' => $request->boolean('has_contract'),
                'contract_number' => $request->boolean('has_contract') ? $request->input('contract_number') : null,
                'contract_information' => $request->boolean('has_contract') ? $request->input('contract_information') : null,
            ], $warehouseAttributes));

            if ($request->filled('from_catalogo_listas')) {
                $documentConfiguration->syncSubdistributor($list, $request, 'subdistributors/logos');
            } else {
                $hasDistributor =
                    $request->filled('distributor_name') ||
                    $request->filled('distributor_address') ||
                    $request->hasFile('distributor_logo');

                if ($hasDistributor) {
                    $logoPath = $request->hasFile('distributor_logo')
                        ? $request->file('distributor_logo')->store('nutri-distributors/logos', 'public')
                        : null;

                    NutriDistributor::create([
                        'nutri_medicine_list_id' => $list->id,
                        'nombre' => $request->input('distributor_name'),
                        'direccion' => $request->input('distributor_address'),
                        'logo_path' => $logoPath,
                    ]);
                }
            }

            foreach ($items as $item) {
                $presentationId = (int) $item['nutrition_medicine_presentation_id'];
                $remissionDescription = trim((string) ($item['descripcion_remision'] ?? ''))
                    ?: $defaultDescriptions->get($presentationId);

                NutriMedicineListItem::create([
                    'nutri_medicine_list_id' => $list->id,
                    'nutrition_medicine_presentation_id' => $presentationId,
                    'precio_ml' => $item['precio_ml'],
                    'descripcion_remision' => $remissionDescription,
                ]);
            }

            DB::commit();

            if ($request->filled('from_catalogo_listas')) {
                session()->flash('swal', [
                    'title' => 'Bien hecho',
                    'text' => 'La lista nutricional se guardo con exito.',
                    'icon' => 'success',
                ]);

                return redirect()->route('admin.catalogo-listas.lists', [
                    'category' => $request->input('from_catalogo_listas'),
                ]);
            }

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'La lista nutricional se creó con éxito.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.nutri-medicine-lists.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function show(NutriMedicineList $nutriMedicineList)
    {
        $nutriMedicineList->load('items.presentation.catalog', 'distributor');

        return view('admin.nutricionales.nutri-medicine-lists.show', compact('nutriMedicineList'));
    }

    public function edit(NutriMedicineList $nutriMedicineList)
    {
        $nutriMedicineList->load('items', 'distributor');

        $catalogs = NutritionMedicineCatalog::with([
            'presentations' => function ($query) {
                $query->where('is_available', 1)
                    ->orderBy('denominacion_comercial');
            },
        ])
            ->where('is_active', 1)
            ->orderBy('denominacion_generica')
            ->get();

        $itemsByPresentation = $nutriMedicineList->items
            ->keyBy('nutrition_medicine_presentation_id');

        return view('admin.nutricionales.nutri-medicine-lists.edit', compact(
            'nutriMedicineList',
            'catalogs',
            'itemsByPresentation'
        ));
    }

    public function update(
        Request $request,
        NutriMedicineList $nutriMedicineList,
        PriceListDocumentConfigurationService $documentConfiguration,
        PriceListWarehouseConfigurationService $warehouseConfiguration
    ) {
        if ($request->filled('from_catalogo_listas') && $request->boolean('is_backup_list')) {
            $request->merge([
                'has_subdistributor' => false,
                'has_contract' => false,
                'contract_number' => null,
                'contract_information' => null,
            ]);
        }

        $totalPresentations = DB::table('nutrition_medicine_presentations')
            ->where('is_available', 1)
            ->count();

        $request->validate([
            'name' => 'required|string|max:255|unique:nutri_medicine_lists,name,'.$nutriMedicineList->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'active_brands' => 'nullable|boolean',
            'distributor_nombre' => 'nullable|string|max:255|required_with:distributor_direccion,distributor_logo',
            'distributor_direccion' => 'nullable|string|max:500|required_with:distributor_nombre,distributor_logo',
            'distributor_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'distributor_delete' => 'nullable|boolean',
            'has_subdistributor' => 'nullable|boolean',
            'subdistributor_razon_social' => 'nullable|string|max:255|required_if:has_subdistributor,1',
            'subdistributor_rfc' => 'nullable|string|max:20|required_if:has_subdistributor,1',
            'subdistributor_direccion' => 'nullable|string|max:500|required_if:has_subdistributor,1',
            'subdistributor_contacto' => 'nullable|string|max:255|required_if:has_subdistributor,1',
            'subdistributor_additional_information' => 'nullable|string|max:10000',
            'subdistributor_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'has_contract' => 'nullable|boolean',
            'contract_number' => 'nullable|string|max:255|required_if:has_contract,1',
            'contract_information' => 'nullable|string|max:10000',
            'items' => 'required|array|size:'.$totalPresentations,
            'items.*.nutrition_medicine_presentation_id' => 'required|exists:nutrition_medicine_presentations,id',
            'items.*.precio_ml' => 'required|numeric|min:0',
            'items.*.selected' => 'nullable|boolean',
            'items.*.descripcion_remision' => 'nullable|string|max:500',
        ], [
            'distributor_nombre.required_with' => 'Indica el nombre del distribuidor.',
            'distributor_direccion.required_with' => 'Indica la dirección del distribuidor.',
        ]);

        $warehouseAttributes = $request->filled('from_catalogo_listas')
            ? $warehouseConfiguration->resolve($request)
            : [];

        $items = collect($request->input('items', []))
            ->when(
                $request->filled('from_catalogo_listas'),
                fn ($items) => $items->filter(
                    fn ($item) => filter_var($item['selected'] ?? false, FILTER_VALIDATE_BOOLEAN)
                )
            )
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors([
                'items' => 'Selecciona al menos un producto para conformar la lista de precios.',
            ]);
        }

        $defaultDescriptions = $this->defaultRemissionDescriptions(
            $items->pluck('nutrition_medicine_presentation_id')
        );

        DB::beginTransaction();

        try {
            $listAttributes = [
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'active_brands' => $request->boolean('active_brands', false),
            ];

            if ($request->filled('from_catalogo_listas')) {
                $listAttributes += [
                    'has_contract' => $request->boolean('has_contract'),
                    'contract_number' => $request->boolean('has_contract') ? $request->input('contract_number') : null,
                    'contract_information' => $request->boolean('has_contract') ? $request->input('contract_information') : null,
                ];
                $listAttributes += $warehouseAttributes;
            }

            $nutriMedicineList->update($listAttributes);

            if ($request->filled('from_catalogo_listas')) {
                $documentConfiguration->syncSubdistributor($nutriMedicineList, $request, 'subdistributors/logos');
            } elseif ($request->boolean('distributor_delete')) {
                if ($nutriMedicineList->distributor) {
                    if (! empty($nutriMedicineList->distributor->logo_path)) {
                        Storage::disk('public')->delete($nutriMedicineList->distributor->logo_path);
                    }

                    $nutriMedicineList->distributor->delete();
                }
            } else {
                $distNombre = trim((string) $request->input('distributor_nombre', ''));
                $distDireccion = trim((string) $request->input('distributor_direccion', ''));

                $hasDistributor =
                    $distNombre !== '' ||
                    $distDireccion !== '' ||
                    $request->hasFile('distributor_logo');

                if ($hasDistributor) {
                    $distributor = $nutriMedicineList->distributor ?: new NutriDistributor();
                    $distributor->nutri_medicine_list_id = $nutriMedicineList->id;
                    $distributor->nombre = $distNombre;
                    $distributor->direccion = $distDireccion;

                    if ($request->hasFile('distributor_logo')) {
                        if (! empty($distributor->logo_path)) {
                            Storage::disk('public')->delete($distributor->logo_path);
                        }

                        $distributor->logo_path = $request->file('distributor_logo')
                            ->store('nutri-distributors/logos', 'public');
                    }

                    $distributor->save();
                }
            }

            $existingDescriptions = $nutriMedicineList->items()
                ->pluck('descripcion_remision', 'nutrition_medicine_presentation_id');

            $nutriMedicineList->items()->delete();

            foreach ($items as $item) {
                $presentationId = (int) $item['nutrition_medicine_presentation_id'];
                $remissionDescription = trim((string) ($item['descripcion_remision'] ?? ''))
                    ?: trim((string) $existingDescriptions->get($presentationId, ''))
                    ?: $defaultDescriptions->get($presentationId);

                NutriMedicineListItem::create([
                    'nutri_medicine_list_id' => $nutriMedicineList->id,
                    'nutrition_medicine_presentation_id' => $presentationId,
                    'precio_ml' => $item['precio_ml'],
                    'descripcion_remision' => $remissionDescription,
                ]);
            }

            DB::commit();

            if ($request->filled('from_catalogo_listas')) {
                session()->flash('swal', [
                    'title' => 'Bien hecho',
                    'text' => 'La lista nutricional se guardo con exito.',
                    'icon' => 'success',
                ]);

                return redirect()->route('admin.catalogo-listas.lists', [
                    'category' => $request->input('from_catalogo_listas'),
                ]);
            }

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'La lista nutricional se actualizó con éxito.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.nutri-medicine-lists.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(NutriMedicineList $nutriMedicineList)
    {
        try {
            $nutriMedicineList->load('distributor');

            if (! empty($nutriMedicineList->distributor?->logo_path)) {
                Storage::disk('public')->delete($nutriMedicineList->distributor->logo_path);
            }

            $nutriMedicineList->delete();

            session()->flash('swal', [
                'title' => 'Eliminada',
                'text' => 'La lista nutricional se eliminó con éxito.',
                'icon' => 'success',
            ]);

            return redirect()->route('admin.nutricionales.nutri-medicine-lists.index');
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function defaultRemissionDescriptions($presentationIds)
    {
        return NutritionMedicinePresentation::query()
            ->with('catalog:id,denominacion_generica')
            ->whereIn('id', collect($presentationIds)->filter()->unique()->values())
            ->get()
            ->mapWithKeys(function (NutritionMedicinePresentation $presentation) {
                $description = trim(implode(' ', array_filter([
                    trim((string) $presentation->catalog?->denominacion_generica),
                    trim((string) $presentation->presentacion),
                ])));

                return [$presentation->id => $description];
            });
    }
}
