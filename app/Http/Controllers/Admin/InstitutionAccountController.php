<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use App\Models\User;
use App\Support\InstitutionPortal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstitutionAccountController extends Controller
{
    public function store(Request $request, Institucion $institucion)
    {
        abort_unless($request->user()->can('usuarios'), 403);
        $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);
        $data = $request->validate($this->rules());
        DB::transaction(function () use ($institucion, $data) {
            Institucion::whereKey($institucion->id)->lockForUpdate()->firstOrFail();
            abort_if($institucion->account()->exists(), 409, 'La institución ya tiene un usuario. Edita su acceso.');
            $user = $institucion->account()->create([
                'name' => $institucion->nombre, 'lastname' => '', 'username' => $data['username'],
                'password' => Hash::make($data['password']), 'credential_password' => $data['password'],
                'hospital_id' => null, 'is_active' => true,
            ]);
            $user->assignRole(Role::findOrCreate('Institucion', 'web'));
            foreach (InstitutionPortal::PERMISSIONS as $permission) {
                $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
            }
        });
        return $request->expectsJson()
            ? response()->json(['message' => 'Acceso de institución creado. Solo consulta.'], 201)
            : back()->with('status', 'Acceso de institución creado. Solo consulta.');
    }

    public function update(Request $request, Institucion $institucion, string $field)
    {
        abort_unless($request->user()->can('usuarios'), 403);
        abort_unless(in_array($field, ['username', 'password'], true), 404);
        $user = $institucion->account()->firstOrFail();
        if ($field === 'username') $request->merge(['username' => Str::lower(trim((string) $request->input('username')))]);
        $data = $request->validate([$field => $this->rules($user->id)[$field]]);
        $user->forceFill($field === 'username' ? $data : [
            'password' => Hash::make($data['password']), 'credential_password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();
        return response()->json(['message' => 'Acceso actualizado.']);
    }

    private function rules(?int $userId = null): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9._@-]+$/',
                Rule::unique('users', 'username')->ignore($userId), Rule::unique('users', 'training_username')->ignore($userId)],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
