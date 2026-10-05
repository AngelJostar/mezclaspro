<?php

namespace App\Models\Nutricionales;

use App\Models\Oncologicos\Diluent;
use App\Models\Oncologicos\DiluentPresentation;
use Illuminate\Database\Eloquent\Model;

class SolicitudDiluent extends Model
{
    protected $table = 'nutrition_solicitud_diluents';

    protected $fillable = [
        'solicitud_id', 'diluent_id', 'diluent_presentation_id', 'volume_ml',
        'generic_name', 'commercial_name', 'presentation_name', 'lot', 'expires_at',
    ];

    protected $casts = [
        'volume_ml' => 'float',
        'expires_at' => 'date',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function diluent()
    {
        return $this->belongsTo(Diluent::class);
    }

    public function presentation()
    {
        return $this->belongsTo(DiluentPresentation::class, 'diluent_presentation_id');
    }
}
