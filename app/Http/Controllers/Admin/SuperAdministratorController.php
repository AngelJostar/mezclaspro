<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SuperAdministratorController extends Controller
{
    private const EXCLUDED_PERSONNEL_ROLES = [
        'Admin',
        'Super Admin',
        'Institucion',
        'Cliente',
    ];

    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

    public function index(): View
    {
        $administrators = User::query()
            ->role('Admin')
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', 'Super Admin'))
            ->with(['hospital:id,name', 'roles:id,name'])
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();

        $eligiblePersonnel = User::query()
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', self::EXCLUDED_PERSONNEL_ROLES);
            })
            ->with(['hospital:id,name', 'roles:id,name'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('lastname')
            ->get();

        return view('admin.superadministrator.index', compact('administrators', 'eligiblePersonnel'));
    }

    public function dismiss(Request $request, User $administrator): RedirectResponse
    {
        $this->ensureSuperAdministrator($request);

        DB::transaction(function () use ($administrator) {
            $lockedAdministrator = User::query()
                ->lockForUpdate()
                ->findOrFail($administrator->id);

            if (! $lockedAdministrator->hasRole('Admin') || $lockedAdministrator->hasRole('Super Admin')) {
                throw ValidationException::withMessages([
                    'administrator' => 'El usuario seleccionado no puede ser destituido desde este apartado.',
                ]);
            }

            $lockedAdministrator->removeRole('Admin');
            $this->userAccessService->block($lockedAdministrator);
        });

        return redirect()
            ->route('admin.superadministrator.index')
            ->with('status', 'El administrador fue destituido y sus accesos quedaron bloqueados.');
    }

    public function appoint(Request $request, User $personnel): RedirectResponse
    {
        $this->ensureSuperAdministrator($request);

        DB::transaction(function () use ($personnel) {
            $lockedPersonnel = User::query()
                ->lockForUpdate()
                ->findOrFail($personnel->id);

            if ($lockedPersonnel->hasAnyRole(self::EXCLUDED_PERSONNEL_ROLES)) {
                throw ValidationException::withMessages([
                    'personnel' => 'El usuario seleccionado no es personal elegible para este nombramiento.',
                ]);
            }

            $lockedPersonnel->syncRoles(['Admin']);
            $lockedPersonnel->forceFill([
                'is_active' => true,
                'remember_token' => Str::random(60),
            ])->save();
        });

        return redirect()
            ->route('admin.superadministrator.index')
            ->with('status', 'El personal seleccionado ahora tiene acceso de administrador.');
    }

    private function ensureSuperAdministrator(Request $request): void
    {
        abort_unless($request->user()?->hasRole('Super Admin'), 403);
    }
}
