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
        return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']);
    }

    public function create()
    {
        return redirect()->route('admin.catalogo-listas.products.create', ['category' => 'nutricionales']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'denominacion_generica' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'request_field.unidad' => 'required|string|max:50',
            'request_field.tipo_input' => ['required', Rule::in(['adulto', 'niño', 'ambos'])],
            'request_field.orden_enum' => 'required|integer|min:0',
            'request_field.is_active' => 'required|boolean',
            'request_field.mult' => 'required|numeric|min:0',
            'request_field.div' => 'required|numeric|min:0.00001',
            'osmolaridad' => 'nullable|numeric|min:0',
            'calorias' => 'nullable|numeric|min:0',
            'densidad' => 'nullable|numeric|gt:0|max:100',
            'is_active' => 'nullable|boolean',

            'presentations' => 'required|array|min:1',
            'presentations.*.denominacion_comercial' => 'required|string|max:255',
            'presentations.*.fabricante' => 'nullable|string|max:255',
            'presentations.*.presentacion' => 'required|string|max:255',
            'presentations.*.presentacion_ml' => 'nullable|numeric|min:0',
            'presentations.*.stability_hours' => 'required|integer|min:1|max:8760',
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
            $input = Input::create($this->requestFieldData($request));

            $catalog = NutritionMedicineCatalog::create([
                'denominacion_generica' => trim($request->denominacion_generica),
                'category_id' => $request->category_id,
                'input_id' => $input->id,
                'osmolaridad' => $request->osmolaridad,
                'calorias' => $request->calorias,
                'densidad' => $request->densidad,
                'is_active' => $request->boolean('is_active', true),
            ]);

            foreach ($request->presentations as $presentation) {
                NutritionMedicinePresentation::create([
                    'nutrition_medicine_catalog_id' => $catalog->id,
                    'denominacion_comercial' => trim($presentation['denominacion_comercial']),
                    'fabricante' => isset($presentation['fabricante']) ? trim($presentation['fabricante']) : null,
                    'presentacion' => trim($presentation['presentacion']),
                    'presentacion_ml' => $presentation['presentacion_ml'] ?? null,
                    'stability_hours' => $presentation['stability_hours'] ?? null,
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

            return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ])->withInput();
        }
    }

    public function edit(NutritionMedicineCatalog $medicine)
    {
        $this->ensureSuperAdminCanEdit();

        $medicine->load([
            'input',
            'presentations' => function ($query) {
                $query->orderBy('denominacion_comercial');
            }
        ]);

        $categories = Category::orderBy('name')->get();

        return view('admin.nutricionales.medicines.edit', compact('medicine', 'categories'));
    }

    public function update(Request $request, NutritionMedicineCatalog $medicine)
    {
        $this->ensureSuperAdminCanEdit();

        $request->validate([
            'denominacion_generica' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'request_field.unidad' => 'required|string|max:50',
            'request_field.tipo_input' => ['required', Rule::in(['adulto', 'niño', 'ambos'])],
            'request_field.orden_enum' => 'required|integer|min:0',
            'request_field.is_active' => 'required|boolean',
            'request_field.mult' => 'required|numeric|min:0',
            'request_field.div' => 'required|numeric|min:0.00001',
            'osmolaridad' => 'nullable|numeric|min:0',
            'calorias' => 'nullable|numeric|min:0',
            'densidad' => 'nullable|numeric|gt:0|max:100',
            'is_active' => 'nullable|boolean',

            'presentations' => 'required|array|min:1',
            'presentations.*.denominacion_comercial' => 'required|string|max:255',
            'presentations.*.fabricante' => 'nullable|string|max:255',
            'presentations.*.presentacion' => 'required|string|max:255',
            'presentations.*.presentacion_ml' => 'nullable|numeric|min:0',
            'presentations.*.stability_hours' => 'nullable|integer|min:1|max:8760',
            'presentations.*.is_available' => 'nullable|boolean',
            'presentations.*.id' => 'nullable|integer|exists:nutrition_medicine_presentations,id',

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
            $input = $medicine->input;
            if ($input) {
                $input->update($this->requestFieldData($request));
            } else {
                $input = Input::create($this->requestFieldData($request));
            }

            $medicine->update([
                'denominacion_generica' => trim($request->denominacion_generica),
                'category_id' => $request->category_id,
                'input_id' => $input->id,
                'osmolaridad' => $request->osmolaridad,
                'calorias' => $request->calorias,
                'densidad' => $request->densidad,
                'is_active' => $request->boolean('is_active', true),
            ]);

            $retainedPresentationIds = [];
            foreach ($request->presentations as $presentation) {
                $presentationData = [
                    'nutrition_medicine_catalog_id' => $medicine->id,
                    'denominacion_comercial' => trim($presentation['denominacion_comercial']),
                    'fabricante' => isset($presentation['fabricante']) ? trim($presentation['fabricante']) : null,
                    'presentacion' => trim($presentation['presentacion']),
                    'presentacion_ml' => $presentation['presentacion_ml'] ?? null,
                    'stability_hours' => $presentation['stability_hours'] ?? null,
                    'is_available' => isset($presentation['is_available'])
                        ? (bool) $presentation['is_available']
                        : true,
                ];

                $presentationModel = !empty($presentation['id'])
                    ? $medicine->presentations()->find($presentation['id'])
                    : null;

                if ($presentationModel) {
                    $presentationModel->update($presentationData);
                } else {
                    $presentationModel = NutritionMedicinePresentation::create($presentationData);
                }

                $retainedPresentationIds[] = $presentationModel->id;
            }

            $medicine->presentations()
                ->whereNotIn('id', $retainedPresentationIds)
                ->update(['is_available' => false]);

            DB::commit();

            session()->flash('swal', [
                'title' => '¡Bien hecho!',
                'text' => 'El medicamento genérico y sus presentaciones se han actualizado con éxito.',
                'icon' => 'success'
            ]);

            return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']);
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

            return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'nutricionales']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->withErrors([
                'error' => $e->getMessage()
            ]);
        }
    }

    private function ensureSuperAdminCanEdit(): void
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);
    }

    private function requestFieldData(Request $request): array
    {
        $field = $request->input('request_field', []);

        return [
            'description' => trim((string) $request->input('denominacion_generica')),
            'unidad' => trim((string) ($field['unidad'] ?? 'mL')),
            'is_active' => (bool) ($field['is_active'] ?? true),
            'tipo_input' => $field['tipo_input'] ?? 'ambos',
            'orden_enum' => (int) ($field['orden_enum'] ?? (((int) Input::max('orden_enum')) + 1)),
            'category_id' => (int) $request->input('category_id'),
            'mult' => (float) ($field['mult'] ?? 1),
            'div' => (float) ($field['div'] ?? 1),
        ];
    }
}
