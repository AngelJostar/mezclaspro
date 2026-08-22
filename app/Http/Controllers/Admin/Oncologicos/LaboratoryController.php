<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Http\Request;

class LaboratoryController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.warehouses.index', array_filter([
            'laboratory_id' => $request->integer('laboratory_id') ?: null,
        ]));
    }

    public function create()
    {
        return view('admin.oncologicos.laboratory.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'estado'    => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'activo'    => 'nullable|boolean',
        ]);

        // Si el checkbox no viene, lo forzamos a false
        $validated['activo'] = $request->has('activo');

        $laboratory = Laboratory::create($validated);

        return redirect()
            ->route('admin.warehouses.index', ['laboratory_id' => $laboratory->id])
            ->with('success', 'Central creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    public function edit(Laboratory $laboratory)
    {
        return view('admin.oncologicos.laboratory.edit', compact('laboratory'));
    }


    public function update(Request $request, Laboratory $laboratory)
    {
        $validated = $request->validate([
            'nombre'    => 'required|string|max:255',
            'estado'    => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'activo'    => 'nullable|boolean',
        ]);

        // Checkbox: si no viene en el request, es false
        $validated['activo'] = $request->has('activo');

        $laboratory->update($validated);

        return redirect()
            ->route('admin.warehouses.index', ['laboratory_id' => $laboratory->id])
            ->with('success', 'Central actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
