<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\Oncologicos\Laboratory;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\UserAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const NON_PERSONNEL_ROLES = ['Cliente', 'Institucion'];

    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status') === 'bajas' ? 'bajas' : 'contratados';
        $sort = in_array($request->query('sort'), ['personal', 'usuario', 'hospital', 'rol'], true)
            ? $request->query('sort')
            : 'personal';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $laboratories = Laboratory::query()
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->get();
        $centralFilter = (string) $request->query('laboratory_id', 'all');
        $selectedLaboratoryId = ctype_digit($centralFilter)
            && $laboratories->contains('id', (int) $centralFilter)
                ? (int) $centralFilter
                : null;
        $centralFilter = $selectedLaboratoryId
            ? (string) $selectedLaboratoryId
            : ($centralFilter === 'unassigned' ? 'unassigned' : 'all');
        $personnelQuery = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', self::NON_PERSONNEL_ROLES);
            });

        $centralPersonnelCounts = (clone $personnelQuery)
            ->join('warehouses as personnel_warehouses', 'personnel_warehouses.id', '=', 'users.warehouse_id')
            ->selectRaw('personnel_warehouses.laboratory_id, COUNT(*) as personnel_count')
            ->groupBy('personnel_warehouses.laboratory_id')
            ->pluck('personnel_count', 'personnel_warehouses.laboratory_id');
        $unassignedCentralPersonnelCount = (clone $personnelQuery)
            ->whereDoesntHave('warehouse.laboratory')
            ->count();
        $totalPersonnelCount = (clone $personnelQuery)->count();

        if ($selectedLaboratoryId) {
            $personnelQuery->whereHas('warehouse', function ($query) use ($selectedLaboratoryId) {
                $query->where('laboratory_id', $selectedLaboratoryId);
            });
        } elseif ($centralFilter === 'unassigned') {
            $personnelQuery->whereDoesntHave('warehouse.laboratory');
        }

        $personnelCounts = [
            'contratados' => (clone $personnelQuery)->where('is_active', true)->count(),
            'bajas' => (clone $personnelQuery)->where('is_active', false)->count(),
        ];

        $usersQuery = $personnelQuery
            ->select('id', 'name', 'lastname', 'username', 'is_active', 'hospital_id', 'warehouse_id')
            ->with(['roles:id,name', 'hospital:id,name', 'warehouse:id,name'])
            ->where('is_active', $status === 'contratados');

        match ($sort) {
            'usuario' => $usersQuery->orderBy('username', $direction),
            'hospital' => $usersQuery->orderBy(
                Hospital::query()
                    ->select('name')
                    ->whereColumn('hospitals.id', 'users.hospital_id')
                    ->limit(1),
                $direction
            ),
            'rol' => $usersQuery->orderBy(
                Role::query()
                    ->select('roles.name')
                    ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->whereColumn('model_has_roles.model_id', 'users.id')
                    ->where('model_has_roles.model_type', User::class)
                    ->orderBy('roles.name')
                    ->limit(1),
                $direction
            ),
            default => $usersQuery
                ->orderBy('name', $direction)
                ->orderBy('lastname', $direction),
        };

        $users = $usersQuery
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact(
            'users',
            'status',
            'personnelCounts',
            'sort',
            'direction',
            'laboratories',
            'centralFilter',
            'centralPersonnelCounts',
            'unassignedCentralPersonnelCount',
            'totalPersonnelCount'
        ));
    }

    public function create()
    {
        $hospitals = Hospital::all();
        $roles = Role::all();
        $warehouses = Warehouse::query()
            ->with('laboratory:id,nombre')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        // ❌ ya no se selecciona lista por usuario
        return view('admin.users.create', compact('hospitals', 'roles', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'lastname' => 'string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|confirmed',
            'hospital_id' => 'required|exists:hospitals,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'lastname' => $request->lastname,
            'username' => $request->username,
            'hospital_id' => $request->hospital_id,
            'warehouse_id' => $request->warehouse_id,
            'password' => Hash::make($request->password),
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
        $warehouses = Warehouse::query()
            ->with('laboratory:id,nombre')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $canManageRoles = Auth::user()?->hasRole('Super Admin') ?? false;

        // ❌ ya no se manda medicineLists
        return view('admin.users.edit', compact('user', 'hospitals', 'roles', 'warehouses', 'canManageRoles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'string|max:255',
            'lastname' => 'string|max:255',
            'username' => 'string|max:255|unique:users,username,'.$user->id,
            'password' => 'nullable|string|confirmed',
            'hospital_id' => 'exists:hospitals,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user->name = $request->name;
        $user->lastname = $request->lastname;
        $user->username = $request->username;
        $user->hospital_id = $request->hospital_id;
        $user->warehouse_id = $request->warehouse_id;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
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

    public function destroy(User $user)
    {
        //
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'personnel' => 'No puedes dar de baja tu propia cuenta.',
            ]);
        }

        DB::transaction(function () use ($request, $user) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($lockedUser->hasAnyRole(self::NON_PERSONNEL_ROLES)) {
                throw ValidationException::withMessages([
                    'personnel' => 'La cuenta seleccionada no pertenece al personal.',
                ]);
            }

            if ($lockedUser->hasRole('Super Admin') && ! $request->user()->hasRole('Super Admin')) {
                abort(403);
            }

            if ($lockedUser->hasRole('Super Admin')) {
                $hasAnotherActiveSuperAdministrator = User::query()
                    ->role('Super Admin')
                    ->where('is_active', true)
                    ->whereKeyNot($lockedUser->id)
                    ->exists();

                if (! $hasAnotherActiveSuperAdministrator) {
                    throw ValidationException::withMessages([
                        'personnel' => 'Debe permanecer al menos un superadministrador activo.',
                    ]);
                }
            }

            if ($lockedUser->is_active) {
                $this->userAccessService->block($lockedUser);
            }
        });

        return redirect()
            ->route('admin.users.index', ['status' => 'bajas'])
            ->with('status', 'El personal fue dado de baja y sus accesos quedaron bloqueados.');
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
}
