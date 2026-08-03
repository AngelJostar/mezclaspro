<?php

namespace App\Http\Controllers\Admin\Oncologicos;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Diluent;
use Illuminate\Http\Request;

class DiluentController extends Controller
{
    public function index()
    {
        return view('admin.oncologicos.diluents.index');
    }


    public function create()
    {
        return view('admin.oncologicos.diluents.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'denominacion_generica' => 'required|string|max:255|unique:diluents,denominacion_generica',
        ], [
            'denominacion_generica.required' => 'La denominación genérica es obligatoria.',
            'denominacion_generica.unique'   => 'Ya existe un diluyente con esa denominación.',
        ]);

        Diluent::create([
            'denominacion_generica' => $request->denominacion_generica,
        ]);

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
