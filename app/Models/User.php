<?php

namespace App\Models;

use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\MedicineList;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    protected $fillable = [
        'name',
        'lastname',
        'password',
        'username',
        'is_active',
        'hospital_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];


    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
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
}
