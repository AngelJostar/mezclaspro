<?php

namespace App\Models\Oncologicos;

use App\Models\Hospital;
use App\Models\PersonnelProfile;
use App\Models\Warehouse;
use App\Models\Oncologicos\MedicineBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratory extends Model
{
    protected $fillable = [
        'nombre',
        'estado',
        'direccion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function medicineBatches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    public function hospitals(): HasMany
    {
        return $this->hasMany(Hospital::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(LaboratoryPurchaseOrder::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function personnelProfiles(): HasMany
    {
        return $this->hasMany(PersonnelProfile::class);
    }
}
