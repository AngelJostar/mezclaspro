<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\User;
use App\Support\AdminMenuAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $isSuperAdmin = Auth::user()?->hasRole('Super Admin') ?? false;
        $section = (string) $request->query('section', $isSuperAdmin ? 'prodifem' : 'instituciones');

        if ($section !== 'instituciones') {
            return redirect()->route('admin.capacitaciones.personal');
        }

        if (! in_array($section, ['prodifem', 'instituciones'], true) || (! $isSuperAdmin && $section === 'prodifem')) {
            $section = 'instituciones';
        }

        $users = null;
        $institutions = null;

        if ($section === 'prodifem') {
            $users = User::select([
                'id',
                'name',
                'lastname',
                'username',
                'credential_password',
                'training_username',
                'training_credential_password',
                'is_active',
                'hospital_id',
            ])
                ->with([
                    'hospital:id,laboratory_id',
                    'hospital.laboratory:id,nombre',
                    'roles:id,name',
                    'permissions:id,name',
                ])
                ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery
                    ->whereIn('name', ['Cliente', 'Institucion']))
                ->orderBy('id')
                ->paginate(50)
                ->withQueryString();
        } else {
            $institutions = Institucion::query()
                ->select('id', 'nombre', 'razon_social', 'is_active')
                ->withCount('hospitals')
                ->with([
                    'hospitals' => fn ($hospitalQuery) => $hospitalQuery
                        ->select(
                            'hospitals.id',
                            'hospitals.name',
                            'hospitals.internal_key',
                            'hospitals.is_active',
                            'hospitals.access_is_active'
                        )
                        ->with([
                            'users' => fn ($userQuery) => $userQuery
                                ->select([
                                    'id',
                                    'hospital_id',
                                    'username',
                                    'credential_password',
                                    'training_username',
                                    'training_credential_password',
                                    'is_active',
                                ])
                                ->with('roles:id,name')
                                ->whereHas('roles', fn ($roleQuery) => $roleQuery
                                    ->whereIn('name', ['Cliente', 'Institucion']))
                                ->orderByDesc('is_active')
                                ->orderBy('username'),
                        ])
                        ->orderBy('hospitals.name'),
                ])
                ->orderBy('nombre')
                ->paginate(25)
                ->withQueryString();
        }

        $roleAccessTree = AdminMenuAccess::tree();
        $roleOptions = AdminMenuAccess::roleOptions();

        return view('admin.users.index', compact(
            'users',
            'institutions',
            'section',
            'isSuperAdmin',
            'roleAccessTree',
            'roleOptions'
        ));
    }

    public function create(Request $request)
    {
        $hospitals = Hospital::all();
        $roles = Role::all();
        $requestedHospitalId = $request->integer('hospital_id');
        $selectedHospitalId = $hospitals->contains('id', $requestedHospitalId)
            ? $requestedHospitalId
            : null;
        $selectedRoleIds = $roles
            ->where('name', trim((string) $request->query('role')))
            ->pluck('id')
            ->all();

        // ❌ ya no se selecciona lista por usuario
        return view('admin.users.create', compact(
            'hospitals',
            'roles',
            'selectedHospitalId',
            'selectedRoleIds'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username'),
                Rule::unique('users', 'training_username'),
            ],
            'password' => 'required|string|confirmed',
            'hospital_id' => 'required|exists:hospitals,id',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'lastname' => $request->lastname,
            'username' => $request->username,
            'hospital_id' => $request->hospital_id,
            'password' => Hash::make($request->password),
            'credential_password' => $request->password,
            'training_username' => User::suggestTrainingUsername($request->username),
            'training_password' => Hash::make($request->password),
            'training_credential_password' => $request->password,
            'is_active' => $request->input('is_active', 1),
        ]);

        $user->roles()->sync($this->resolvedRoleIds($request));

        session()->flash('swal', [
            'title' => '¡Bien hecho!',
            'text' => 'El usuario se ha creado con éxito.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.users.index');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $hospitals = Hospital::all();

        $canManageRoles = Auth::user()?->hasRole('Super Admin') ?? false;

        // ❌ ya no se manda medicineLists
        return view('admin.users.edit', compact('user', 'hospitals', 'roles', 'canManageRoles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'string|max:255',
            'lastname' => 'string|max:255',
            'username' => [
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
                Rule::unique('users', 'training_username'),
            ],
            'password' => 'nullable|string|confirmed',
            'hospital_id' => 'exists:hospitals,id',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->username = $request->username;
        $user->hospital_id = $request->hospital_id;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->credential_password = $request->password;
        }

        $user->save();

        if (Auth::user()?->hasRole('Super Admin')) {
            $user->roles()->sync($this->resolvedRoleIds($request));
        }

        session()->flash('swal', [
            'title' => '¡Bien hecho!',
            'text' => 'El usuario se ha editado con éxito.',
            'icon' => 'success',
        ]);

        return redirect()->route('admin.users.index');
    }

    public function updateUsername(Request $request, User $user)
    {
        $request->merge([
            'username' => trim((string) $request->input('username')),
        ]);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
                Rule::unique('users', 'training_username'),
            ],
        ]);

        $user->username = trim($validated['username']);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'El nombre de usuario se actualizo correctamente.',
                'username' => $user->username,
            ]);
        }

        return back()->with('swal', [
            'title' => 'Usuario actualizado',
            'text' => 'El nombre de usuario se actualizo correctamente.',
            'icon' => 'success',
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->credential_password = $validated['password'];
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'La contrasena se actualizo correctamente.',
            ]);
        }

        return back()->with('swal', [
            'title' => 'Contrasena actualizada',
            'text' => 'La contrasena se actualizo correctamente.',
            'icon' => 'success',
        ]);
    }

    public function updateTrainingUsername(Request $request, User $user)
    {
        $request->merge([
            'training_username' => trim((string) $request->input('training_username')),
        ]);

        $validated = $request->validate([
            'training_username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'training_username')->ignore($user->id),
                Rule::unique('users', 'username'),
            ],
        ]);

        $user->training_username = trim($validated['training_username']);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'El usuario de capacitación se actualizó correctamente.',
                'value' => $user->training_username,
            ]);
        }

        return back()->with('swal', [
            'title' => 'Usuario actualizado',
            'text' => 'El usuario de capacitación se actualizó correctamente.',
            'icon' => 'success',
        ]);
    }

    public function updateTrainingPassword(Request $request, User $user)
    {
        $validated = $request->validate([
            'training_password' => ['required', 'string', 'max:255'],
        ]);

        $user->training_password = Hash::make($validated['training_password']);
        $user->training_credential_password = $validated['training_password'];
        $user->save();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'La contraseña de capacitación se actualizó correctamente.',
            ]);
        }

        return back()->with('swal', [
            'title' => 'Contraseña actualizada',
            'text' => 'La contraseña de capacitación se actualizó correctamente.',
            'icon' => 'success',
        ]);
    }

    public function updateRoleAccess(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in(array_keys(AdminMenuAccess::roleOptions()))],
            'menu_permissions' => ['nullable', 'array'],
            'menu_permissions.*' => [
                'string',
                Rule::in(AdminMenuAccess::permissionNames()),
            ],
        ]);

        if ($user->is(Auth::user()) && $validated['role'] !== 'Super Admin') {
            throw ValidationException::withMessages([
                'role' => 'No puedes retirar tu propio rol de superadministrador.',
            ]);
        }

        $menuPermissions = $validated['role'] === AdminMenuAccess::GENERAL_ROLE
            ? AdminMenuAccess::normalizeSelection($validated['menu_permissions'] ?? [])
            : [];

        DB::transaction(function () use ($user, $validated, $menuPermissions) {
            $role = Role::query()->firstOrCreate([
                'name' => $validated['role'],
                'guard_name' => 'web',
            ]);

            $user->syncRoles([$role]);

            if ($validated['role'] === AdminMenuAccess::GENERAL_ROLE) {
                $supportingPermissions = Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('name', AdminMenuAccess::supportingPermissionNames($menuPermissions))
                    ->pluck('name');

                $user->syncPermissions(
                    collect($menuPermissions)
                        ->merge($supportingPermissions)
                        ->unique()
                        ->values()
                        ->all()
                );

                return;
            }

            $preservedPermissions = $user->getDirectPermissions()
                ->pluck('name')
                ->diff(AdminMenuAccess::managedPermissionNames())
                ->values()
                ->all();

            $user->syncPermissions($preservedPermissions);
        });

        return response()->json([
            'message' => 'El rol y los accesos se actualizaron correctamente.',
            'role' => $validated['role'],
            'role_label' => AdminMenuAccess::roleLabel($validated['role']),
            'menu_permissions' => $menuPermissions,
        ]);
    }

    public function updateStatus(Request $request, User $user)
    {
        $isActive = $request->validate([
            'is_active' => ['required', 'boolean'],
        ])['is_active'];

        $this->setAccessStatus(collect([$user->id]), (bool) $isActive);

        return response()->json([
            'message' => $isActive ? 'El acceso se activó correctamente.' : 'El acceso se bloqueó correctamente.',
            'is_active' => (bool) $isActive,
            'affected_user_ids' => [$user->id],
        ]);
    }

    public function updateHospitalStatus(Request $request, Hospital $hospital)
    {
        $isActive = $request->validate([
            'is_active' => ['required', 'boolean'],
        ])['is_active'];
        $userIds = $hospital->users()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Cliente', 'Institucion']))
            ->pluck('users.id');

        if (! $isActive && $userIds->contains((int) Auth::id())) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes bloquear tu propio acceso.',
            ]);
        }

        DB::transaction(function () use ($hospital, $userIds, $isActive) {
            $hospital->update(['access_is_active' => (bool) $isActive]);

            if (! $isActive && $userIds->isNotEmpty() && Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            }
        });

        return response()->json([
            'message' => $isActive
                ? 'Los accesos del hospital se activaron correctamente.'
                : 'Los accesos del hospital se bloquearon correctamente.',
            'is_active' => (bool) $isActive,
            'affected_user_ids' => $userIds->values()->all(),
        ]);
    }

    public function updateInstitutionStatus(Request $request, Institucion $institucion)
    {
        $isActive = $request->validate([
            'is_active' => ['required', 'boolean'],
        ])['is_active'];
        $userIds = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Cliente', 'Institucion']))
            ->whereHas('hospital.instituciones', fn ($query) => $query->whereKey($institucion->id))
            ->pluck('users.id');

        if (! $isActive && $userIds->contains((int) Auth::id())) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes bloquear tu propio acceso.',
            ]);
        }

        DB::transaction(function () use ($institucion, $userIds, $isActive) {
            $institucion->update(['is_active' => (bool) $isActive]);

            if (! $isActive && $userIds->isNotEmpty() && Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            }
        });

        return response()->json([
            'message' => $isActive
                ? 'Los accesos de la institución se activaron correctamente.'
                : 'Los accesos de la institución se bloquearon correctamente.',
            'is_active' => (bool) $isActive,
            'affected_user_ids' => $userIds->values()->all(),
        ]);
    }

    public function destroy(User $user)
    {
        //
    }

    private function resolvedRoleIds(Request $request): array
    {
        $roleIds = collect($request->input('roles', []))
            ->map(fn ($roleId) => (int) $roleId)
            ->filter()
            ->unique()
            ->values();
        $exclusiveRoleIds = Role::query()
            ->whereIn('name', ['Capacitacion', 'Administracion y facturacion'])
            ->where('guard_name', 'web')
            ->pluck('id')
            ->map(fn ($roleId) => (int) $roleId);
        $selectedExclusiveRoleId = $roleIds
            ->first(fn ($roleId) => $exclusiveRoleIds->contains((int) $roleId));

        if ($selectedExclusiveRoleId) {
            return [(int) $selectedExclusiveRoleId];
        }

        return $roleIds->all();
    }

    private function setAccessStatus($userIds, bool $isActive): void
    {
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            throw ValidationException::withMessages([
                'is_active' => 'No existen accesos registrados para actualizar.',
            ]);
        }

        if (! $isActive && $userIds->contains((int) Auth::id())) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes bloquear tu propio acceso.',
            ]);
        }

        DB::transaction(function () use ($userIds, $isActive) {
            User::query()->whereKey($userIds)->update(['is_active' => $isActive]);

            if (! $isActive && Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            }
        });
    }
}
