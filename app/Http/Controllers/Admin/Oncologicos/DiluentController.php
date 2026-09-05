<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentCatalogPresentation;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DiluentController extends Controller
{
    public function index()
    {
        return view('admin.oncologicos.diluents.index');
    }


    public function create(Request $request)
    {
        $selectedWarehouse = Warehouse::query()
            ->with('laboratory:id,nombre')
            ->when($request->integer('laboratory_id') > 0, function ($query) use ($request) {
                $query->where('laboratory_id', $request->integer('laboratory_id'));
            })
            ->find($request->integer('warehouse_id'));

        return view('admin.oncologicos.diluents.create', [
            'selectedWarehouse' => $selectedWarehouse,
            'diluents' => Diluent::query()->orderBy('denominacion_generica')->get(['id', 'denominacion_generica']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'generic_mode' => 'required|in:existing,new',
            'diluent_id' => 'required_if:generic_mode,existing|nullable|integer|exists:diluents,id',
            'denominacion_generica' => 'required_if:generic_mode,new|nullable|string|max:255|unique:diluents,denominacion_generica',
            'catalog_presentation.presentation' => 'required|string|max:255',
            'catalog_presentation.commercial_name' => 'nullable|string|max:255',
            'catalog_presentation.manufacturer' => 'nullable|string|max:255',
            'catalog_presentation.volume_ml' => 'nullable|numeric|min:0',
            'laboratory_id' => 'nullable|integer|exists:laboratories,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ], [
            'denominacion_generica.required' => 'La denominación genérica es obligatoria.',
            'denominacion_generica.unique'   => 'Ya existe un diluyente con esa denominación.',
        ]);

        $selectedWarehouse = null;

        if (! empty($data['warehouse_id'])) {
            $selectedWarehouse = Warehouse::query()
                ->whereKey($data['warehouse_id'])
                ->when(! empty($data['laboratory_id']), function ($query) use ($data) {
                    $query->where('laboratory_id', $data['laboratory_id']);
                })
                ->first();

            if (! $selectedWarehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'El almacén seleccionado no pertenece a la central de mezclas.',
                ]);
            }
        }

        if ($data['generic_mode'] === 'new') {
            $genericName = trim($data['denominacion_generica']);
            $duplicateGeneric = Diluent::query()
                ->whereRaw('LOWER(denominacion_generica) = ?', [mb_strtolower($genericName)])
                ->exists();

            if ($duplicateGeneric) {
                throw ValidationException::withMessages([
                    'denominacion_generica' => 'Ya existe un diluyente con esa denominación, aunque use mayúsculas o minúsculas diferentes.',
                ]);
            }
        }

        $diluent = $data['generic_mode'] === 'existing'
            ? Diluent::query()->findOrFail($data['diluent_id'])
            : Diluent::create(['denominacion_generica' => $genericName]);

        if ($diluent->catalogPresentations()
            ->whereRaw('LOWER(presentation) = ?', [mb_strtolower(trim($data['catalog_presentation']['presentation']))])
            ->exists()) {
            throw ValidationException::withMessages([
                'catalog_presentation.presentation' => 'Ya existe esta presentación para el diluyente seleccionado.',
            ]);
        }

        $diluent->catalogPresentations()->create([
            ...$data['catalog_presentation'],
            'is_active' => true,
        ]);

        if ($selectedWarehouse) {
            return redirect()
                ->route('admin.oncologicos.diluent_presentations.create', [
                    'diluent' => $diluent,
                    'laboratory_id' => $selectedWarehouse->laboratory_id,
                    'warehouse_id' => $selectedWarehouse->id,
                ])
                ->with('success', 'Producto creado. Agrega ahora su presentación y existencia inicial.');
        }

        return redirect()
            ->route('admin.catalogo-listas.catalog', 'diluyentes')
            ->with('success', 'Diluyente creado correctamente.');
    }

    public function edit(Request $request, Diluent $diluent)
    {
        $presentation = $diluent->catalogPresentations()->find($request->integer('presentation_id'));
        return view('admin.oncologicos.diluents.edit', compact('diluent', 'presentation'));
    }

    public function update(Request $request, Diluent $diluent)
    {
        $request->validate([
            'denominacion_generica' => 'required|string|max:255|unique:diluents,denominacion_generica,' . $diluent->id,
            'catalog_presentation.presentation' => 'nullable|string|max:255',
            'catalog_presentation.commercial_name' => 'nullable|string|max:255',
            'catalog_presentation.manufacturer' => 'nullable|string|max:255',
            'catalog_presentation.volume_ml' => 'nullable|numeric|min:0',
        ], [
            'denominacion_generica.required' => 'La denominación genérica es obligatoria.',
            'denominacion_generica.unique'   => 'Ya existe un diluyente con esa denominación.',
        ]);

        $genericName = trim($request->denominacion_generica);
        if (Diluent::query()->whereKeyNot($diluent->id)
            ->whereRaw('LOWER(denominacion_generica) = ?', [mb_strtolower($genericName)])
            ->exists()) {
            throw ValidationException::withMessages([
                'denominacion_generica' => 'Ya existe un diluyente con esa denominación, aunque use mayúsculas o minúsculas diferentes.',
            ]);
        }

        $diluent->update([
            'denominacion_generica' => $genericName,
        ]);
        if ($request->filled('catalog_presentation.id')) {
            $presentationData = $request->input('catalog_presentation');
            if (! empty($presentationData['presentation']) && $diluent->catalogPresentations()
                ->whereKeyNot($presentationData['id'])
                ->whereRaw('LOWER(presentation) = ?', [mb_strtolower(trim($presentationData['presentation']))])
                ->exists()) {
                throw ValidationException::withMessages([
                    'catalog_presentation.presentation' => 'Ya existe esta presentación para este diluyente.',
                ]);
            }
            $diluent->catalogPresentations()->whereKey($request->input('catalog_presentation.id'))->update($request->only('catalog_presentation')['catalog_presentation']);
        }

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && str_starts_with($returnTo, url('/'))) {
            return redirect()->to($returnTo)->with('success', 'Diluyente actualizado correctamente.');
        }
        return redirect()->route('admin.warehouses.index')->with('success', 'Diluyente actualizado correctamente.');
    }

    public function destroy(Diluent $diluent)
    {
        $diluent->delete();

        return redirect()
            ->route('admin.oncologicos.diluents.index')
            ->with('success', 'Diluyente eliminado correctamente.');
    }

    public function storeCatalogPresentation(Request $request, Diluent $diluent)
    {
        $data = $request->validate([
            'presentation' => ['required', 'string', 'max:255'],
            'volume_ml' => ['nullable', 'numeric', 'min:0'],
            'commercial_name' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
        ]);
        DiluentCatalogPresentation::create($data + ['diluent_id' => $diluent->id, 'is_active' => true]);
        return back()->with('success', 'Presentación agregada al catálogo. Ya puedes registrar sus lotes desde el almacén.');
    }
}
