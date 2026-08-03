<?php

namespace App\Models\Nutricionales;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hospital;

class NutriMedicineList extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'active_brands',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_brands' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(NutriMedicineListItem::class, 'nutri_medicine_list_id');
    }

    public function hospitals()
    {
        return $this->hasMany(Hospital::class, 'nutri_medicine_list_id');
    }

    public function distributor()
    {
        return $this->hasOne(NutriDistributor::class, 'nutri_medicine_list_id');
    }
}
