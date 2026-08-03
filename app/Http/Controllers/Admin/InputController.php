<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Category;
use App\Models\Nutricionales\Input;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InputController extends Controller
{
    public function index()
    {
        $inputs = Input::with('category')
            ->orderBy('orden_enum')
            ->orderBy('description')
            ->paginate(30);

        return view('admin.nutricionales.inputs.index', compact('inputs'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.nutricionales.inputs.create', compact('categories'));
    }

    public function store(Request $request)
    {
        Input::create($this->validatedData($request));

        session()->flash('swal', [
            'title' => 'Input creado',
            'text' => 'El input se creo correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.nutricionales.inputs.index');
    }

    public function edit(Input $input)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.nutricionales.inputs.edit', compact('input', 'categories'));
    }

    public function update(Request $request, Input $input)
    {
        $input->update($this->validatedData($request));

        session()->flash('swal', [
            'title' => 'Input actualizado',
            'text' => 'El input se actualizo correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.nutricionales.inputs.index');
    }

    public function destroy(Input $input)
    {
        if ($input->nutritionMedicineCatalog()->exists() || $input->solicitudInputs()->exists()) {
            return back()->withErrors([
                'error' => 'No puedes eliminar este input porque ya esta ligado a medicamentos o solicitudes. Puedes desactivarlo.',
            ]);
        }

        $input->delete();

        session()->flash('swal', [
            'title' => 'Input eliminado',
            'text' => 'El input se elimino correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.nutricionales.inputs.index');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'description' => 'required|string|max:255',
            'unidad' => 'required|string|max:50',
            'is_active' => 'required|boolean',
            'tipo_input' => [
                'required',
                Rule::in(['adulto', 'niño', 'ambos']),
            ],
            'orden_enum' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'mult' => 'required|numeric|min:0',
            'div' => 'required|numeric|min:0.00001',
        ]);
    }
}
