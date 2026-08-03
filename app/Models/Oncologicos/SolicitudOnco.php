<?php

namespace App\Models\Oncologicos;

use App\Models\Hospital;
use App\Models\User;
use App\Models\Oncologicos\Laboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudOnco extends Model
{
    use HasFactory;

    protected $table = 'solicitud_oncos';

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'fecha_nacimiento' => 'date',
        'peso' => 'decimal:2',
        'edad' => 'integer',
    ];

    protected $fillable = [
        'user_id',
        'hospital_id',
        'servicio',
        'nombre_paciente',
        'sexo',
        'edad',
        'peso',
        'cama',
        'piso',
        'registro_paciente',
        'fecha_nacimiento',
        'diagnostico',
        'alergias',
        'fecha_entrega',
        'observaciones',
        'nombre_medico',
        'cedula_medico',
        'estado',
        'remision',
    ];

    public function mezclas(): HasMany
    {
        return $this->hasMany(Mezcla::class, 'solicitud_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

}
