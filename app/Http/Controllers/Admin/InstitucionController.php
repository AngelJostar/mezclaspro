<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Instituciones\InstitucionGeneralExport;
use App\Exports\Instituciones\InstitucionHospitalDetalleExport;
use App\Exports\Instituciones\InstitucionMezclasOncoExport;
use App\Exports\Instituciones\InstitucionHospitalExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InstitucionController extends Controller
{
    public function index()
    {
        $instituciones = Institucion::orderBy('id', 'desc')->paginate(10);

        return view('admin.instituciones.index', compact('instituciones'));
    }

    public function reportes(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $instituciones = Institucion::withCount('hospitals')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subquery) use ($search) {
                    $subquery->where('nombre', 'like', '%' . $search . '%')
                        ->orWhere('razon_social', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.instituciones.reportes', compact('instituciones', 'search'));
    }

    public function create()
    {
        return view('admin.instituciones.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        Institucion::create($data);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Institucion creada',
            'text' => 'La institucion se registro correctamente.',
        ]);

        return redirect()->route('admin.instituciones.create');
    }

    public function edit(Institucion $institucion)
    {
        return view('admin.instituciones.edit', compact('institucion'));
    }

    public function update(Request $request, Institucion $institucion)
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'razon_social' => ['required', 'string', 'max:255'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ]);

        $institucion->update($data);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Institucion actualizada',
            'text' => 'Los datos de la institucion se actualizaron correctamente.',
        ]);

        return redirect()->route('admin.instituciones.edit', $institucion);
    }

    public function destroy(Institucion $institucion)
    {
        $institucion->delete();

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Institucion eliminada',
            'text' => 'La institucion fue eliminada correctamente.',
        ]);

        return redirect()->route('admin.instituciones.index');
    }

    public function exportarMezclasOnco(Institucion $institucion)
    {
        $filename = 'reporte_mezclas_onco_institucion_' . $institucion->id . '.xlsx';

        return Excel::download(new InstitucionMezclasOncoExport($institucion->id), $filename);
    }

    public function exportarReporteGeneral(Institucion $institucion)
    {
        $filename = 'reporte_general_institucion_' . $institucion->id . '.xlsx';

        return Excel::download(new InstitucionGeneralExport($institucion->id), $filename);
    }

    public function exportarReporteHospital(Institucion $institucion)
    {
        $filename = 'reporte_por_hospital_institucion_' . $institucion->id . '.xlsx';

        return Excel::download(new InstitucionHospitalExport($institucion->id), $filename);
    }

    public function exportarReporteHospitalDetalle(Institucion $institucion)
    {
        $filename = 'reporte_por_hospital_con_detalle_institucion_' . $institucion->id . '.xlsx';

        return Excel::download(new InstitucionHospitalDetalleExport($institucion->id), $filename);
    }

    public function hospitales(Institucion $institucion)
    {
        $institucion->load('hospitals');

        $hospitals = Hospital::orderBy('name')->get();
        $selectedHospitalIds = $institucion->hospitals->pluck('id')->toArray();

        return view('admin.instituciones.hospitals', compact(
            'institucion',
            'hospitals',
            'selectedHospitalIds'
        ));
    }

    public function actualizarHospitales(Request $request, Institucion $institucion)
    {
        $data = $request->validate([
            'hospitals' => ['nullable', 'array'],
            'hospitals.*' => ['integer', 'exists:hospitals,id'],
        ]);

        $institucion->hospitals()->sync($data['hospitals'] ?? []);

        session()->flash('swal', [
            'icon' => 'success',
            'title' => 'Hospitales actualizados',
            'text' => 'Los hospitales vinculados a la institucion se actualizaron correctamente.',
        ]);

        return redirect()->route('admin.instituciones.hospitals', $institucion);
    }
}
