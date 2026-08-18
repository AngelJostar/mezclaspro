<?php

namespace App\Models\Oncologicos;

use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MedicineList extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'catalog_category',
        'active_brands',
        'charge_by',
        'show_label_lot_expiry',
        'has_contract',
        'contract_number',
        'contract_information',
    ];

    protected $casts = [
        'active_brands' => 'boolean',
        'charge_by' => 'string',
        'show_label_lot_expiry' => 'boolean',
        'has_contract' => 'boolean',
    ];

    public function scopeForCategory($query, string $category)
    {
        return $query->where('catalog_category', $category);
    }

    public function medicines()
    {
        return $this->belongsToMany(
            MedicineOnco::class,
            'medicine_medicine_lists',
            'medicine_list_id',
            'medicine_id'
        )->withPivot('precio')->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'medicine_list_id');
    }

    public function chargeByMg(): bool
    {
        return $this->charge_by === 'mg';
    }

    public function chargeByFrasco(): bool
    {
        return $this->charge_by === 'frasco';
    }

    public function presentations()
    {
        return $this->belongsToMany(
            MedicinePresentation::class,
            'medicine_list_presentation',
            'medicine_list_id',
            'medicine_presentation_id'
        )->withPivot([
            'charge_by',
            'precio',
            'precio_mg_override',
            'iva_desglosado',
            'descripcion_remision',
        ])->withTimestamps();
    }

    public function distributor()
    {
        return $this->hasOne(Distributor::class, 'medicine_list_id', 'id');
    }

    public function hospital()
    {
        return $this->hasOne(Hospital::class, 'onco_medicine_list_id', 'id');
    }
}
