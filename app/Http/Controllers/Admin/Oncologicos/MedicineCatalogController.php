<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\AdministrationRoute;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\MedicinesCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MedicineCatalogController extends Controller
{
    public function index()
    {
        return view('admin.oncologicos.catalog.index');
    }

    public function create()
    {
        $diluents = Diluent::with([
            'presentations' => fn($q) => $q->where('is_active', true)->orderBy('volume_ml')
        ])
            ->orderBy('denominacion_generica')
            ->get(['id', 'denominacion_generica']);

        $routes = AdministrationRoute::orderBy('name')->get(['id', 'name']);

        return view('admin.oncologicos.catalog.create', compact('diluents', 'routes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'denominacion' => 'required|string|max:255|unique:medicines_catalog,denominacion',
            'conc_min' => 'nullable|numeric|min:0',
            'conc_max' => 'nullable|numeric|min:0',
            'requires_infusor' => 'nullable|boolean',
            'diluents' => 'nullable|array',
            'diluents.*' => 'integer|exists:diluents,id',
            'routes' => 'nullable|array',
            'routes.*' => 'integer|exists:administration_routes,id',
        ], [
            'denominacion.unique' => 'Ya existe un medicamento con esa denominación.',
        ]);

        // Importante: la lógica de dosis/volumen ya NO vive en catálogo,
        // ahora se calculará por presentación (medicine_presentations).
        $catalog = MedicinesCatalog::create([
            'denominacion'           => $request->denominacion,
            'conc_min'               => $request->conc_min,
            'conc_max'               => $request->conc_max,
            'requires_infusor'       => $request->boolean('requires_infusor', false),
            // si dejaste cantidad_medicamento/volumen_diluyente en la tabla, puedes
            // inicializarlos como null y usarlos solo de referencia, no para cálculos
            // 'cantidad_medicamento'   => null,
            // 'volumen_diluyente'      => null,
        ]);

        // Relaciones many-to-many
        $catalog->diluents()->sync($request->input('diluents', []));
        $catalog->administrationRoutes()->sync($request->input('routes', []));

        return redirect()
            ->route('admin.oncologicos.medicines.catalog.index')
            ->with('success', 'Medicamento agregado correctamente al catálogo.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $medicamento = MedicinesCatalog::with([
            'diluents:id,denominacion_generica',
            'administrationRoutes:id,name',
        ])->findOrFail($id);

        $diluents = Diluent::with([
            'presentations' => fn($q) => $q->where('is_active', true)->orderBy('volume_ml')
        ])
            ->orderBy('denominacion_generica')
            ->get(['id', 'denominacion_generica']);

        $routes = AdministrationRoute::orderBy('name')->get(['id', 'name']);

        $selectedDiluents = $medicamento->diluents->pluck('id')->all();
        $selectedRoutes   = $medicamento->administrationRoutes->pluck('id')->all();

        return view('admin.oncologicos.catalog.edit', compact(
            'medicamento',
            'diluents',
            'routes',
            'selectedDiluents',
            'selectedRoutes'
        ));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'denominacion'           => 'required|string|max:255',
            'conc_min'               => 'nullable|numeric|min:0',
            'conc_max'               => 'nullable|numeric|min:0',
            'requires_infusor'       => 'nullable|boolean',
            'diluents'               => 'nullable|array',
            'diluents.*'             => 'integer|exists:diluents,id',
            'routes'                 => 'nullable|array',
            'routes.*'               => 'integer|exists:administration_routes,id',
        ]);

        $medicamento = MedicinesCatalog::findOrFail($id);

        $medicamento->update([
            'denominacion'           => $request->denominacion,
            'conc_min'               => $request->conc_min,
            'conc_max'               => $request->conc_max,
            'requires_infusor'       => $request->boolean('requires_infusor', false),
        ]);

        // Relaciones many-to-many
        $medicamento->diluents()->sync($request->input('diluents', []));
        $medicamento->administrationRoutes()->sync($request->input('routes', []));

        return redirect()
            ->route('admin.oncologicos.medicines.catalog.index')
            ->with('success', 'Medicamento actualizado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $medicamento = MedicinesCatalog::findOrFail($id);
        $medicamento->update(['state' => false]);

        return redirect()->route('admin.oncologicos.medicines.catalog.index')
            ->with('success', 'Medicamento deshabilitado correctamente.');
    }
}
