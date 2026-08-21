<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Hospital\MezclasOncoPorHospitalExport;
use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\Nutricionales\NutriMedicineList;
use App\Models\Oncologicos\Laboratory;
use App\Models\Oncologicos\MedicineList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class HospitalController extends Controller
{
    public function index(Request $request)
    {
        $institutionId = (string) $request->query('institution_id', 'all');
        $instituciones = Institucion::orderBy('nombre')->get(['id', 'nombre']);

        if ($institutionId !== 'all' && ! $instituciones->contains('id', (int) $institutionId)) {
            $institutionId = 'all';
        }

        $hospitals = Hospital::with('instituciones')
            ->when($institutionId !== 'all', function ($query) use ($institutionId) {
                $query->whereHas('instituciones', function ($subquery) use ($institutionId) {
                    $subquery->where('clientes.id', (int) $institutionId);
                });
            })
            ->latest()
            ->get();

        return view('admin.hospitals.index', compact('hospitals', 'instituciones', 'institutionId'));
    }

    public function createForInstitution(Institucion $institucion)
    {
        return $this->createView($institucion);
    }

    private function createView(Institucion $institucion)
    {
        $laboratories = Laboratory::where('activo', 1)->orderBy('nombre')->get();
        $nutriMedicineLists = NutriMedicineList::where('is_active', 1)->orderBy('name')->get();
        $oncoMedicineLists = MedicineList::forCategory('oncologicos')->orderBy('name')->get();
        $antibioticMedicineLists = MedicineList::forCategory('antibioticos')->orderBy('name')->get();
        $formAction = route('admin.instituciones.hospitals.store', $institucion);
        $cancelRoute = route('admin.instituciones.hospitals', $institucion);

        return view('admin.hospitals.create', compact(
            'institucion',
            'laboratories',
            'nutriMedicineLists',
            'oncoMedicineLists',
            'antibioticMedicineLists',
            'formAction',
            'cancelRoute'
        ));
    }

    public function storeForInstitution(Request $request, Institucion $institucion)
    {
        return $this->storeHospital($request, $institucion);
    }

    private function storeHospital(Request $request, Institucion $institucion)
    {
        if ($request->boolean('use_institution_fiscal_data')) {
            $request->merge([
                'rfc' => $request->filled('rfc') ? $request->input('rfc') : $institucion->rfc,
                'phone' => $request->filled('phone') ? $request->input('phone') : $institucion->telefono,
            ]);
        }

        $data = $request->validate([
            'name_hp' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'internal_key' => ['required', 'string', 'max:50', Rule::unique('hospitals', 'internal_key')],
            'unit_type' => ['required', 'string', 'max:100'],
            'care_level' => ['nullable', 'string', 'max:100'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'clues' => ['nullable', 'string', 'max:30'],
            'free_text' => ['nullable', 'string', 'max:10000'],
            'google_maps_url' => ['nullable', 'url:http,https', 'max:2048'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:150'],
            'postal_code' => ['required', 'string', 'max:10'],
            'neighborhood' => ['nullable', 'string', 'max:150'],
            'street_number' => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:150'],
            'contact_position' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150'],
            'reception_hours' => ['nullable', 'string', 'max:100'],
            'operation_days' => ['nullable', 'array'],
            'operation_days.*' => ['string', Rule::in([
                'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo',
            ])],
            'laboratory_id' => ['nullable', 'integer', 'exists:laboratories,id'],
            'nutri_medicine_list_id' => ['nullable', 'integer', 'exists:nutri_medicine_lists,id'],
            'onco_medicine_list_id' => [
                'nullable',
                'integer',
                Rule::exists('medicine_lists', 'id')->where('catalog_category', 'oncologicos'),
            ],
            'antibiotic_medicine_list_id' => [
                'nullable',
                'integer',
                Rule::exists('medicine_lists', 'id')->where('catalog_category', 'antibioticos'),
            ],
            'access_username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username'),
                Rule::unique('users', 'training_username'),
            ],
            'access_password' => ['required', 'string', 'min:8', 'max:20'],
        ], [
            'access_username.regex' => 'El usuario solo puede contener letras, numeros, puntos, guiones y guion bajo.',
            'access_username.unique' => 'El usuario propuesto ya existe. Captura uno diferente.',
            'access_password.min' => 'La contrasena debe tener al menos 8 caracteres.',
        ]);

        $data['name'] = $data['name_hp'];
        $data['adress'] = implode(', ', array_filter([
            $data['street_number'],
            $data['neighborhood'] ?? null,
            $data['municipality'],
            $data['state'],
            $data['postal_code'],
            $data['country'],
        ]));
        $data['is_active'] = $request->boolean('is_active', true);
        $data['service_oncology'] = $request->boolean('service_oncology');
        $data['service_antibiotics'] = $request->boolean('service_antibiotics');
        $data['service_nutrition'] = $request->boolean('service_nutrition');
        unset($data['name_hp']);

        $accessCredentials = [
            'username' => trim($data['access_username']),
            'password' => $data['access_password'],
        ];
        unset($data['access_username'], $data['access_password']);

        $institutionRole = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['Institucion', 'Cliente'])
            ->orderByRaw("CASE WHEN name = 'Institucion' THEN 0 ELSE 1 END")
            ->first();

        if (! $institutionRole) {
            throw ValidationException::withMessages([
                'access_username' => 'No existe el rol Institucion requerido para crear el acceso.',
            ]);
        }

        DB::transaction(function () use ($data, $institucion, $accessCredentials, $institutionRole) {
            $hospital = Hospital::create($data);

            $institucion->hospitals()->syncWithoutDetaching([$hospital->id]);

            $accessUser = User::create([
                'name' => ($data['short_name'] ?? null) ?: $data['name'],
                'lastname' => '',
                'username' => $accessCredentials['username'],
                'password' => Hash::make($accessCredentials['password']),
                'credential_password' => $accessCredentials['password'],
                'training_username' => User::suggestTrainingUsername($accessCredentials['username']),
                'training_password' => Hash::make($accessCredentials['password']),
                'training_credential_password' => $accessCredentials['password'],
                'hospital_id' => $hospital->id,
                'is_active' => $data['is_active'],
            ]);
            $accessUser->assignRole($institutionRole);
        });

        session()->flash('swal', [
            'title' => 'Hospital creado',
            'text' => 'El hospital se creo correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.instituciones.hospitals', $institucion);
    }

    public function edit(Hospital $hospital)
    {
        $laboratories = Laboratory::where('activo', 1)->orderBy('nombre')->get();
        $nutriMedicineLists = NutriMedicineList::where('is_active', 1)->orderBy('name')->get();
        $oncoMedicineLists = MedicineList::forCategory('oncologicos')->orderBy('name')->get();
        $antibioticMedicineLists = MedicineList::forCategory('antibioticos')->orderBy('name')->get();

        return view('admin.hospitals.edit', compact(
            'hospital',
            'laboratories',
            'nutriMedicineLists',
            'oncoMedicineLists',
            'antibioticMedicineLists'
        ));
    }

    public function update(Request $request, Hospital $hospital)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'adress' => ['required', 'string', 'max:400'],
            'free_text' => ['nullable', 'string', 'max:10000'],
            'google_maps_url' => ['nullable', 'url:http,https', 'max:2048'],
            'country' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'laboratory_id' => ['nullable', 'integer', 'exists:laboratories,id'],
            'nutri_medicine_list_id' => ['nullable', 'integer', 'exists:nutri_medicine_lists,id'],
            'onco_medicine_list_id' => [
                'nullable',
                'integer',
                Rule::exists('medicine_lists', 'id')->where('catalog_category', 'oncologicos'),
            ],
            'antibiotic_medicine_list_id' => [
                'nullable',
                'integer',
                Rule::exists('medicine_lists', 'id')->where('catalog_category', 'antibioticos'),
            ],
        ]);

        $hospital->update($request->only([
            'name',
            'adress',
            'free_text',
            'google_maps_url',
            'country',
            'is_active',
            'laboratory_id',
            'nutri_medicine_list_id',
            'onco_medicine_list_id',
            'antibiotic_medicine_list_id',
        ]));

        session()->flash('swal', [
            'title' => 'Hospital actualizado',
            'text' => 'El hospital se actualizo correctamente.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.hospitals.index');
    }

    public function toggleStatus(Request $request, Hospital $hospital)
    {
        $hospital->update([
            'is_active' => ! $hospital->is_active,
        ]);

        return redirect()->route('admin.hospitals.index', [
            'institution_id' => $request->input('institution_id', 'all'),
        ]);
    }

    public function exportarMezclasOnco(Hospital $hospital)
    {
        return Excel::download(
            new MezclasOncoPorHospitalExport($hospital->id),
            'reporte_mezclas_onco_hospital_'.$hospital->id.'.xlsx'
        );
    }
}
