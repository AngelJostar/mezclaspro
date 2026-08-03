<?php

namespace App\Http\Controllers\Admin\Nutricionales;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use App\Models\Nutricionales\NutritionMedicineCatalog;
use App\Models\Nutricionales\NutritionMedicinePresentation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    public function index()
    {
        $medicines = NutritionMedicineCatalog::with([
            'category',
            'input',
            'presentations' => function ($query) {
                $query->orderBy('denominacion_comercial');
            }
        ])
            ->orderBy('denominacion_generica')
            ->get();

        return view('admin.nutricionales.medicines.index', compact('medicines'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        $inputs = Input::leftJoin('nutrition_medicines_catalog', 'inputs.id', '=', 'nutrition_medicines_catalog.input_id')
            ->whereNull('nutrition_medicines_catalog.id')
            ->select('inputs.*')
            ->orderBy('description')
            ->get();

        return view('admin.nutricionales.medicines.create', compact('categories', 'inputs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'denominacion_generica' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'input_id' => 'required|exists:inputs,id|unique:nutrition_medicines_catalog,input_id',
            'osmolaridad' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',

            'presentations' => 'required|array|min:1',
            'presentations.*.denominacion_comercial' => 'required|string|max:255',
            'presentations.*.fabricante' => 'nullable|string|max:255',
            'presentations.*.presentacion' => 'required|string|max:255',
            'presentations.*.presentacion_ml' => 'nullable|numeric|min:0',
            'presentations.*.is_available' => 'nullable|boolean',
        ]);

        $presentaciones = collect($request->input('presentations', []))
            ->map(fn($presentation) => mb_strtolower(trim((string) ($presentation['denominacion_comercial'] ?? ''))) . '|' . mb_strtolower(trim((string) ($presentation['presentacion'] ?? ''))))
            ->filter(fn($key) => $key !== '|');

        if ($presentaciones->duplicates()->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'presentations' => 'No puedes capturar la misma denominacion comercial con la misma presentacion para este medicamento.',
                ]);
        }

        DB::beginTransaction();

        try {
            $catalog = NutritionMedicineCatalog::create([
                'denominacion_generica' => $request->denominacion_generica,
                'category_id' => $request->category_id,
                'input_id' => $request->input_id,
                'osmolaridad' => $request->osmolaridad,
                'is_active' => $request->boolean('is_active', true),
            ]);

            foreach ($request->presentations as $presentation) {
                NutritionMedicinePresentation::create([
                    'nutrition_medicine_catalog_id' => $catalog->id,
                    'denominacion_comercial' => trim($presentation['denominacion_comercial']),
                    'fabricante' => isset($presentation['fabricante']) ? trim($presentation['fabricante']) : null,
                    'presentacion' => trim($presentation['presentacion']),
                    'presentacion_ml' => $presentation['presentacion_ml'] ?? null,
                    'is_available' => isset($presentation['is_available'])
                        ? (bool) $presentation['is_available']
                        : true,
                ]);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El medicamento genérico y sus presentaciones se han creado con éxito.',
                'icon' => 'success'
            ]);

            return redirect()->route('admin.nutricionales.medicines.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ])->withInput();
        }
    }

    public function edit(NutritionMedicineCatalog $medicine)
    {
        $medicine->load([
            'presentations' => function ($query) {
                $query->orderBy('denominacion_comercial');
            }
        ]);

        $categories = Category::orderBy('name')->get();

        $inputs = Input::orderBy('description')->get();

        return view('admin.nutricionales.medicines.edit', compact('medicine', 'categories', 'inputs'));
    }

    public function update(Request $request, NutritionMedicineCatalog $medicine)
    {
        $request->validate([
            'denominacion_generica' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'input_id' => [
                'required',
                'exists:inputs,id',
                Rule::unique('nutrition_medicines_catalog', 'input_id')->ignore($medicine->id),
            ],
            'osmolaridad' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',

            'presentations' => 'required|array|min:1',
            'presentations.*.denominacion_comercial' => 'required|string|max:255',
            'presentations.*.fabricante' => 'nullable|string|max:255',
            'presentations.*.presentacion' => 'required|string|max:255',
            'presentations.*.presentacion_ml' => 'nullable|numeric|min:0',
            'presentations.*.is_available' => 'nullable|boolean',

        ]);

        $presentaciones = collect($request->input('presentations', []))
            ->map(fn($presentation) => mb_strtolower(trim((string) ($presentation['denominacion_comercial'] ?? ''))) . '|' . mb_strtolower(trim((string) ($presentation['presentacion'] ?? ''))))
            ->filter(fn($key) => $key !== '|');

        if ($presentaciones->duplicates()->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'presentations' => 'No puedes capturar la misma denominacion comercial con la misma presentacion para este medicamento.',
                ]);
        }

        DB::beginTransaction();

        try {
            $medicine->update([
                'denominacion_generica' => $request->denominacion_generica,
                'category_id' => $request->category_id,
                'input_id' => $request->input_id,
                'osmolaridad' => $request->osmolaridad,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Como aún no hay producción, hacemos replace completo de presentaciones
            $medicine->presentations()->delete();

            foreach ($request->presentations as $presentation) {
                NutritionMedicinePresentation::create([
                    'nutrition_medicine_catalog_id' => $medicine->id,
                    'denominacion_comercial' => trim($presentation['denominacion_comercial']),
                    'fabricante' => isset($presentation['fabricante']) ? trim($presentation['fabricante']) : null,
                    'presentacion' => trim($presentation['presentacion']),
                    'presentacion_ml' => $presentation['presentacion_ml'] ?? null,
                    'is_available' => isset($presentation['is_available'])
                        ? (bool) $presentation['is_available']
                        : true,
                ]);
            }

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El medicamento genérico y sus presentaciones se han actualizado con éxito.',
                'icon' => 'success'
            ]);

            return redirect()->route('admin.nutricionales.medicines.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ])->withInput();
        }
    }

    public function destroy(NutritionMedicineCatalog $medicine)
    {
        DB::beginTransaction();

        try {
            $medicine->presentations()->delete();
            $medicine->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El medicamento genérico se eliminó correctamente.',
                'icon' => 'success'
            ]);

            return redirect()->route('admin.nutricionales.medicines.index');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }
}
