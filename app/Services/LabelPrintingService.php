<?php

namespace App\Services;

use App\Models\LabelPrint;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LabelPrintingService
{
    public const INITIAL_COPIES = 3;
    public const REPRINT_COPIES = 1;
    public const NUTRITION = 'nutrition_request';
    public const ONCOLOGY = 'oncology_mixture';

    public function summary(string $documentType, int $documentId): array
    {
        $query = LabelPrint::query()
            ->where('document_type', $documentType)
            ->where('document_id', $documentId);

        return [
            'has_prints' => (clone $query)->exists(),
            'copies' => (int) (clone $query)->sum('copies'),
            'events' => (int) (clone $query)->count(),
            'history' => (clone $query)
                ->with(['requester:id,name,lastname,username', 'authorizer:id,name,lastname,username'])
                ->latest('id')
                ->get(),
        ];
    }

    public function authorizeAndRecord(
        Model $document,
        string $documentType,
        User $user,
        ?string $password,
        ?string $ipAddress
    ): LabelPrint {
        return DB::transaction(function () use ($document, $documentType, $user, $password, $ipAddress) {
            $document::query()->whereKey($document->getKey())->lockForUpdate()->firstOrFail();

            $hasPrints = LabelPrint::query()
                ->where('document_type', $documentType)
                ->where('document_id', $document->getKey())
                ->exists();

            if ($hasPrints) {
                if (! $user->hasRole('Super Admin')) {
                    throw new AuthorizationException(
                        'Solo un Super administrador puede autorizar la reimpresion de etiquetas.'
                    );
                }

                if (! is_string($password) || $password === '' || ! Hash::check($password, $user->password)) {
                    throw ValidationException::withMessages([
                        'password' => 'La contrasena del Super administrador no es correcta.',
                    ]);
                }
            }

            return LabelPrint::create([
                'document_type' => $documentType,
                'document_id' => $document->getKey(),
                'copies' => $hasPrints ? self::REPRINT_COPIES : self::INITIAL_COPIES,
                'print_type' => $hasPrints ? LabelPrint::TYPE_REPRINT : LabelPrint::TYPE_INITIAL,
                'requested_by' => $user->id,
                'authorized_by' => $hasPrints ? $user->id : null,
                'ip_address' => $ipAddress,
            ]);
        });
    }
}
