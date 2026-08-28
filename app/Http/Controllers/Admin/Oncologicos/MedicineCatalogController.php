<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\AdministrationRoute;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\MedicinesCatalog;
use App\Models\Oncologicos\MedicinePresentation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineCatalogController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'oncologicos']);
    }

    public function create()
    {
        return redirect()->route('admin.catalogo-listas.products.create', ['category' => 'oncologicos']);
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
            ->route('admin.catalogo-listas.catalog', ['category' => 'oncologicos'])
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
        $this->ensureSuperAdminCanEdit();

        $medicamento = MedicinesCatalog::with([
            'diluents:id,denominacion_generica',
            'administrationRoutes:id,name',
            'presentations' => fn ($query) => $query->orderBy('presentacion'),
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
        $this->ensureSuperAdminCanEdit();

        $request->validate([
            'denominacion'           => 'required|string|max:255',
            'conc_min'               => 'nullable|numeric|min:0',
            'conc_max'               => 'nullable|numeric|min:0',
            'requires_infusor'       => 'nullable|boolean',
            'diluents'               => 'nullable|array',
            'diluents.*'             => 'integer|exists:diluents,id',
            'routes'                 => 'nullable|array',
            'routes.*'               => 'integer|exists:administration_routes,id',
            'presentations' => 'required|array|min:1',
            'presentations.*.id' => 'nullable|integer|exists:medicine_presentations,id',
            'presentations.*.presentacion' => 'required|string|max:255',
            'presentations.*.marca' => 'nullable|string|max:255',
            'presentations.*.fabricante' => 'nullable|string|max:255',
            'presentations.*.contenido_valor' => 'required|numeric|min:0.0001',
            'presentations.*.contenido_unidad' => 'required|in:mg,g,ml,UI,smg',
            'presentations.*.cantidad_medicamento' => 'nullable|numeric|min:0',
            'presentations.*.volumen_diluyente' => 'nullable|numeric|min:0',
            'presentations.*.precio_frasco' => 'nullable|numeric|min:0',
            'presentations.*.stability_hours' => 'nullable|integer|min:0|max:8760',
            'presentations.*.is_available' => 'nullable|boolean',
        ]);

        $medicamento = MedicinesCatalog::findOrFail($id);

        DB::transaction(function () use ($request, $medicamento) {
            $medicamento->update([
                'denominacion' => $request->denominacion,
                'conc_min' => $request->conc_min,
                'conc_max' => $request->conc_max,
                'requires_infusor' => $request->boolean('requires_infusor', false),
            ]);
            $medicamento->diluents()->sync($request->input('diluents', []));
            $medicamento->administrationRoutes()->sync($request->input('routes', []));

            $submittedIds = collect($request->input('presentations', []))
                ->pluck('id')
                ->filter()
                ->map(fn ($presentationId) => (int) $presentationId);

            $omittedPresentations = $medicamento->presentations();
            if ($submittedIds->isNotEmpty()) {
                $omittedPresentations->whereNotIn('id', $submittedIds->all());
            }
            $omittedPresentations->update(['is_available' => false]);

            foreach ($request->input('presentations', []) as $row) {
                $attributes = [
                    'presentacion' => trim($row['presentacion']), 'marca' => blank($row['marca'] ?? null) ? null : trim($row['marca']),
                    'fabricante' => blank($row['fabricante'] ?? null) ? null : trim($row['fabricante']),
                    'contenido_valor' => $row['contenido_valor'], 'contenido_unidad' => $row['contenido_unidad'],
                    'cantidad_medicamento' => $row['cantidad_medicamento'] ?: null,
                    'volumen_diluyente' => $row['volumen_diluyente'] ?: null,
                    'precio_frasco' => $row['precio_frasco'] ?: null,
                    'stability_hours' => $row['stability_hours'] ?: null,
                    'is_available' => isset($row['is_available']) ? (bool) $row['is_available'] : true,
                ];
                $presentation = ! empty($row['id']) ? $medicamento->presentations()->findOrFail($row['id']) : new MedicinePresentation();
                $presentation->fill($attributes);
                $presentation->catalog()->associate($medicamento);
                $presentation->save();
            }
        });

        return redirect()
            ->route('admin.catalogo-listas.catalog', ['category' => 'oncologicos'])
            ->with('success', 'Medicamento actualizado correctamente.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $medicamento = MedicinesCatalog::findOrFail($id);
        $medicamento->update(['state' => false]);

        return redirect()->route('admin.catalogo-listas.catalog', ['category' => 'oncologicos'])
            ->with('success', 'Medicamento deshabilitado correctamente.');
    }

    private function ensureSuperAdminCanEdit(): void
    {
        abort_unless(auth()->user()?->hasRole('Super Admin'), 403);
    }
}
