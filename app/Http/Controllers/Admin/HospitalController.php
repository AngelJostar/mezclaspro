<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Hospital\MezclasOncoPorHospitalExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\MedicineList;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HospitalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hospitals = Hospital::with('instituciones')->latest()->get();
        return view('admin.hospitals.index', compact('hospitals'));
    }

    public function create()
    {
        $laboratories = Laboratory::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $nutriMedicineLists = NutriMedicineList::where('is_active', 1)
            ->orderBy('name')
            ->get();

        $oncoMedicineLists = MedicineList::orderBy('name')->get();

        return view('admin.hospitals.create', compact('laboratories', 'nutriMedicineLists', 'oncoMedicineLists'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name_hp' => 'required|string|max:255',
            'adress'  => 'required|string|max:400',
            'laboratory_id' => 'nullable|integer|exists:laboratories,id',
            'nutri_medicine_list_id' => 'nullable|integer|exists:nutri_medicine_lists,id',
            'onco_medicine_list_id' => 'nullable|integer|exists:medicine_lists,id',
        ]);

        $datos = $request->all();
        $datos['name'] = $datos['name_hp'];

        unset($datos['name_hp']);

        Hospital::create($datos);

        session()->flash('swal', [
            'title' => "¡Bien hecho!",
            'text'  => "El hospital se ha creado con éxito.",
            'icon'  => "success"
        ]);

        return redirect()->route('admin.hospitals.index');
    }

    public function edit(Hospital $hospital)
    {
        $laboratories = Laboratory::where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $nutriMedicineLists = NutriMedicineList::where('is_active', 1)
            ->orderBy('name')
            ->get();

        $oncoMedicineLists = MedicineList::orderBy('name')->get();

        return view('admin.hospitals.edit', compact('hospital', 'laboratories', 'nutriMedicineLists', 'oncoMedicineLists'));
    }


    public function update(Request $request, Hospital $hospital)
    {

        $request->validate([
            'name'      => 'required|string|max:255',
            'adress'    => 'required|string|max:400',
            'is_active' => 'required|boolean',
            'laboratory_id' => 'nullable|integer|exists:laboratories,id',
            'nutri_medicine_list_id' => 'nullable|integer|exists:nutri_medicine_lists,id',
            'onco_medicine_list_id' => 'nullable|integer|exists:medicine_lists,id',
        ]);

        $data = $request->only([
            'name',
            'adress',
            'is_active',
            'laboratory_id',
            'nutri_medicine_list_id',
            'onco_medicine_list_id'
        ]);

        $hospital->update($data);

        session()->flash('swal', [
            'title' => "¡Bien hecho!",
            'text'  => "El hospital se ha actualizado con éxito.",
            'icon'  => "success"
        ]);

        return redirect()->route('admin.hospitals.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Hospital $hospital)
    // {
    //     //
    // }

    public function exportarMezclasOnco(Hospital $hospital)
    {
        return Excel::download(
            new MezclasOncoPorHospitalExport($hospital->id),
            'reporte_mezclas_onco_hospital_' . $hospital->id . '.xlsx'
        );
    }
}
