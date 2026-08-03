<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Infusor;
use Illuminate\Http\Request;

class InfusorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $infusores = Infusor::orderBy('id', 'desc')->paginate(10);

        return view('admin.oncologicos.infusores.index', compact('infusores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.oncologicos.infusores.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_generico'   => 'nullable|string|max:255',
            'nombre_comercial'  => 'nullable|string|max:255',
            'precio'            => 'required|numeric|min:0', // o nullable|numeric|min:0
            'lote'              => 'nullable|string|max:100',
            'caducidad'         => 'nullable|date',
            'is_active'         => 'boolean',
        ]);

        // Si quieres que siempre exista:
        $validated['is_active'] = $request->boolean('is_active');

        Infusor::create($validated);

        return redirect()
            ->route('admin.oncologicos.infusores.index')
            ->with('success', 'Infusor creado correctamente');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Infusor $infusor)
    {
        return view('admin.oncologicos.infusores.edit', compact('infusor'));
    }

    public function update(Request $request, Infusor $infusor)
    {
        $data = $request->validate([
            'nombre_generico'  => ['nullable', 'string', 'max:255'],
            'nombre_comercial' => ['nullable', 'string', 'max:255'],
            'precio'           => ['required', 'numeric', 'min:0'], // o ['nullable','numeric','min:0']
            'lote'             => ['nullable', 'string', 'max:100'],
            'caducidad'        => ['nullable', 'date'],
            'is_active'        => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $infusor->update($data);

        return redirect()
            ->route('admin.oncologicos.infusores.index')
            ->with('success', 'Infusor actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Infusor $infusor)
    {
        $infusor->delete();

        return redirect()
            ->route('admin.oncologicos.infusores.index')
            ->with('success', 'Infusor eliminado correctamente.');
    }
}
