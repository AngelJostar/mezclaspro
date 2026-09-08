<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Hash;

final class PersonnelCredentialDisplay
{
    public static function forUser(User $user): array
    {
        $copies = [];
        foreach (['software' => 'credential_password', 'training' => 'training_credential_password'] as $context => $field) {
            try {
                $copies[$context] = (string) ($user->{$field} ?? '');
            } catch (DecryptException) {
                $copies[$context] = '';
            }
        }

        $result = [];
        foreach (['software' => 'password', 'training' => 'training_password'] as $context => $field) {
            $hash = (string) ($user->{$field} ?? '');
            $value = '';

            // A stored copy may be stale or belong to the other independent login.
            if ($hash !== '') {
                $candidates = array_unique([$copies[$context], ...array_values($copies)]);
                foreach ($candidates as $candidate) {
                    if ($candidate === '') continue;
                    try {
                        if (Hash::check($candidate, $hash)) {
                            $value = $candidate;
                            break;
                        }
                    } catch (\RuntimeException) {
                        // Do not display an unverified credential or interrupt the personnel list.
                    }
                }
            }

            $result[$context] = [
                'value' => $value,
                'configured' => $hash !== '',
                'empty_label' => $hash !== '' ? 'Configurada (no recuperable)' : 'Sin configurar',
                'empty_hint' => $hash !== ''
                    ? 'Tiene una contrasena configurada, pero no existe una copia recuperable de la clave actual.'
                    : 'Este acceso no tiene una contrasena configurada.',
            ];
        }

        return $result;
    }
}
