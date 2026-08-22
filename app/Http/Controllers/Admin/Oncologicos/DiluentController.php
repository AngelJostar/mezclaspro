<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Diluent;
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

        return view('admin.oncologicos.diluents.create', compact('selectedWarehouse'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'denominacion_generica' => 'required|string|max:255|unique:diluents,denominacion_generica',
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

        $diluent = Diluent::create([
            'denominacion_generica' => $data['denominacion_generica'],
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
            ->route('admin.oncologicos.diluents.index')
            ->with('success', 'Diluyente creado correctamente.');
    }

    public function edit(Diluent $diluent)
    {
        return view('admin.oncologicos.diluents.edit', compact('diluent'));
    }

    public function update(Request $request, Diluent $diluent)
    {
        $request->validate([
            'denominacion_generica' => 'required|string|max:255|unique:diluents,denominacion_generica,' . $diluent->id,
        ], [
            'denominacion_generica.required' => 'La denominación genérica es obligatoria.',
            'denominacion_generica.unique'   => 'Ya existe un diluyente con esa denominación.',
        ]);

        $diluent->update([
            'denominacion_generica' => $request->denominacion_generica,
        ]);

        return redirect()
            ->route('admin.oncologicos.diluents.index')
            ->with('success', 'Diluyente actualizado correctamente.');
    }

    public function destroy(Diluent $diluent)
    {
        $diluent->delete();

        return redirect()
            ->route('admin.oncologicos.diluents.index')
            ->with('success', 'Diluyente eliminado correctamente.');
    }
}
