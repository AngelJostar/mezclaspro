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
    use HasRoles {
        checkPermissionTo as private checkRolePermissionTo;
    }
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
        'institution_id',
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

    public function getEmailForPasswordReset(): string
    {
        // Laravel's token table keeps its legacy email column, but accounts use usernames.
        return (string) $this->username;
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function isInstitutionAccount(): bool
    {
        return (int) $this->institution_id > 0;
    }

    public function checkPermissionTo($permission, $guardName = null): bool
    {
        // Spatie's Gate hook runs before the application's hook. Cap legacy role grants here too.
        if ($this->isInstitutionAccount()) {
            $name = $permission instanceof \BackedEnum ? $permission->value : ($permission->name ?? $permission);
            return in_array($name, \App\Support\InstitutionPortal::PERMISSIONS, true);
        }
        return $this->checkRolePermissionTo($permission, $guardName);
    }

    public function accessibleHospitalIds(): array
    {
        if (! $this->isInstitutionAccount()) {
            return $this->hospital_id ? [(int) $this->hospital_id] : [];
        }

        // Query membership on every request; a removed hospital immediately loses access.
        return Hospital::whereHas('instituciones', fn ($query) => $query->where('clientes.id', $this->institution_id))
            ->pluck('hospitals.id')->map(fn ($id) => (int) $id)->all();
    }

    public function portalHospitalIds(): array
    {
        $ids = $this->accessibleHospitalIds();
        $selected = $this->isInstitutionAccount() ? request()->attributes->get('institution_hospital') : null;
        return $selected ? array_values(array_intersect($ids, [(int) $selected])) : $ids;
    }

    public function canConsultHospital($hospitalId): bool
    {
        return $hospitalId && in_array((int) $hospitalId, $this->accessibleHospitalIds(), true);
    }

    public function personnelProfile(): HasOne
    {
        return $this->hasOne(PersonnelProfile::class);
    }

    public function trainingPrograms(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(TrainingProgram::class, 'training_program_access', 'user_id', 'training_program_id')
            ->withPivot(['module_ids', 'all_modules'])->withTimestamps();
    }

    public function isSalesperson(): bool
    {
        return $this->hasAnyRole([PersonnelProfile::POSITION_SELLER, 'Ventas'])
            && ! $this->hasAnyRole(['Cliente', 'Institucion']);
    }

    public function hasSalesOnlyAccess(): bool
    {
        return $this->isSalesperson() && $this->roles->every(
            fn ($role) => in_array($role->name, [PersonnelProfile::POSITION_SELLER, 'Ventas', 'Capacitacion'], true)
        );
    }

    public function scopeActiveSalespeople($query)
    {
        return $query->where('is_active', true)
            ->whereHas('roles', fn ($roles) => $roles->whereIn('name', [PersonnelProfile::POSITION_SELLER, 'Ventas']))
            ->whereDoesntHave('roles', fn ($roles) => $roles->whereIn('name', ['Cliente', 'Institucion']));
    }

    public function canUseQuotationRequests(): bool
    {
        if ($this->isInstitutionAccount()) {
            return $this->institution()->exists();
        }
        if (! $this->hasAnyRole(['Cliente', 'Institucion'])) {
            return true;
        }

        // Fetch current settings even when the hospital relation was loaded with a limited column set.
        $hospital = $this->hospital()->with('instituciones')->first();

        // Unassigned accounts retain the existing empty-list behavior and normal scope checks.
        return ! $hospital || \App\Support\EnabledServices::hospital($hospital);
    }

    public function isBlockedByInstitution(): bool
    {
        if ($this->isInstitutionAccount()) {
            return ! $this->institution()->where('is_active', true)->exists();
        }
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
