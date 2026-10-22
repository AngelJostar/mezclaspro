<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institucion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitutionFieldController extends Controller
{
    public function edit(Institucion $institucion, string $field)
    {
        return response()->json([
            'value' => (string) $institucion->{$field},
            'version' => hash('sha256', (string) $institucion->{$field}),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, Institucion $institucion, string $field)
    {
        $data = $request->validate([
            'value' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'size:64'],
        ]);
        DB::transaction(function () use ($data, $institucion, $field) {
            $current = Institucion::whereKey($institucion->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals(hash('sha256', (string) $current->{$field}), $data['version']), 409,
                'Este dato cambió. Cierra y vuelve a abrir la ventana para cargar el valor actual.');
            $current->update([$field => $data['value']]);
        });

        return response()->json(['message' => 'Cambios guardados.']);
    }

    public function updateCredential(Request $request, Institucion $institucion, User $user, string $field)
    {
        abort_unless($request->user()->can('usuarios'), 403);
        abort_unless($user->hasAnyRole(['Cliente', 'Institucion'])
            && $institucion->hospitals()->whereKey($user->hospital_id)->exists(), 404);

        $controller = app(UserController::class);
        return $field === 'username'
            ? $controller->updateUsername($request, $user)
            : $controller->updatePassword($request, $user);
    }
}
