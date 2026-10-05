<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SalesHospitalAssignmentController extends Controller
{
    public function personnel(Request $request, User $personnel)
    {
        abort_unless($personnel->isSalesperson(), 422, 'Guarda primero el puesto Vendedor para asignar hospitales.');
        return response()->json([
            'name' => trim($personnel->name.' '.$personnel->lastname),
            'institutions' => \App\Models\Institucion::orderBy('nombre')->get(['id', 'nombre']),
            'hospitals' => Hospital::with('instituciones:id,nombre')->orderBy('name')->get()->map(fn ($h) => [
                'id' => $h->id, 'name' => $h->name, 'institutions' => $h->instituciones->modelKeys(),
            ]),
            'selected' => \Illuminate\Support\Facades\DB::table('hospital_salesperson')->where('user_id', $personnel->id)->pluck('hospital_id'),
        ]);
    }

    public function savePersonnel(Request $request, User $personnel)
    {
        abort_unless($personnel->isSalesperson(), 422, 'El personal debe tener el puesto Vendedor.');
        $data = $request->validate(['hospital_ids' => 'present|array', 'hospital_ids.*' => 'integer|distinct|exists:hospitals,id']);
        \Illuminate\Support\Facades\DB::transaction(function () use ($personnel, $data) {
            User::whereKey($personnel->id)->lockForUpdate()->firstOrFail();
            $personnel->belongsToMany(Hospital::class, 'hospital_salesperson')->withTimestamps()->sync($data['hospital_ids']);
        });
        return response()->json(['message' => 'Hospitales asignados correctamente.']);
    }

    public function index()
    {
        return view('admin.sales.hospitals', [
            'hospitals' => Hospital::with('salespeople')->orderBy('name')->paginate(20),
            'sellers' => User::activeSalespeople()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Hospital $hospital)
    {
        $data = $request->validate(['seller_ids' => 'sometimes|array|max:100', 'seller_ids.*' => 'integer|distinct|min:1']);
        $ids = $data['seller_ids'] ?? [];
        if (User::activeSalespeople()->whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['seller_ids' => 'Selecciona vendedores activos.']);
        }
        $hospital->salespeople()->sync($ids);
        return back()->with('status', 'Vendedores del hospital actualizados.');
    }
}
