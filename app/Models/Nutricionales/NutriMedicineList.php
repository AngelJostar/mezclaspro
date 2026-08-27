<?php

namespace App\Models\Nutricionales;

use App\Models\Hospital;
use App\Models\Oncologicos\Laboratory;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutriMedicineList extends Model
{
    protected $fillable = [
        'laboratory_id',
        'warehouse_id',
        'backup_enabled',
        'backup_warehouse_id',
        'is_backup',
        'primary_warehouse_id',
        'name',
        'description',
        'is_active',
        'active_brands',
        'has_contract',
        'contract_number',
        'contract_information',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_brands' => 'boolean',
        'has_contract' => 'boolean',
        'backup_enabled' => 'boolean',
        'is_backup' => 'boolean',
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
