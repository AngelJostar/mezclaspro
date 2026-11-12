<?php

namespace App\Services\Agents;

use App\Models\AiAgent;
use App\Models\PersonnelProfile;
use App\Models\User;
use App\Services\Clinical\ClinicalEvidence;
use App\Support\AdminMenuAccess;
use App\Support\PersonnelPositions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AgentAccess
{
    public const NONE = ['can_view' => false, 'can_run' => false, 'can_edit' => false];
    public const LABELS = ['can_view' => 'Consultar', 'can_run' => 'Ejecutar', 'can_edit' => 'Editar'];

    public function permissions(User $user, AiAgent $agent): array
    {
        if (isset($user->is_active) && !$user->is_active) return self::NONE;
        if ($user->hasRole('Super Admin')) return array_fill_keys(array_keys(self::NONE), true);
        // Resolve membership and grants afresh: a removed role, position or grant takes effect immediately.
        return $this->resolve($agent->accessGrants()->get(), (string) $user->id,
            $user->roles()->pluck('roles.id')->map(fn ($id) => (string) $id)->all(),
            $user->personnelProfile()->first()?->positions ?? []);
    }

    private function resolve(Collection $grants, string $userId, array $roles, array $positions): array
    {
        $direct = $grants->first(fn ($grant) => $grant->subject_type === 'user' && $grant->subject_key === $userId);
        $matches = $direct ? collect([$direct]) : $grants->filter(fn ($grant) =>
            ($grant->subject_type === 'role' && in_array($grant->subject_key, $roles, true))
            || ($grant->subject_type === 'position' && in_array($grant->subject_key, $positions, true)));
        $result = self::NONE;
        foreach (array_keys($result) as $key) $result[$key] = $matches->contains(fn ($grant) => (bool) $grant->$key);
        if (!$result['can_view']) return self::NONE;
        return $result;
    }

    public function authorize(User $user, AiAgent $agent, string $permission = 'can_view'): void
    {
        abort_unless($this->permissions($user, $agent)[$permission] ?? false, 403, 'No tienes esta autorización para el agente.');
    }

    public function canViewValidationRules(User $user): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Auxiliar de responsable sanitario'])) return true;

        return $this->clinicalAgentPermission($user, 'can_view');
    }

    public function canEditValidationRules(User $user): bool
    {
        if ($user->hasRole('Super Admin')) return true;

        return $this->clinicalAgentPermission($user, 'can_edit');
    }

    public function canApproveValidationRules(User $user): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Auxiliar de responsable sanitario'])) return true;

        return $this->clinicalAgentPermission($user, 'can_edit');
    }

    private function clinicalAgentPermission(User $user, string $permission): bool
    {
        $clinicalAgent = AiAgent::query()
            ->where('integration_key', ClinicalEvidence::KEY)
            ->first();

        return $clinicalAgent !== null
            && ($this->permissions($user, $clinicalAgent)[$permission] ?? false);
    }

    public function isHospitalScoped(User $user): bool
    {
        return $user->isInstitutionAccount() || $user->hasAnyRole(['Cliente', 'Institucion']);
    }

    public function effectiveUsers(AiAgent $agent): Collection
    {
        $grants = $agent->accessGrants()->get();
        if ($grants->isEmpty()) return collect();
        return User::with(['roles', 'personnelProfile'])->orderBy('name')->get()
            ->reject(fn ($user) => $user->hasRole('Super Admin') || (isset($user->is_active) && !$user->is_active))
            ->map(function ($user) use ($grants) {
                $permissions = $this->resolve($grants, (string) $user->id, $user->roles->pluck('id')->map(fn ($id) => (string) $id)->all(), $user->personnelProfile?->positions ?? []);
                return ['id' => $user->id, 'name' => $user->name, 'permissions' => $permissions];
            })->filter(fn ($entry) => $entry['permissions']['can_view'])->values();
    }

    public function editorRows(AiAgent $agent): array
    {
        $grants = $agent->accessGrants()->get()->keyBy(fn ($g) => $g->subject_type.':'.$g->subject_key);
        $rows = [];
        $add = function ($type, $key, $label) use (&$rows, $grants) {
            $grant = $grants->get($type.':'.$key);
            $row = ['subject_type' => $type, 'subject_key' => (string) $key, 'label' => $label, 'assigned' => (bool) $grant];
            foreach (array_keys(self::NONE) as $permission) $row[$permission] = (bool) $grant?->$permission;
            $rows[] = $row;
        };
        foreach (User::with('roles')->orderBy('name')->get() as $user) {
            if (!$user->hasRole('Super Admin')) $add('user', $user->id, $user->name.($user->username ? ' · '.$user->username : ''));
        }
        foreach (Role::where('guard_name', 'web')->where('name', '!=', 'Super Admin')->orderBy('name')->get() as $role) {
            $add('role', $role->id, AdminMenuAccess::roleLabel($role->name));
        }
        $positions = collect(PersonnelPositions::catalog())->flatten()
            ->merge(PersonnelProfile::all()->flatMap(fn ($profile) => $profile->positions ?? []))
            ->merge($grants->where('subject_type', 'position')->pluck('subject_key'))->unique()->sort();
        foreach ($positions as $position) $add('position', $position, $position);
        return $rows;
    }

    public function revision(AiAgent $agent): string
    {
        return hash('sha256', $agent->accessGrants()->orderBy('id')->get()->toJson());
    }

    public function replace(User $actor, AiAgent $agent, array $rows, string $revision): void
    {
        abort_unless($actor->hasRole('Super Admin'), 403);
        $rows = Validator::make(['rows' => $rows], [
            'rows' => 'array|max:5000', 'rows.*.subject_type' => ['required', Rule::in(['user', 'role', 'position'])],
            'rows.*.subject_key' => 'required|string|max:160', 'rows.*.assigned' => 'required|boolean',
            'rows.*.can_view' => 'required|boolean', 'rows.*.can_run' => 'required|boolean', 'rows.*.can_edit' => 'required|boolean',
        ])->validate()['rows'];
        $allowed = collect($this->editorRows($agent))->keyBy(fn ($row) => $row['subject_type'].':'.$row['subject_key']);
        $keys = [];
        $selected = [];
        foreach ($rows as $row) {
            $key = $row['subject_type'].':'.$row['subject_key'];
            if (!$allowed->has($key) || isset($keys[$key])) throw ValidationException::withMessages(['accessRows' => 'La selección de usuarios, puestos o roles cambió. Cierra y vuelve a abrir las autorizaciones.']);
            $keys[$key] = true;
            if (!$row['assigned']) continue;
            if (!$row['can_view'] && ($row['can_run'] || $row['can_edit'])) throw ValidationException::withMessages(['accessRows' => 'Ejecutar y editar requieren el permiso de consultar.']);
            $selected[] = \Illuminate\Support\Arr::only($row, ['subject_type', 'subject_key', 'can_view', 'can_run', 'can_edit']) + ['granted_by' => $actor->id];
        }
        DB::transaction(function () use ($agent, $revision, $selected) {
            AiAgent::whereKey($agent->id)->lockForUpdate()->firstOrFail();
            if (!hash_equals($revision, $this->revision($agent))) throw ValidationException::withMessages(['accessRows' => 'Otro superadministrador modificó los accesos. Cierra y vuelve a abrir para ver los permisos actuales.']);
            $agent->accessGrants()->delete();
            $agent->accessGrants()->createMany($selected);
        });
    }
}
