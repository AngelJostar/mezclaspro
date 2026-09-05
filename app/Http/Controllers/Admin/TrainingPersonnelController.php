<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Oncologicos\Laboratory;
use App\Models\PersonnelProfile;
use App\Models\User;
use App\Support\AdminMenuAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class TrainingPersonnelController extends Controller
{
    public function index(Request $request): View
    {
        $laboratories = Laboratory::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'estado', 'direccion', 'activo']);

        $selectedLaboratory = $request->integer('laboratory_id') > 0
            ? $laboratories->firstWhere('id', $request->integer('laboratory_id'))
            : null;

        $laboratories->each(function (Laboratory $laboratory): void {
            $laboratory->setAttribute(
                'personnel_count',
                $this->personnelUsersQuery((int) $laboratory->id)->count()
            );
        });

        $persistedPersonnel = $this->personnelUsersQuery($selectedLaboratory?->id)
            ->select([
                'id',
                'name',
                'lastname',
                'username',
                'password',
                'credential_password',
                'training_username',
                'training_password',
                'training_credential_password',
                'is_active',
                'hospital_id',
                'created_at',
                'updated_at',
            ])
            ->with([
                'personnelProfile.laboratory:id,nombre',
                'hospital:id,laboratory_id',
                'hospital.laboratory:id,nombre',
                'roles:id,name',
                'permissions:id,name',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (User $user) {
                $profile = $user->personnelProfile;
                $name = $this->normalizePersonName(trim($user->name.' '.$user->lastname));
                $positions = collect($profile?->positions ?? [])
                    ->filter()
                    ->values()
                    ->map(fn (string $position, int $index) => [
                        'name' => $position,
                        'current' => $index === 0,
                    ])
                    ->all();

                return [
                    'initials' => $this->initials($name),
                    'name' => $name,
                    'completedPrograms' => [],
                    'currentPrograms' => [],
                    'positions' => $positions,
                    'hireDate' => $profile?->hire_date?->format('d/m/Y') ?? 'Sin fecha',
                    'sortDate' => $profile?->hire_date?->getTimestamp() ?? 0,
                    'sortId' => $user->id,
                    'examScores' => [],
                    'department' => $profile?->department ?: 'Sin departamento',
                    'employmentStatus' => $profile?->employment_status ?: 'hired',
                    'activity' => $user->updated_at?->format('d/m/Y H:i') ?? 'Sin actividad',
                    'avatar' => ['sky', 'mint', 'rose', 'violet', 'cyan'][$user->id % 5],
                    'central' => $profile?->laboratory?->nombre
                        ?? $user->hospital?->laboratory?->nombre
                        ?? 'Sin central asignada',
                    'user' => $user,
                ];
            })
            ->all();

        return view('admin.capacitaciones.index', [
            'persistedPersonnel' => $persistedPersonnel,
            'laboratories' => $laboratories,
            'selectedLaboratory' => $selectedLaboratory,
            'jobCatalog' => $this->jobCatalog(),
            'isSuperAdmin' => Auth::user()?->hasRole('Super Admin') ?? false,
            'roleAccessTree' => AdminMenuAccess::tree(),
            'roleOptions' => AdminMenuAccess::roleOptions(),
        ]);
    }

    public function students(): View
    {
        $assignmentPersonnel = $this->personnelUsersQuery()
            ->select(['id', 'name', 'lastname'])
            ->orderBy('name')
            ->orderBy('lastname')
            ->get()
            ->map(fn (User $user): array => [
                'id' => (string) $user->id,
                'name' => $this->normalizePersonName(trim($user->name.' '.$user->lastname)),
            ])
            ->filter(fn (array $person): bool => $person['name'] !== '')
            ->values()
            ->all();

        return view('admin.capacitaciones.index', [
            'assignmentPersonnel' => $assignmentPersonnel,
        ]);
    }

    public function edit(User $personnel): JsonResponse
    {
        $personnel = $this->personnelUsersQuery()->findOrFail($personnel->id);
        $profile = $personnel->personnelProfile;
        $laboratory = $profile?->laboratory ?? $personnel->hospital?->laboratory;

        return response()->json([
            'action' => route('admin.capacitaciones.personal.update', $personnel),
            'name' => $this->normalizePersonName(trim($personnel->name.' '.$personnel->lastname)),
            'fields' => [
                'first_name' => $personnel->name,
                // Legacy accounts do not store the two surnames separately.
                'paternal_surname' => $profile?->paternal_surname ?? $personnel->lastname,
                'maternal_surname' => $profile?->maternal_surname,
                'phone' => $profile?->phone,
                'personal_email' => $profile?->personal_email,
                'laboratory_id' => $laboratory?->id,
                'department' => $profile?->department,
                'hire_date' => $profile?->hire_date?->toDateString(),
                'positions' => $profile?->positions ?? [],
                'prior_experience' => $profile?->prior_experience,
                'additional_information' => $profile?->additional_information,
            ],
            'laboratory' => $laboratory ? ['id' => $laboratory->id, 'name' => $laboratory->nombre] : null,
            'cv_name' => $profile?->cv_original_name,
        ]);
    }

    public function update(Request $request, User $personnel): JsonResponse
    {
        $personnel = $this->personnelUsersQuery()->findOrFail($personnel->id);
        $profile = $personnel->personnelProfile;
        $currentLaboratoryId = $profile?->laboratory_id ?? $personnel->hospital?->laboratory_id;
        $previousName = $this->normalizePersonName(trim($personnel->name.' '.$personnel->lastname));

        if (is_string($request->input('personal_email'))) {
            $request->merge(['personal_email' => Str::lower(trim($request->input('personal_email')))]);
        }

        // Validate scalar input before applying the same name normalization used for new personnel.
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'paternal_surname' => ['required', 'string', 'max:120'],
            'maternal_surname' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'personal_email' => ['required', 'email:rfc', 'max:255',
                Rule::unique('personnel_profiles', 'personal_email')->ignore($profile?->id)],
            'laboratory_id' => ['required', 'integer', Rule::exists('laboratories', 'id')
                ->where(fn ($query) => $query->where('activo', true)->orWhere('id', $currentLaboratoryId))],
            'department' => ['required', Rule::in(['Administracion', 'Almacen', 'Calidad', 'Operaciones', 'Produccion'])],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'positions' => ['required', 'array', 'min:1'],
            'positions.*' => ['required', 'string', Rule::in(array_merge($this->jobNames(), $profile?->positions ?? []))],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'prior_experience' => ['nullable', 'string', 'max:3000'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
        ], [
            'positions.required' => 'Selecciona al menos un puesto.',
            'positions.*.in' => 'Selecciona puestos del catalogo.',
            'personal_email.unique' => 'Ya existe una persona registrada con este correo.',
            'laboratory_id.required' => 'Selecciona una central.',
            'laboratory_id.exists' => 'Selecciona una central activa.',
        ]);

        $cvPath = $request->file('cv')?->store('personnel/cv', 'local');
        $oldCvPath = $profile?->cv_path;

        try {
            DB::transaction(function () use ($personnel, $validated, $request, $cvPath) {
                $personnel = User::query()->lockForUpdate()->findOrFail($personnel->id);
                $profile = $personnel->personnelProfile()->firstOrNew();
                $paternalSurname = $this->normalizePersonName($validated['paternal_surname']);
                $maternalSurname = $this->normalizePersonName($validated['maternal_surname'] ?? null);

                $personnel->fill([
                    'name' => $this->normalizePersonName($validated['first_name']),
                    'lastname' => trim($paternalSurname.' '.$maternalSurname),
                ])->save();
                $personnel->touch();

                // Keep the existing primary position first when it remains selected.
                $positions = array_values(array_unique(array_merge(
                    array_intersect($profile->positions ?? [], $validated['positions']),
                    $validated['positions']
                )));

                $profile->fill([
                    'laboratory_id' => $validated['laboratory_id'],
                    'paternal_surname' => $paternalSurname,
                    'maternal_surname' => $maternalSurname ?: null,
                    'phone' => $validated['phone'] ?? null,
                    'personal_email' => Str::lower(trim($validated['personal_email'])),
                    'department' => $validated['department'],
                    'hire_date' => $validated['hire_date'],
                    'positions' => $positions,
                    'prior_experience' => $validated['prior_experience'] ?? null,
                    'additional_information' => $validated['additional_information'] ?? null,
                ]);

                if (! $profile->exists) {
                    $profile->employment_status = $personnel->is_active ? 'hired' : 'inactive';
                    $profile->force_password_change = false;
                }

                if ($cvPath) {
                    $profile->cv_path = $cvPath;
                    $profile->cv_original_name = $request->file('cv')->getClientOriginalName();
                }

                $personnel->personnelProfile()->save($profile);
            });
        } catch (\Throwable $exception) {
            if ($cvPath) {
                Storage::disk('local')->delete($cvPath);
            }
            throw $exception;
        }

        if ($cvPath && $oldCvPath && $oldCvPath !== $cvPath) {
            Storage::disk('local')->delete($oldCvPath);
        }

        $request->session()->flash('swal', [
            'title' => 'Personal actualizado',
            'text' => 'La informacion del personal se actualizo correctamente.',
            'icon' => 'success',
        ]);
        $personnel->refresh();

        return response()->json([
            'previous_name' => $previousName,
            'name' => $this->normalizePersonName(trim($personnel->name.' '.$personnel->lastname)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maternalSurname = $this->normalizePersonName($request->input('maternal_surname'));

        $request->merge([
            'first_name' => $this->normalizePersonName($request->input('first_name')),
            'paternal_surname' => $this->normalizePersonName($request->input('paternal_surname')),
            'maternal_surname' => $maternalSurname !== '' ? $maternalSurname : null,
            'username' => Str::lower(trim((string) $request->input('username'))),
            'personal_email' => Str::lower(trim((string) $request->input('personal_email'))),
        ]);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'paternal_surname' => ['required', 'string', 'max:120'],
            'maternal_surname' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'personal_email' => ['required', 'email:rfc', 'max:255', 'unique:personnel_profiles,personal_email'],
            'laboratory_id' => [
                'required',
                'integer',
                Rule::exists('laboratories', 'id')->where('activo', true),
            ],
            'department' => ['required', 'string', Rule::in([
                'Administracion',
                'Almacen',
                'Calidad',
                'Operaciones',
                'Produccion',
            ])],
            'hire_date' => ['required', 'date', 'before_or_equal:today'],
            'employment_status' => ['required', Rule::in(['hired', 'inactive'])],
            'positions' => ['required', 'array', 'min:1'],
            'positions.*' => ['required', 'string', Rule::in($this->jobNames())],
            'username' => [
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('users', 'username'),
                Rule::unique('users', 'training_username'),
            ],
            'password' => ['required', 'string', 'min:6', 'max:50'],
            'cv' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
            'prior_experience' => ['nullable', 'string', 'max:3000'],
            'additional_information' => ['nullable', 'string', 'max:5000'],
        ], [
            'positions.required' => 'Selecciona al menos un puesto.',
            'positions.min' => 'Selecciona al menos un puesto.',
            'username.regex' => 'El usuario solo puede contener minusculas, numeros, puntos, guiones y guiones bajos.',
            'personal_email.unique' => 'Ya existe una persona registrada con este correo.',
            'laboratory_id.required' => 'Asigna una central antes de crear el personal.',
            'laboratory_id.exists' => 'Selecciona una central activa.',
        ]);

        $cvPath = $request->file('cv')?->store('personnel/cv', 'local');

        try {
            DB::transaction(function () use ($validated, $request, $cvPath) {
                $plainPassword = $validated['password'];
                $lastName = trim($validated['paternal_surname'].' '.($validated['maternal_surname'] ?? ''));
                $trainingUsername = User::suggestTrainingUsername($validated['username']);

                $user = User::create([
                    'name' => trim($validated['first_name']),
                    'lastname' => $lastName,
                    'username' => $validated['username'],
                    'password' => Hash::make($plainPassword),
                    'credential_password' => $plainPassword,
                    'training_username' => $trainingUsername,
                    'training_password' => Hash::make($plainPassword),
                    'training_credential_password' => $plainPassword,
                    'is_active' => $validated['employment_status'] === 'hired',
                ]);

                $role = Role::query()->firstOrCreate([
                    'name' => 'Capacitacion',
                    'guard_name' => 'web',
                ]);
                $user->syncRoles([$role]);

                $user->personnelProfile()->create([
                    'laboratory_id' => $validated['laboratory_id'],
                    'paternal_surname' => trim($validated['paternal_surname']),
                    'maternal_surname' => filled($validated['maternal_surname'] ?? null)
                        ? trim($validated['maternal_surname'])
                        : null,
                    'phone' => $validated['phone'] ?? null,
                    'personal_email' => $validated['personal_email'],
                    'positions' => array_values(array_unique($validated['positions'])),
                    'department' => $validated['department'],
                    'hire_date' => $validated['hire_date'],
                    'employment_status' => $validated['employment_status'],
                    'force_password_change' => false,
                    'cv_path' => $cvPath,
                    'cv_original_name' => $request->file('cv')?->getClientOriginalName(),
                    'prior_experience' => $validated['prior_experience'] ?? null,
                    'additional_information' => $validated['additional_information'] ?? null,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($cvPath) {
                Storage::disk('local')->delete($cvPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.capacitaciones.personal')
            ->with('swal', [
                'title' => 'Personal agregado',
                'text' => 'La persona y sus accesos se registraron correctamente.',
                'icon' => 'success',
            ]);
    }

    private function initials(string $name): string
    {
        return Str::of($name)
            ->split('/\s+/')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    /**
     * @return Builder<User>
     */
    private function personnelUsersQuery(?int $laboratoryId = null): Builder
    {
        $query = User::query()
            ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery
                ->whereIn('name', ['Cliente', 'Institucion']));

        if ($laboratoryId) {
            $query->where(function (Builder $personnelQuery) use ($laboratoryId): void {
                $personnelQuery
                    ->whereHas('personnelProfile', fn (Builder $profileQuery) => $profileQuery
                        ->where('laboratory_id', $laboratoryId))
                    ->orWhere(function (Builder $fallbackQuery) use ($laboratoryId): void {
                        $fallbackQuery
                            ->whereDoesntHave('personnelProfile', fn (Builder $profileQuery) => $profileQuery
                                ->whereNotNull('laboratory_id'))
                            ->whereHas('hospital', fn (Builder $hospitalQuery) => $hospitalQuery
                                ->where('laboratory_id', $laboratoryId));
                    });
            });
        }

        return $query;
    }

    private function normalizePersonName(?string $name): string
    {
        return Str::of((string) $name)
            ->squish()
            ->lower()
            ->title()
            ->toString();
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function jobCatalog(): array
    {
        return [
            'Operacion tecnica' => [
                'Responsable sanitario',
                'Coordinador de produccion',
                'Preparador de mezclas',
                'Verificador',
                'Auxiliar tecnico',
            ],
            'Calidad' => [
                'Garantia de calidad',
                'Control microbiologico',
                'Control documental',
            ],
            'Logistica y administracion' => [
                'Responsable de almacen',
                'Auxiliar de almacen',
                PersonnelProfile::POSITION_COURIER,
                'Capturista administrativo',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function jobNames(): array
    {
        return collect($this->jobCatalog())->flatten()->values()->all();
    }
}
