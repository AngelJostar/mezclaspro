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
            ->with('personnelProfile:id,user_id,positions,employment_status')
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Las credenciales proporcionadas no son válidas.'],
            ]);
        }

        if (! $user->is_active || ! $this->isMessenger($user)) {
            throw ValidationException::withMessages([
                'username' => ['Este usuario no tiene acceso a la aplicación de mensajería.'],
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
        $user->loadMissing('personnelProfile:id,user_id,positions,employment_status');

        abort_unless($user->is_active && $this->isMessenger($user), 403, 'Acceso de mensajero requerido.');

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

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim($user->name.' '.$user->lastname),
            'username' => $user->username,
        ];
    }
}
