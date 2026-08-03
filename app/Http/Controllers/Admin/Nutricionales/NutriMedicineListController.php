<?php

namespace App\Http\Controllers\Admin\Nutricionales;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\NutriDistributor;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Nutricionales\NutriMedicineListItem;
use App\Models\Nutricionales\NutritionMedicineCatalog;
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
            }
        ])
            ->where('is_active', 1)
            ->orderBy('denominacion_generica')
            ->get();

        return view('admin.nutricionales.nutri-medicine-lists.create', compact('catalogs'));
    }

    public function store(Request $request)
    {
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
            'items' => 'required|array|size:' . $totalPresentations,
            'items.*.nutrition_medicine_presentation_id' => 'required|exists:nutrition_medicine_presentations,id',
            'items.*.precio_ml' => 'required|numeric|min:0',
        ], [
            'distributor_name.required_with' => 'Indica el nombre del distribuidor.',
            'distributor_address.required_with' => 'Indica la dirección del distribuidor.',
        ]);

        DB::beginTransaction();

        try {
            $list = NutriMedicineList::create([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'active_brands' => $request->boolean('active_brands', false),
            ]);

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

            foreach ($request->items as $item) {
                NutriMedicineListItem::create([
                    'nutri_medicine_list_id' => $list->id,
                    'nutrition_medicine_presentation_id' => $item['nutrition_medicine_presentation_id'],
                    'precio_ml' => $item['precio_ml'],
                ]);
            }

            DB::commit();

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
            }
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

    public function update(Request $request, NutriMedicineList $nutriMedicineList)
    {
        $totalPresentations = DB::table('nutrition_medicine_presentations')
            ->where('is_available', 1)
            ->count();

        $request->validate([
            'name' => 'required|string|max:255|unique:nutri_medicine_lists,name,' . $nutriMedicineList->id,
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'active_brands' => 'nullable|boolean',
            'distributor_nombre' => 'nullable|string|max:255|required_with:distributor_direccion,distributor_logo',
            'distributor_direccion' => 'nullable|string|max:500|required_with:distributor_nombre,distributor_logo',
            'distributor_logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'distributor_delete' => 'nullable|boolean',
            'items' => 'required|array|size:' . $totalPresentations,
            'items.*.nutrition_medicine_presentation_id' => 'required|exists:nutrition_medicine_presentations,id',
            'items.*.precio_ml' => 'required|numeric|min:0',
        ], [
            'distributor_nombre.required_with' => 'Indica el nombre del distribuidor.',
            'distributor_direccion.required_with' => 'Indica la dirección del distribuidor.',
        ]);

        DB::beginTransaction();

        try {
            $nutriMedicineList->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->boolean('is_active', true),
                'active_brands' => $request->boolean('active_brands', false),
            ]);

            if ($request->boolean('distributor_delete')) {
                if ($nutriMedicineList->distributor) {
                    if (!empty($nutriMedicineList->distributor->logo_path)) {
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
                        if (!empty($distributor->logo_path)) {
                            Storage::disk('public')->delete($distributor->logo_path);
                        }

                        $distributor->logo_path = $request->file('distributor_logo')
                            ->store('nutri-distributors/logos', 'public');
                    }

                    $distributor->save();
                }
            }

            $nutriMedicineList->items()->delete();

            foreach ($request->items as $item) {
                NutriMedicineListItem::create([
                    'nutri_medicine_list_id' => $nutriMedicineList->id,
                    'nutrition_medicine_presentation_id' => $item['nutrition_medicine_presentation_id'],
                    'precio_ml' => $item['precio_ml'],
                ]);
            }

            DB::commit();

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

            if (!empty($nutriMedicineList->distributor?->logo_path)) {
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
                'error' => $e->getMessage()
            ]);
        }
    }
}
