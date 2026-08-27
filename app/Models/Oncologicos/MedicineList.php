<?php

namespace App\Models\Oncologicos;

use App\Models\Hospital;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineList extends Model
{
    protected $fillable = [
        'user_id',
        'laboratory_id',
        'warehouse_id',
        'backup_enabled',
        'backup_warehouse_id',
        'is_backup',
        'primary_warehouse_id',
        'name',
        'description',
        'catalog_category',
        'active_brands',
        'charge_by',
        'show_label_lot_expiry',
        'has_contract',
        'contract_number',
        'contract_information',
        'has_mixing_service',
        'mixing_service_price',
    ];

    protected $casts = [
        'active_brands' => 'boolean',
        'charge_by' => 'string',
        'show_label_lot_expiry' => 'boolean',
        'has_contract' => 'boolean',
        'has_mixing_service' => 'boolean',
        'mixing_service_price' => 'decimal:4',
        'backup_enabled' => 'boolean',
        'is_backup' => 'boolean',
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

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function backupWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'backup_warehouse_id');
    }

    public function primaryWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'primary_warehouse_id');
    }
}
