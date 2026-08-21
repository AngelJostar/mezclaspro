<?php

namespace App\Models;

use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\MedicineList;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    public const INSTITUTION_BLOCKED_MESSAGE = 'No tienes conexion a internet, revisa tu conexion.';

    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasRoles;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'lastname',
        'password',
        'credential_password',
        'username',
        'training_username',
        'training_password',
        'training_credential_password',
        'is_active',
        'hospital_id',
    ];

    protected $hidden = [
        'password',
        'credential_password',
        'training_password',
        'training_credential_password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'credential_password' => 'encrypted',
        'training_credential_password' => 'encrypted',
        'is_active' => 'boolean',
    ];

    public function setUsernameAttribute(?string $value): void
    {
        $this->attributes['username'] = $value === null
            ? null
            : Str::lower(trim($value));
    }

    public function setTrainingUsernameAttribute(?string $value): void
    {
        $normalizedValue = $value === null ? null : trim($value);

        $this->attributes['training_username'] = blank($normalizedValue)
            ? null
            : Str::lower($normalizedValue);
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function personnelProfile(): HasOne
    {
        return $this->hasOne(PersonnelProfile::class);
    }

    public function isBlockedByInstitution(): bool
    {
        if (! $this->hospital_id || ! $this->hasAnyRole(['Cliente', 'Institucion'])) {
            return false;
        }

        return $this->hospital()
            ->whereHas('instituciones', fn ($query) => $query->where('clientes.is_active', false))
            ->exists();
    }

    public function isBlockedByHospital(): bool
    {
        if (! $this->hospital_id || ! $this->hasAnyRole(['Cliente', 'Institucion'])) {
            return false;
        }

        return $this->hospital()
            ->where('access_is_active', false)
            ->exists();
    }

    public function isBlockedByOrganization(): bool
    {
        return $this->isBlockedByHospital() || $this->isBlockedByInstitution();
    }

    //Relacion uno a uno
    public function solicitud()
    {
        return $this->hasOne(Solicitud::class);
    }

    public function medicineList()
    {
        return $this->belongsTo(MedicineList::class, 'medicine_list_id', 'id');
    }

    public static function suggestTrainingUsername(string $softwareUsername, ?int $ignoreUserId = null): string
    {
        $cleanUsername = Str::lower(
            preg_replace('/[^A-Za-z0-9._-]/', '', $softwareUsername) ?: 'usuario'
        );
        $base = Str::limit('cap'.$cleanUsername, 230, '');
        $candidate = $base;
        $suffix = 1;

        while (static::query()
            ->when($ignoreUserId, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where(function ($query) use ($candidate) {
                $normalizedCandidate = Str::lower($candidate);

                $query->whereRaw('LOWER(username) = ?', [$normalizedCandidate])
                    ->orWhereRaw('LOWER(training_username) = ?', [$normalizedCandidate]);
            })
            ->exists()) {
            $candidate = Str::limit($base, 240, '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
