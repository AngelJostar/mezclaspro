<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\PersonnelProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->whereRaw('LOWER(username) = ?', [strtolower(trim($credentials['username']))])
            ->with([
                'personnelProfile:id,user_id,positions,employment_status',
                'hospital:id,name,short_name,is_active,access_is_active',
            ])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales proporcionadas no son válidas.'],
            ]);
        }

        if (! $user->is_active || ! $this->accessModule($user)) {
            throw ValidationException::withMessages([
                'username' => ['Este usuario no tiene acceso a la aplicación móvil.'],
            ]);
        }

        $tokenName = 'mobile-rutas';
        $user->tokens()->where('name', $tokenName)->delete();
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userData($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing([
            'personnelProfile:id,user_id,positions,employment_status',
            'hospital:id,name,short_name,is_active,access_is_active',
        ]);

        abort_unless($user->is_active && $this->accessModule($user), 403, 'Acceso movil requerido.');

        return response()->json(['user' => $this->userData($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    private function isMessenger(User $user): bool
    {
        $profile = $user->personnelProfile;

        return $profile
            && $profile->employment_status === 'hired'
            && in_array(PersonnelProfile::POSITION_COURIER, $profile->positions ?? [], true);
    }

    private function isHospitalUser(User $user): bool
    {
        return (bool) $user->hospital_id
            && $user->hasAnyRole(['Cliente', 'Institucion'])
            && $user->hospital
            && $user->hospital->is_active
            && $user->hospital->access_is_active;
    }

    private function accessModule(User $user): ?string
    {
        if ($this->isMessenger($user)) {
            return 'courier';
        }

        return $this->isHospitalUser($user) ? 'hospital' : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        $module = $this->accessModule($user);

        return [
            'id' => $user->id,
            'name' => trim($user->name.' '.$user->lastname),
            'username' => $user->username,
            'module' => $module,
            'hospital' => $module === 'hospital' ? [
                'id' => $user->hospital?->id,
                'name' => $user->hospital?->name,
                'short_name' => $user->hospital?->short_name,
            ] : null,
        ];
    }
}
