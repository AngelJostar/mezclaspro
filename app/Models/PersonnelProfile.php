<?php

namespace App\Models;

use App\Models\Oncologicos\Laboratory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelProfile extends Model
{
    public const POSITION_COURIER = 'Mensajero de red fria';

    protected $fillable = [
        'user_id',
        'laboratory_id',
        'paternal_surname',
        'maternal_surname',
        'phone',
        'personal_email',
        'positions',
        'department',
        'hire_date',
        'employment_status',
        'force_password_change',
        'cv_path',
        'cv_original_name',
        'prior_experience',
        'additional_information',
    ];

    protected $casts = [
        'positions' => 'array',
        'hire_date' => 'date',
        'force_password_change' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }
}
